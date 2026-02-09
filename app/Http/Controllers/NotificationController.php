<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markRead(Request $request)
    {
        $id = $request->input('id');

        if (!$id) {
            return response()->json(['status' => 'missing_id'], 422);
        }

        // ✅ Auth::user() returns the full User model, so unreadNotifications() works
        $notification = Auth::user()->unreadNotifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'not_found'], 404);
    }
}
