<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Notifications\Notification;

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
        $notifications_id = $request->input('notifications_id');
        $notifications = $user->notifications()
                      ->whereIn('notifications.id', $notifications_id)
                      ->get();

        foreach ($notifications as $notification) {
            Log::info(print_r($notification, true));
            if ($user->unreadNotifications->contains($notification->id)) {
                $notification->markAsRead();
            }
        }
        return response(['status' => 'success'], 200);
    }
}
