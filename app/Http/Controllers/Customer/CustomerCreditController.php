<?php



namespace App\Http\Controllers\Customer;



use App\Enums\ProjectPaymentType;

use App\Http\Controllers\Controller;

use App\Models\Credits\CreditPackage;

use App\Models\Construction\ProjectPayment;

use App\Services\Credits\CreditService;

use App\Services\Payments\PaymentGatewayService;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Str;



class CustomerCreditController extends Controller

{

    public function balance(Request $request, CreditService $credits): JsonResponse

    {

        return response()->json(['data' => ['balance' => $credits->getBalance($request->user())]]);

    }



    public function history(Request $request, CreditService $credits): JsonResponse

    {

        return response()->json(['data' => $credits->getHistory($request->user())]);

    }



    public function packages(): JsonResponse

    {

        return response()->json(['data' => CreditPackage::active()->get()]);

    }



    public function purchase(Request $request, int $packageId, PaymentGatewayService $gateway): JsonResponse

    {

        $validated = $request->validate([

            'phone_number' => 'required|string|max:30',

            'payment_method' => 'required|in:mpesa,emola',

        ]);



        $package = CreditPackage::active()->findOrFail($packageId);

        $reference = 'CR-' . Str::upper(Str::random(10));



        $payment = ProjectPayment::create([

            'user_id' => $request->user()->id,

            'type' => ProjectPaymentType::CreditsPurchase,

            'credit_package_id' => $package->id,

            'provider' => $validated['payment_method'],

            'reference' => $reference,

            'phone_number' => $validated['phone_number'],

            'amount' => $package->price_mt,

            'currency' => 'MZN',

            'status' => 'pending',

            'metadata' => ['package_id' => $package->id],

        ]);



        $result = $gateway->initiateC2b(

            $validated['payment_method'],

            (float) $package->price_mt,

            $validated['phone_number'],

            $reference

        );



        if (!($result['success'] ?? false)) {

            return response()->json([

                'message' => $result['message'] ?? 'Falha ao iniciar pagamento.',

                'data' => $payment,

            ], 502);

        }



        $gatewayRef = $result['data']['gateway_reference'] ?? $result['data']['payment_reference'] ?? $reference;

        $payment->update([

            'provider_reference' => $gatewayRef,

            'transaction_id' => $result['data']['transaction_id'] ?? null,

            'metadata' => array_merge($payment->metadata ?? [], ['gateway' => $result['data']]),

        ]);



        return response()->json([

            'data' => $payment->fresh(),

            'gateway' => $result,

            'message' => $result['message'],

        ], 201);

    }

}

