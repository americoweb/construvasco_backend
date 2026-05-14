<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectPortfolioItem;
use Illuminate\Http\JsonResponse;

class PortfolioProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $items = ProjectPortfolioItem::query()
            ->where('is_public', true)
            ->latest('published_at')
            ->get();

        return response()->json(['data' => $items]);
    }
}
