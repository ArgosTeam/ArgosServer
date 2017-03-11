<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class NotificationController extends Controller
{
    public function getNotifications(Request $request) {
        $user = Auth::user();

        $response = [];
        $notifications = $user->unreadNotifications();
        $types = $request->input('types');
        foreach ($types as $type) {
            $notifications->orWhere('type', 'like', '%' . $type);
        }
        $notifications = $notifications->get();
        foreach ($notifications as $notification) {
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
            if ($user->unreadNotifications->contains($notification->id)) {
                $notification->markAsRead();
            }
        }
        return response(['status' => 'success'], 200);
    }
}
