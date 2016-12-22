<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

use App\Http\Requests;

class AuthController extends Controller
{
    //

    public function registerManual(Requests\SubmitRegisterRequest $request){

        $data = $request->all();

        //Check if object exists
        $user = \App\Models\User::where('email', $data["email"])->orWhere('phone', $data["phone"])->first();

        if(is_object($user)){
            return (["registered" => false]);
        }


        //Create User
        $user = new \App\Models\User();
        $user->email = $data["phone"];
        $user->firstName = $data["firstname"];
        $user->lastName = $data["lastname"];
        $user->username = (array_key_exists("username",$data))? $data["username"] : "";
        $user->phone = $data["phone"];
        $user->sex = $data["sex"];
        $user->password = bcrypt($data["password"]);
        $user->save();

        return (["registered" => true, "user_id" => $user->id]);

    }
}
