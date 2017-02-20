<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use IlluminateSupport\Facades\\Notification;

class NotificationController extends Controller
{
    public function getNotifications(Request $request) {
        $user = Auth::user();

        $response = [];
        foreach ($user->unreadNotifications as $notification) {
            $response[] = [
                'notification_id' => $notification->id,
                'notification_type' => $notification->type,
                'data' => $notification->data
            ];
        }

        return response($response, 200);
    }

    public function markAsRead(Request $request) {
        $user = Auth::user();
        $notification_id = $request->input('notification_id');
        if ($user->unreadNotifications->contains($notification_id)) {
            $notification = Notification::find($notification_id);
            $notification->markAsRead();
            return response(['status' => 'success'], 200);
        }
        return response(['status' => 'No such notification'], 404);
    }
}
