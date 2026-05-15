<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Construction\Quote;
use App\Services\Construction\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerQuoteController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $quote = Quote::with('projectRequest')
            ->whereHas('projectRequest', fn ($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($id);

        return response()->json(['data' => $quote]);
    }

    public function accept(Request $request, int $id, QuoteService $quotes): JsonResponse
    {
        $quote = Quote::with('projectRequest')->findOrFail($id);
        $project = $quotes->accept($quote, $request->user());

        return response()->json(['data' => ['quote' => $quote->fresh(), 'project' => $project]]);
    }

    public function reject(Request $request, int $id, QuoteService $quotes): JsonResponse
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:500']);
        $quote = Quote::findOrFail($id);
        $quote = $quotes->reject($quote, $request->user(), $validated['reason'] ?? null);

        return response()->json(['data' => $quote]);
    }
}
