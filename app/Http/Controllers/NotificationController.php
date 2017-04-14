<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Classes\PhotoFunctions;

class NotificationController extends Controller
{
    public function getNotifications(Request $request) {
        $user = Auth::user();

        $response = [];
        $notifications = $user->unreadNotifications()
                       ->get();

        $response = [];
        foreach ($notifications as $notification) {
            $userFrom = User::find($notification->data['user_id']);
            

            $profile_pic_path = null;
            $profile_pic = $userFrom->profile_pic()->first();
            if (is_object($profile_pic)) {
                $request = PhotoFunctions::getUrl($profile_pic, 'regular');
                $profile_pic_path = '' . $request->getUri() . '';
            }
            if (!array_key_exists((string)$userFrom->id, $response)) {
                $response[(string)$userFrom->id] = [
                    'id' => $userFrom->id,
                    'nickname' => $userFrom->nickname,
                    'profile_pic' => $profile_pic_path,
                    'notifs' => []
                ];
            }

            $type = str_replace('App\Notifications', '', $notification->type);
            $response[(string)$userFrom->id]['notifs'][] = [
                'notif_id' => $notification->id,
                'type' => $type,
                'data' => $notification->data,
                'time' => $notification->created_at
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

    public function count(Request $request) {
        $user = Auth::user();
        $notificationsCount = $user->unreadNotifications()
                            ->get()
                            ->count();

        return response([
            'count' => $notificationsCount
        ], 200);
    }
}
