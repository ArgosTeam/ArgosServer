<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function registerManual(Requests\SubmitRegisterRequest $request){
        $data = $request->all();
        //Check if object exists
        $userCheck = User::where('nickname', '=', $data['nickname'])->first();
        $phoneCheck = User::where('phone', '=', $data['phone'])->first();

        if (is_object($userCheck)) {
            return response(['status' => 'Nickname already exists'], 403);
        }
        if (is_object($phoneCheck)) {
            return response(['status' => 'Phone number already used'], 403);
        }

        //Create User
        $user = new User();
        $user->nickname = $data['nickname'];
        $user->phone = $data['phone'];
        $user->sex = $data['sex'];
        $user->password = bcrypt($data['password']);
        $user->dob = $data['dob'];
        $user->save();

        return (response()->json(['registered' => true, 'user_id' => $user->id]));
    }
}
