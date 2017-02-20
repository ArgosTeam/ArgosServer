<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    //

    public function registerManual(Requests\SubmitRegisterRequest $request){
        $data = $request->all();
        \Illuminate\Support\Facades\Log::info('Showing request registerManual : ' . json_encode($data));
        //Check if object exists
        $user = \App\Models\User::where('phone', $data["phone"])->first();

        if(is_object($user)){
            return (response()->json(["registered" => false]));
        }

        //Create User
        $user = new \App\Models\User();
        $user->email = $data["email"];
        $user->firstName = $data["firstname"];
        $user->lastName = $data["lastname"];
        $user->username = (array_key_exists("username",$data))? $data["username"] : "";
        $user->phone = $data["phone"];
        $user->sex = $data["sex"];
        $user->password = bcrypt($data["password"]);
        $user->save();

        return (response()->json(["registered" => true, "user_id" => $user->id]));
    }
}
