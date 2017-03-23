<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Models\User;

class ToolsController extends Controller
{

    public static function checkNickname(Request $request) {
        $nickname = $request->input('nickname');
        $user = User::where('nickname', $nickname)
              ->first();
        if (is_object($user)) {
            return response(['available' => false], 200);
        }
        return response(['available' => true], 200);
    }
    
}