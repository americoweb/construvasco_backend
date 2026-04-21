<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Order\OrderStatus;
use App\Enums\JobCard\JobCardStatus;
use App\Http\Controllers\Controller;
use App\Models\Design\Design;
use App\Models\Order\Order;
use App\Models\JobCard\JobCard;
use App\Models\Product\Category;
use App\Models\Product\Product;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'orders_total' => Order::count(),
                'orders_pending' => Order::where('status', OrderStatus::PENDING)->count(),
                'orders_active' => Order::whereIn('status', [
                    OrderStatus::CONFIRMED,
                    OrderStatus::IN_PRODUCTION,
                    OrderStatus::SHIPPED,
                ])->count(),
                'products_total'    => Product::count(),
                'designs_total'     => Design::count(),
                'categories_total'  => Category::count(),
                // Job Cards
                'job_cards_total'      => JobCard::count(),
                'job_cards_active'     => JobCard::whereNotIn('status', [JobCardStatus::DONE, JobCardStatus::CANCELLED])->count(),
                'job_cards_urgent'     => JobCard::where('priority_override', true)
                                                  ->whereNotIn('status', [JobCardStatus::DONE, JobCardStatus::CANCELLED])
                                                  ->count(),
                'job_cards_overdue'    => JobCard::whereNotIn('status', [JobCardStatus::DONE, JobCardStatus::CANCELLED])
                                                  ->where('deadline', '<', now())
                                                  ->count(),
                'job_cards_in_design'  => JobCard::where('status', JobCardStatus::DESIGN)->count(),
                'job_cards_in_approval'=> JobCard::where('status', JobCardStatus::APPROVAL)->count(),
            ],
        ]);
    }
}
