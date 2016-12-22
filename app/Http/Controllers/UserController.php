<?php

namespace App\Http\Controllers;

use App\Classes\UserFunctions;
use Illuminate\Http\Request;

use App\Http\Requests;

class UserController extends Controller
{
    //
    public function profileRequests($userId){

        $func = new UserFunctions;
        return $func->fetch($userId);

    }

    public function setEmailRequests(){

        $func = new UserFunctions;
        return $func->setEmail();

    }
}
