<?php

namespace App\Http\Controllers\OnlinePayment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class OnlinePaymentController extends Controller
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private bool $testMode;

    public function __construct()
    {
        $this->baseUrl = (string) env('PAYMENT_BASE_URL', 'https://e2payments.explicador.co.mz');
        $this->clientId = (string) env('PAYMENT_CLIENT_ID', '');
        $this->clientSecret = (string) env('PAYMENT_CLIENT_SECRET', '');
        $this->testMode = (bool) env('PAYMENT_TEST_MODE', true);
    }

    // Obtains the token for authentication
    private function obterToken()
    {
        try {
            $httpClient = Http::asForm();

            // Configure SSL certificate for local development (WAMP/Windows)
            // Only use local cert path if APP_ENV is 'local', not in production
            $appEnv = env('APP_ENV', app()->environment());
            if ($appEnv === 'local' && app()->environment('local')) {
                $certPath = 'C:\wamp64\bin\php\php8.2.18\extras\ssl\cacert.pem';
                if (file_exists($certPath)) {
                    $httpClient->withOptions([
                        'verify' => $certPath,
                    ]);
                }
            }
            // In production, Laravel's Http client will use system's default CA bundle automatically

            $response = $httpClient->post("{$this->baseUrl}/oauth/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            $responseBody = $response->body();
            $contentType = $response->header('Content-Type');
            
            Log::info("OAuth token request", [
                'status' => $response->status(),
                'content_type' => $contentType,
                'body_preview' => substr($responseBody, 0, 200)
            ]);

            if (!$response->successful()) {
                Log::error("Failed to obtain OAuth token", [
                    'status' => $response->status(),
                    'content_type' => $contentType,
                    'body' => $responseBody
                ]);
                return null;
            }

            // Check if response is HTML (means we got redirected to landing page)
            if (str_contains($contentType, 'text/html') || str_starts_with(trim($responseBody), '<!DOCTYPE') || str_starts_with(trim($responseBody), '<html')) {
                Log::error("OAuth endpoint returned HTML instead of JSON - authentication failed", [
                    'status' => $response->status(),
                    'content_type' => $contentType,
                    'body_preview' => substr($responseBody, 0, 500)
                ]);
                return null;
            }

            $token = $response->json('access_token');
            
            if (!$token) {
                Log::error("OAuth token not found in response", [
                    'response' => $response->json(),
                    'body' => $responseBody
                ]);
            }

            return $token;
        } catch (\Exception $e) {
            Log::error("Exception obtaining OAuth token", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function makePayment(Request $request, $tipo)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'phone' => 'required|string',
            'reference' => 'nullable|string'
        ]);

        // Format phone number for payment gateway
        // Remove +258 prefix if present, keep only the 9 digits (84/85/86/87 + 7 digits)
        $phone = $request->phone;
        $phone = preg_replace('/^\+?258/', '', $phone); // Remove +258 or 258 prefix
        $phone = preg_replace('/\D/', '', $phone); // Remove any non-digits
        
        // Generate a valid reference for e2Payments/M-Pesa API
        // M-Pesa requires alphanumeric references, max 20 characters (e2Payments may add prefix)
        $originalReference = $request->reference ?? 'RefPadrao';
        $shortenedReference = $this->shortenReference($originalReference, 20);
        
        // Log the payment request for debugging
        Log::info("Payment request initiated", [
            'type' => $tipo,
            'amount' => $request->amount,
            'phone_original' => $request->phone,
            'phone_formatted' => $phone,
            'reference_original' => $originalReference,
            'reference_shortened' => $shortenedReference,
        ]);

        try {
            $token = $this->obterToken();
            
            if (!$token) {
                Log::error("Failed to obtain payment gateway token");
                return response()->json([
                    'success' => false,
                    'message' => 'Falha ao autenticar com o gateway de pagamento. Por favor, tente novamente.',
                    'errors' => [['message' => 'Token de autenticação não obtido']]
                ], 500);
            }

            $url = "{$this->baseUrl}/v1/c2b/{$tipo}-payment/" . ($tipo === 'mpesa' ? '993564' : '993565');

            // Use withHeaders to explicitly set Authorization header (some APIs require this)
            $httpClient = Http::asForm()->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ]);

            // Configure SSL certificate and timeout for local development (WAMP/Windows)
            $options = [
                'timeout' => 30, // 30 seconds timeout
            ];
            
            // Only use local cert path if APP_ENV is 'local', not in production
            $appEnv = env('APP_ENV', app()->environment());
            if ($appEnv === 'local' && app()->environment('local')) {
                $certPath = 'C:\wamp64\bin\php\php8.2.18\extras\ssl\cacert.pem';
                if (file_exists($certPath)) {
                    $options['verify'] = $certPath;
                }
            }
            // In production, Laravel's Http client will use system's default CA bundle automatically
            
            $httpClient->withOptions($options);

            // Use test amount (1 MT) if test mode is enabled, otherwise use actual amount
            $amount = $this->testMode ? 1 : $request->amount;
            
            if ($this->testMode) {
                Log::info("Payment test mode enabled - using 1 MT instead of actual amount", [
                    'original_amount' => $request->amount,
                    'test_amount' => $amount
                ]);
            }
            
            $paymentPayload = [
                'client_id' => $this->clientId,
                'amount' => $amount,
                'phone' => $phone, // Use formatted phone number
                'reference' => $shortenedReference, // Use shortened reference (max 27 chars)
            ];

            // Log request details before sending
            Log::info("Sending payment request to gateway", [
                'url' => $url,
                'payload' => array_merge($paymentPayload, ['phone' => $phone]), // Log without exposing full phone
                'token_preview' => $token ? substr($token, 0, 20) . '...' : 'null',
                'test_mode' => $this->testMode,
                'original_amount' => $this->testMode ? $request->amount : null,
                'headers' => [
                    'Authorization' => 'Bearer ' . ($token ? substr($token, 0, 20) . '...' : 'null'),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            $response = $httpClient->post($url, $paymentPayload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $contentType = $response->header('Content-Type');
            $responseData = $response->json() ?? [];

            // Check if we got HTML instead of JSON (API endpoint not found or auth failed)
            if (str_contains($contentType, 'text/html') || str_starts_with(trim($responseBody), '<!DOCTYPE') || str_starts_with(trim($responseBody), '<html')) {
                Log::error("Payment gateway returned HTML instead of JSON - endpoint may be wrong or authentication failed", [
                    'url' => $url,
                    'status' => $statusCode,
                    'content_type' => $contentType,
                    'body_preview' => substr($responseBody, 0, 500),
                    'token_obtained' => $token ? 'yes' : 'no'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao comunicar com o gateway de pagamento. Por favor, verifique as credenciais da API.',
                    'errors' => [['message' => 'O gateway retornou uma resposta inválida. Verifique as credenciais e o endpoint da API.']]
                ], 500);
            }

            Log::info("Payment gateway response", [
                'status' => $statusCode,
                'content_type' => $contentType,
                'response_body' => $responseBody,
                'response_data' => $responseData
            ]);

            // Check if the payment gateway returned an error
            if (!$response->successful()) {
                Log::error("Payment gateway error", [
                    'status' => $statusCode,
                    'response' => $responseData,
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Falha ao processar pagamento. Por favor, verifique os dados e tente novamente.',
                    'errors' => $responseData['errors'] ?? [['message' => 'Erro no gateway de pagamento']],
                    'data' => $responseData
                ], $statusCode);
            }

            // Extract transaction information from gateway response
            // Try multiple possible field names that e2Payments might use
            $transactionId = $responseData['transaction_id'] 
                ?? $responseData['transactionId'] 
                ?? $responseData['request_id'] 
                ?? $responseData['id']
                ?? $responseData['payment_id']
                ?? null;
            
            $gatewayReference = $responseData['reference'] 
                ?? $responseData['merchant_reference'] 
                ?? $responseData['order_reference']
                ?? $request->reference 
                ?? null;

            // If response is empty but status is 200, payment might still be initiated
            // Log warning but return success (gateway might send webhook later)
            if (empty($responseData) && $statusCode === 200) {
                Log::warning("Payment gateway returned empty response but status 200", [
                    'url' => $url,
                    'response_body' => $responseBody,
                    'status' => $statusCode
                ]);
            }

            // Return the gateway response with transaction details
            // Include original gateway response plus our extracted fields
            $responsePayload = array_merge($responseData, [
                'transaction_id' => $transactionId,
                'payment_reference' => $gatewayReference ?? $shortenedReference,
                'payment_method' => $tipo,
                'original_reference' => $originalReference // Include original for tracking
            ]);

            return response()->json([
                'success' => true,
                'message' => $responseData['message'] ?? 'Solicitação de pagamento enviada. Por favor, verifique o seu telefone e insira o PIN M-Pesa.',
                'data' => $responsePayload
            ], $statusCode);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Handle timeout errors gracefully
            if (str_contains($errorMessage, 'cURL error 28') || str_contains($errorMessage, 'Operation timed out')) {
                Log::warning("Payment gateway timeout", [
                    'error' => $errorMessage,
                    'url' => $url ?? 'unknown'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'O gateway de pagamento demorou muito para responder. Por favor, verifique o seu telefone - o pagamento pode ter sido processado. Se não recebeu nenhuma notificação, tente novamente.',
                    'errors' => [['message' => 'Timeout ao comunicar com o gateway de pagamento']]
                ], 504); // 504 Gateway Timeout
            }
            
            // Handle connection errors
            if (str_contains($errorMessage, 'cURL error') || str_contains($errorMessage, 'Connection')) {
                Log::error("Payment gateway connection error", [
                    'error' => $errorMessage
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de conexão com o gateway de pagamento. Por favor, verifique sua conexão com a internet e tente novamente.',
                    'errors' => [['message' => 'Erro de conexão']]
                ], 503); // 503 Service Unavailable
            }
            
            // Generic error handling
            Log::error("Payment processing exception", [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento. Por favor, tente novamente.',
                'errors' => [['message' => 'Erro ao processar pagamento']]
            ], 500);
        }
    }

    /**
     * Generate a valid reference for e2Payments/M-Pesa API
     * M-Pesa requires alphanumeric references, max 20 characters (e2Payments adds prefix)
     * Format: ORD + timestamp + hash (e.g., ORD20251119133517A1B2)
     */
    private function shortenReference(string $reference, int $maxLength = 20): string
    {
        // If it's already short and simple, use it
        if (strlen($reference) <= $maxLength && preg_match('/^[A-Z0-9]+$/i', $reference)) {
            return strtoupper($reference);
        }
        
        // Generate a simpler reference format that M-Pesa will accept
        // Use format: ORD + date/time + short hash from original reference
        $timestamp = date('YmdHis'); // 20251119133517 (14 chars)
        $hash = substr(md5($reference), 0, 6); // 6 char hash
        
        // If timestamp + hash is too long, use just timestamp
        $simpleRef = 'ORD' . $timestamp . $hash; // ORD + 14 + 6 = 23 chars
        
        if (strlen($simpleRef) > $maxLength) {
            // Use just ORD + timestamp (17 chars)
            $simpleRef = 'ORD' . $timestamp;
        }
        
        // Ensure it's exactly maxLength or less
        return substr($simpleRef, 0, $maxLength);
    }

    public function payWithEmola(Request $request)
    {
        return $this->makePayment($request, 'emola');
    }

    public function payWithMpesa(Request $request)
    {
        return $this->makePayment($request, 'mpesa');
    }

    /**
     * Handle payment via file upload (payment proof)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function payWithProofUpload(Request $request)
    {
        // Validate the request
        $request->validate([
            'amount' => 'required|numeric',
            'reference' => 'nullable|string',
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
            'bank_account' => 'nullable|string',
            'transfer_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);
        
        // Generate a unique filename
        $filename = Str::uuid() . '.' . $request->file('payment_proof')->getClientOriginalExtension();
        
        // Store the file in a dedicated 'payment_proofs' directory
        $path = $request->file('payment_proof')->storeAs(
            'payment_proofs', 
            $filename, 
            'public' // Using the public disk - make sure it's configured in filesystems.php
        );
        
        // Here you might want to store the payment information in your database
        // For example:
        PaymentProof::create([
             'amount' => $request->amount,
             'reference' => $request->reference ?? 'Manual Payment',
             'file_path' => $path,
             'bank_account' => $request->bank_account,
             'transfer_date' => $request->transfer_date,
             'notes' => $request->notes,
             'status' => 'pending_review', // Initial status
         ]);
        
        // Return a response
        return response()->json([
            'success' => true,
            'message' => 'Payment proof uploaded successfully and pending review',
            'data' => [
                'reference' => $request->reference ?? 'Manual Payment',
                'amount' => $request->amount,
                'file_path' => Storage::url($path),
                'upload_date' => now()->toDateTimeString(),
                'status' => 'pending_review'
            ]
        ], 200);
    }
}
