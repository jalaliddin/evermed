<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->when($request->is_read !== null, fn($q) => $q->where('is_read', $request->boolean('is_read')))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($notifications);
    }

    public function markRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return response()->json($notification);
    }

    public function readAll()
    {
        Notification::where('is_read', false)->update(['is_read' => true]);
        return response()->json(['message' => 'All marked as read']);
    }

    public function unreadCount()
    {
        return response()->json(['count' => Notification::where('is_read', false)->count()]);
    }
}
