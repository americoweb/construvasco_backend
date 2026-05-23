<?php

/**
 * Verificações estáticas do Bloco 2 (estúdio cliente).
 * Uso: php tools/bloco2_verify.php
 */

$root = dirname(__DIR__);
$errors = [];

function check(bool $ok, string $msg): void
{
    global $errors;
    if ($ok) {
        echo "  OK  {$msg}\n";
    } else {
        echo " FAIL {$msg}\n";
        $errors[] = $msg;
    }
}

echo "Bloco 2 — verificações\n\n";

$files = [
    'app/Services/Construction/StudioDraftService.php',
    'app/Http/Controllers/Customer/CustomerStudioController.php',
];
foreach ($files as $f) {
    check(is_file("{$root}/{$f}"), "Ficheiro {$f}");
}

$routes = file_get_contents("{$root}/routes/customer.php");
check(str_contains($routes, 'studio/state'), 'Rota GET studio/state');
check(str_contains($routes, 'studio/reset'), 'Rota POST studio/reset');

$ai = file_get_contents("{$root}/app/Http/Controllers/AI/AiGenerationController.php");
check(str_contains($ai, 'project_request_id'), 'Filtro project_request_id em AI index');

$creditsFile = file_get_contents("{$root}/config/credits.php");
preg_match("/initial_grant_amount.*?(\d+)/", $creditsFile, $m1);
preg_match("/cost_per_generation.*?(\d+)/", $creditsFile, $m2);
check((int) ($m1[1] ?? 0) === 5, 'Créditos iniciais = 5 (config, default env)');
check((int) ($m2[1] ?? 0) === 1, 'Custo geração = 1 (config, default env)');

$frontendRoot = dirname($root) . '/construvasco_frontend_v1';
if (is_dir($frontendRoot)) {
    check(is_file("{$frontendRoot}/src/app/shared/construction/studio.service.ts"), 'studio.service.ts');
    check(is_file("{$frontendRoot}/src/app/modules/account/views/customer-studio/customer-studio.component.ts"), 'customer-studio.component.ts');
    check(is_file("{$frontendRoot}/src/app/modules/account/views/customer-studio/studio-briefing.schema.ts"), 'studio-briefing.schema.ts');
    $routesTs = file_get_contents("{$frontendRoot}/src/app/app.routes.ts");
    check(str_contains($routesTs, "path: 'estudio'"), 'Rota /conta/estudio');
    check(str_contains($routesTs, 'studioCanDeactivateGuard'), 'CanDeactivate guard no estúdio');
} else {
    echo "  SKIP frontend (pasta não encontrada)\n";
}

echo "\n";
if ($errors) {
    echo count($errors) . " falha(s).\n";
    exit(1);
}
echo "Todas as verificações passaram.\n";
exit(0);
