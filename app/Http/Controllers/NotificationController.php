<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Notifications\Notification;

class NotificationController extends Controller
{
    public function getNotifications(Request $request) {
        $user = Auth::user();

        $response = [];
        foreach ($user->unreadNotifications a $notification) {
            $response[] = [
                'notification_id' => $notification->id,
                'notification_type' => $notification->type,
                'data' => $notification->data
            ];
        }

        return response($response, 200);
    }
}
