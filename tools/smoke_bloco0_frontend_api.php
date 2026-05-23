<?php
/** Test 2 helper: verify role from /auth/me matches expected dashboard path */
$base = 'http://127.0.0.1:8000/api';
$roles = [
    'admin' => ['admin@construvasco.co.mz', 'Admin@2026', '/admin/dashboard', 'admin'],
    'gestor' => ['gestor@construvasco.co.mz', 'Gestor@2026', '/admin/dashboard', 'project_manager'],
    'tecnico' => ['tecnico@construvasco.co.mz', 'Tecnico@2026', '/admin/dashboard', 'technician'],
    'cliente' => ['cliente@construvasco.co.mz', 'Cliente@2026', '/conta/dashboard', 'customer'],
];

function api(string $method, string $url, ?array $body = null, ?string $token = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 60,
    ]);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'json' => json_decode($body, true)];
}

echo "=== TEST 2 (API role → expected dashboard) ===\n";
foreach ($roles as $label => [$email, $password, $expectedPath, $expectedRole]) {
    $login = api('POST', "$base/auth/login", ['identifier' => $email, 'password' => $password]);
    $token = $login['json']['access_token'] ?? null;
    $me = api('GET', "$base/auth/me", null, $token);
    $role = $me['json']['user']['current_tenant_context']['role']
        ?? $me['json']['data']['current_tenant_context']['role']
        ?? $me['json']['tenant_context']['role']
        ?? $me['json']['current_tenant_context']['role']
        ?? 'unknown';
    $resolved = ($role === 'customer' || $role === '') ? '/conta/dashboard' : '/admin/dashboard';
    $ok = ($login['status'] === 200 && $me['status'] === 200 && $role === $expectedRole && $resolved === $expectedPath);
    echo "2.$label: login={$login['status']} me={$me['status']} role=$role expected_role=$expectedRole resolved=$resolved expected_path=$expectedPath " . ($ok ? 'OK' : 'FAIL') . "\n";
    if (!$ok) {
        exit(1);
    }
}

echo "\n=== TEST 6 (401 invalid token — backend) ===\n";
$bad = api('GET', "$base/v1/manager/project-requests", null, 'invalid.token.value');
echo "6.backend invalid token: HTTP {$bad['status']} (expect 401)\n";
if ($bad['status'] !== 401) {
    exit(1);
}

echo "\n=== TEST 7 check ===\n";
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$googleId = env('GOOGLE_CLIENT_ID');
echo 'GOOGLE_CLIENT_ID=' . (empty($googleId) ? 'empty' : 'configured') . "\n";
