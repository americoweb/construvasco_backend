<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private bool $testMode;

    public function __construct()
    {
        $this->baseUrl = (string) config('payment.base_url');
        $this->clientId = (string) config('payment.client_id');
        $this->clientSecret = (string) config('payment.client_secret');
        $this->testMode = (bool) config('payment.test_mode');
    }

    public function initiateC2b(string $method, float $amount, string $phone, string $reference): array
    {
        $tipo = $method === 'emola' ? 'emola' : 'mpesa';
        $phone = preg_replace('/^\+?258/', '', $phone);
        $phone = preg_replace('/\D/', '', $phone);
        $shortRef = $this->shortenReference($reference, 20);

        $token = $this->obterToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Falha ao autenticar com o gateway de pagamento.'];
        }

        $url = "{$this->baseUrl}/v1/c2b/{$tipo}-payment/" . ($tipo === 'mpesa' ? '993564' : '993565');
        $payAmount = $this->testMode ? 1 : $amount;

        $httpClient = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);

        if (app()->environment('local')) {
            $certPath = 'C:\wamp64\bin\php\php8.2.18\extras\ssl\cacert.pem';
            if (file_exists($certPath)) {
                $httpClient = $httpClient->withOptions(['verify' => $certPath, 'timeout' => 30]);
            }
        }

        Log::channel('payments')->info('payment.initiated', [
            'method' => $tipo,
            'amount' => $payAmount,
            'reference' => $shortRef,
        ]);

        $response = $httpClient->post($url, [
            'client_id' => $this->clientId,
            'amount' => $payAmount,
            'phone' => $phone,
            'reference' => $shortRef,
        ]);

        $data = $response->json() ?? [];

        if (!$response->successful()) {
            Log::channel('payments')->error('payment.gateway_error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Falha ao iniciar pagamento.',
                'data' => $data,
            ];
        }

        return [
            'success' => true,
            'message' => $data['message'] ?? 'Solicitação enviada. Confirme no telemóvel.',
            'data' => array_merge($data, [
                'transaction_id' => $data['transaction_id'] ?? $data['id'] ?? null,
                'payment_reference' => $data['reference'] ?? $shortRef,
                'gateway_reference' => $shortRef,
            ]),
        ];
    }

    private function obterToken(): ?string
    {
        try {
            $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (!$response->successful()) {
                return null;
            }

            return $response->json('access_token');
        } catch (\Throwable $e) {
            Log::channel('payments')->error('payment.token_error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function shortenReference(string $reference, int $maxLength = 20): string
    {
        if (strlen($reference) <= $maxLength && preg_match('/^[A-Z0-9]+$/i', $reference)) {
            return strtoupper($reference);
        }

        $simple = 'ORD' . date('YmdHis') . substr(md5($reference), 0, 4);

        return substr(strtoupper($simple), 0, $maxLength);
    }

    public static function isSuccessfulStatus(string $status): bool
    {
        return in_array(strtolower($status), ['paid', 'completed', 'success', 'successful'], true);
    }
}
