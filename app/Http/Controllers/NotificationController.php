<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = AppNotification::where('user_id', $request->user()->id)
            ->recent()
            ->paginate((int) $request->get('per_page', 20));

        return response()->json($items);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::where('user_id', $request->user()->id)->unread()->count();

        return response()->json(['data' => ['count' => $count]]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $n = AppNotification::where('user_id', $request->user()->id)->findOrFail($id);
        $n->markAsRead();

        return response()->json(['data' => $n]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)->unread()->update(['read_at' => now()]);

        return response()->json(['message' => 'Todas marcadas como lidas.']);
    }
}
