<?php

/** Bloco 0 smoke test runner — API tests */
$base = 'http://127.0.0.1:8000/api';

function api(string $method, string $url, ?array $body = null, ?string $token = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 120,
    ]);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $respBody = substr($raw, $headerSize);

    return ['status' => $status, 'body' => $respBody, 'json' => json_decode($respBody, true)];
}

$roles = [
    'admin' => ['admin@construvasco.co.mz', 'Admin@2026'],
    'gestor' => ['gestor@construvasco.co.mz', 'Gestor@2026'],
    'tecnico' => ['tecnico@construvasco.co.mz', 'Tecnico@2026'],
    'cliente' => ['cliente@construvasco.co.mz', 'Cliente@2026'],
];

echo "=== TEST 1: Login API ===\n";
$tokens = [];
foreach ($roles as $role => [$email, $password]) {
    $r = api('POST', "$base/auth/login", ['identifier' => $email, 'password' => $password]);
    $token = $r['json']['access_token'] ?? null;
    $tokens[$role] = $token;
    $preview = $token ? substr($token, 0, 24) . '...' : 'NO_TOKEN';
    echo "1.$role ($email): HTTP {$r['status']} token=$preview\n";
    if ($r['status'] !== 200) {
        echo "  BODY: " . substr($r['body'], 0, 500) . "\n";
        exit(1);
    }
}

echo "\n=== TEST 3 prep: create request + upload ===\n";
$gestorToken = $tokens['gestor'];
$clienteId = null;
$r = api('GET', "$base/auth/me", null, $tokens['cliente']);
$clienteId = $r['json']['data']['id'] ?? $r['json']['id'] ?? 4;

$list = api('GET', "$base/v1/manager/project-requests", null, $gestorToken);
$requestId = $list['json']['data'][0]['id'] ?? null;
if (!$requestId) {
    $r = api('POST', "$base/v1/manager/project-requests", [
        'title' => 'Smoke Test Pedido ' . date('Y-m-d H:i:s'),
        'description' => 'Briefing mínimo smoke test Bloco 0',
        'project_type' => 'residencial',
        'user_id' => $clienteId,
        'submit' => true,
    ], $gestorToken);
    echo "Create request: HTTP {$r['status']}\n";
    if ($r['status'] !== 201 && $r['status'] !== 200) {
        echo "  BODY: " . substr($r['body'], 0, 800) . "\n";
        exit(1);
    }
    $requestId = $r['json']['data']['id'] ?? $r['json']['id'] ?? null;
} else {
    echo "Using existing request_id=$requestId\n";
}
echo "  request_id=$requestId\n";

// multipart upload
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$tmp = tempnam(sys_get_temp_dir(), 'smoke') . '.png';
file_put_contents($tmp, $png);
$ch = curl_init("$base/v1/manager/project-requests/$requestId/documents");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $gestorToken, 'Accept: application/json'],
    CURLOPT_POSTFIELDS => [
        'file' => new CURLFile($tmp, 'image/png', 'smoke.png'),
        'document_type' => 'referencia',
    ],
    CURLOPT_TIMEOUT => 120,
]);
$uploadBody = curl_exec($ch);
$uploadStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
unlink($tmp);
$uploadJson = json_decode($uploadBody, true);
$docId = $uploadJson['data']['id'] ?? null;
echo "Upload document: HTTP $uploadStatus doc_id=" . ($docId ?? 'null') . "\n";
if ($uploadStatus !== 201) {
    echo "  BODY: " . substr($uploadBody, 0, 800) . "\n";
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$row = Illuminate\Support\Facades\DB::selectOne(
    'SELECT id, project_request_id, project_id FROM project_documents WHERE id = ?',
    [$docId]
);
echo "SQL check: id={$row->id} project_request_id={$row->project_request_id} project_id=" . ($row->project_id === null ? 'NULL' : $row->project_id) . "\n";

echo "\n=== TEST 4: Technician project show ===\n";
$r = api('GET', "$base/v1/manager/projects", null, $tokens['admin']);
$projects = $r['json']['data'] ?? [];
if (isset($projects['data']) && is_array($projects['data'])) {
    $projects = $projects['data'];
}
$projectId = is_array($projects) && count($projects) > 0 ? ($projects[0]['id'] ?? null) : null;
echo "Admin list projects: HTTP {$r['status']} count=" . (is_array($projects) ? count($projects) : 0) . "\n";

if (!$projectId) {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $projectId = Illuminate\Support\Facades\DB::table('projects')->value('id');
    echo "Fallback project_id from SQL: " . ($projectId ?? 'null') . "\n";
}

if ($projectId) {
    // assign technician if admin token works
    $tecUser = App\Models\User::where('identifier', 'tecnico@construvasco.co.mz')->first();
    if ($tecUser) {
        $assign = api('POST', "$base/v1/manager/projects/$projectId/assign", [
            'assigned_to' => $tecUser->id,
            'assignment_role' => 'main',
        ], $tokens['admin']);
        echo "Assign technician: HTTP {$assign['status']}\n";
    }
    $showMgr = api('GET', "$base/v1/manager/projects/$projectId", null, $tokens['tecnico']);
    echo "Technician via MANAGER route: HTTP {$showMgr['status']}\n";
    $showTech = api('GET', "$base/v1/technician/projects/$projectId", null, $tokens['tecnico']);
    echo "Technician via TECHNICIAN route: HTTP {$showTech['status']}\n";
    if ($showTech['status'] !== 200) {
        echo "  BODY: " . substr($showTech['body'], 0, 500) . "\n";
        exit(1);
    }
} else {
    echo "WARN: No project found for test 4\n";
    exit(1);
}

echo "\nALL API TESTS PASSED\n";
