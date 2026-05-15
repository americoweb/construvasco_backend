<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credits\CreditPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCreditPackageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => CreditPackage::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $pkg = CreditPackage::create($request->validate([
            'name' => 'required|string|max:255',
            'credits_amount' => 'required|integer|min:1',
            'price_mt' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]));

        return response()->json(['data' => $pkg], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pkg = CreditPackage::findOrFail($id);
        $pkg->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'credits_amount' => 'sometimes|integer|min:1',
            'price_mt' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]));

        return response()->json(['data' => $pkg->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        CreditPackage::findOrFail($id)->delete();

        return response()->json(['message' => 'Removido.']);
    }
}
