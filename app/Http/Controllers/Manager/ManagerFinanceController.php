<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\Quote;
use Illuminate\Http\JsonResponse;

class ManagerFinanceController extends Controller
{
    public function overview(): JsonResponse
    {
        $paidQuery = ProjectPayment::where('status', 'paid');

        $recentQuotes = Quote::with(['projectRequest:id,reference_code,title,user_id', 'projectRequest.user:id,name'])
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (Quote $q) => [
                'id' => $q->id,
                'total_amount_mt' => $q->total_amount_mt,
                'status' => $q->status?->value ?? (string) $q->status,
                'sent_at' => $q->sent_at?->toIso8601String(),
                'delivery_days' => $q->delivery_days,
                'reference_code' => $q->projectRequest?->reference_code,
                'request_title' => $q->projectRequest?->title,
                'client_name' => $q->projectRequest?->user?->name,
            ]);

        $recentPayments = ProjectPayment::with(['project:id,name', 'user:id,name'])
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (ProjectPayment $p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'currency' => $p->currency ?? 'MZN',
                'status' => $p->status,
                'type' => $p->type?->value ?? (string) $p->type,
                'paid_at' => $p->paid_at?->toIso8601String(),
                'project_name' => $p->project?->name,
                'client_name' => $p->user?->name,
            ]);

        return response()->json([
            'data' => [
                'payments_total_mt' => (float) $paidQuery->sum('amount'),
                'payments_count' => $paidQuery->count(),
                'quotes_sent_count' => Quote::where('status', 'sent')->count(),
                'quotes_accepted_count' => Quote::where('status', 'accepted')->count(),
                'quotes_total_sent_mt' => (float) Quote::whereIn('status', ['sent', 'accepted'])->sum('total_amount_mt'),
                'recent_quotes' => $recentQuotes,
                'recent_payments' => $recentPayments,
            ],
        ]);
    }
}
