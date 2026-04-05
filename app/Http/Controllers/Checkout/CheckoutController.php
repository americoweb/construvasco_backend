<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Services\Checkout\CheckoutService;
use App\Http\Requests\Checkout\ProcessCheckoutRequest;
use App\Http\Resources\Checkout\CheckoutSummaryResource;
use App\Http\Resources\Checkout\CheckoutResultResource;
use Illuminate\Http\JsonResponse;
use Exception;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService
    ) {}

    public function getSummary(string $cartUuid): JsonResponse
    {
        try {
            $summary = $this->checkoutService->getCheckoutSummary($cartUuid);
            
            return response()->json([
                'data' => new CheckoutSummaryResource($summary)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function process(ProcessCheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->checkoutService->processCheckout($request->validated());
            
            return response()->json([
                'data' => new CheckoutResultResource($order),
                'message' => 'Pedido criado com sucesso!'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'CHECKOUT_FAILED'
            ], 422);
        }
    }

    public function validateData(): JsonResponse
    {
        $data = request()->all();
        $errors = $this->checkoutService->validateCheckoutData($data);
        
        if (!empty($errors)) {
            return response()->json([
                'valid' => false,
                'errors' => $errors
            ], 422);
        }
        
        return response()->json([
            'valid' => true,
            'message' => 'Dados válidos'
        ]);
    }

    public function getShippingOptions(string $cartUuid): JsonResponse
    {
        try {
            $summary = $this->checkoutService->getCheckoutSummary($cartUuid);
            
            return response()->json([
                'data' => $summary['shipping_options']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
