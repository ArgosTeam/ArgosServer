<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Models\Category;
use App\Classes\CategoryFunctions;
use App\Models\User;

class CategoryController extends Controller
{

    /*
    ** Admin
    */
    public function addToEvent(Request $request) {
        $user = Auth::user();
        $parent_id = $request->input('parent_id');
        $event_id = $request->input('event_id');
        $name = $request->input('name');
        return CategoryFunctions::addToEvent($user, $parent_id, $event_id, $name);
    }

    public function removeFromEvent(Request $request) {
        $user = Auth::user();
        $category_id = $request->input('id');
        return CategoryFunctions::removeFromEvent($user, $event_id, $category_id);
    }

    /*
    ** Non-admin
    */
    public function updateUsersCategory(Request $request) {
        $event_id = $request->input('event_id');
        $category_id = $request->input('category_id');
        $count = $request->input('count');
        $user = Auth::user();
        return CategoryFunctions::updateUsersCategory($user, $event_id, $category_id, $count);
    }

    
    
}