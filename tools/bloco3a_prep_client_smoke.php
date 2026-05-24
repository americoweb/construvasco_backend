<?php

/**
 * Prepara pedido #6 com orçamento sent para smoke visual cliente (pontos 10–13).
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

include __DIR__ . '/bloco3a_prep_pr6.php';

$base = rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/');
$api = $base . '/api';

$res = Http::post("{$api}/auth/login", [
    'identifier' => 'gestor@construvasco.co.mz',
    'password' => 'Gestor@2026',
]);
$token = $res->json('access_token') ?? $res->json('token');

$quote = Http::acceptJson()->withToken($token)->post("{$api}/v1/manager/project-requests/6/quotes", [
    'quote_type' => 'architecture',
    'total_amount_mt' => 75000,
    'delivery_days' => 30,
    'conditions' => '50% sinal, 50% entrega',
]);

echo 'Quote HTTP ' . $quote->status() . ' id=' . ($quote->json('data.id') ?? '?') . "\n";
