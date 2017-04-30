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
    public function addToEvent(Request $request) {
        $user = Auth::user();
        $parent_id = $request->input('parent_id');
        $event_id = $request->input('event_id');
        return CategoryFunctions::addToEvent($user, $parent_id, $event_id);
    }

    public function removeFromEvent(Request $request) {
        $user = Auth::user();
        $category_id = $request->input('id');
        return CategoryFunctions::removeFromEvent($user, $event_id, $category_id);
    }
}