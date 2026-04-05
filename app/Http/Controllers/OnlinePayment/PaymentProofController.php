<?php

namespace App\Http\Controllers\OnlinePayment;

use App\Http\Controllers\Controller;
use App\Models\Subscription\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentProofController extends Controller
{
    /**
     * Display a listing of the payment proofs
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $perPage = $request->query('per_page', 15);
        
        $query = PaymentProof::query();
        
        // Filter by status if provided
        if (!empty($status) && in_array($status, ['pending_review', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }
        
        // Order by most recent
        $query->orderBy('created_at', 'desc');
        
        $proofs = $query->paginate($perPage);
        
        return response()->json($proofs);
    }
    
    /**
     * Display the specified payment proof
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $proof = PaymentProof::findOrFail($id);
        
        return response()->json($proof);
    }
    
    /**
     * Review a payment proof
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function review(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:500',
        ]);
        
        $proof = PaymentProof::findOrFail($id);
        
        // Only allow reviewing if current status is pending_review
        if ($proof->status !== 'pending_review') {
            return response()->json([
                'success' => false,
                'message' => 'This payment proof has already been reviewed',
            ], 422);
        }
        
        $proof->update([
            'status' => $request->status,
            'review_notes' => $request->review_notes,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);
        
        // Here you might want to trigger notifications or other actions
        // based on the review status
        
        return response()->json([
            'success' => true,
            'message' => 'Payment proof has been ' . $request->status,
            'data' => $proof
        ]);
    }
}