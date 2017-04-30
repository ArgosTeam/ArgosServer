<?php
namespace App\Classes;
use App\Models\User;
use App\Models\Category;
use App\Models\Event;

class CategoryFunctions
{

    /*
    ** Basic Operation on Category
    ** Private protected
    */
    private static function add($parent_id) {
        $parent = null;
        
        if ($parent_id) {
            $parent = Category::find($parent_id);
            if (!is_object($parent)) {
                return null;
            }
        }
        
        $category = new Category([
            'name' => $request->input('name'),
            'count' => 0
        ]);
        $category->save();

        if (is_object($parent)) {
            $parent->appendNode($category);
        }

        return $category;
    }

    private static function remove($category_id) {
        $category->delete();
    }

    /*
    ** Public methods for event Inventory
    */
    public static function addToEvent($user, $parent_id, $event_id) {
        $event = Event::find($event_id);
        if (is_object($event)) {
            $admin = $event->users()
                   ->where('admin', true)
                   ->where('users.id', $user->id)
                   ->first();
            if (is_object($admin)) {
                $category = CategoryFunctions::add($parent_id);

                /*
                ** Add only root Nodes to relationship
                */
                if ($category) {
                    if ($category->isRoot()) {
                        $event->categories()->attach($category->id);
                    }


                    return response(['status' => 'Category added successfully'], 200);
                }
                return  response(['status' => 'Category selected to add a '
                                  . 'sub-category does not exist'], 403);
            }

            return response(['status' => 'Access denied'], 403);
        }

        return response(['status' => 'Event does not exist'], 200);
    }

    public static function removeFromEvent($user, $event_id, $category_id) {
        $event = Event::find($event_id);

        if (is_object($event)) {
            $category = Category::find($category_id);

            $admin = $user->events()
                   ->where('events.id', $event->id)
                   ->where('admin', true)
                   ->first();

            if (is_object($admin)) {

                if (is_object($category)) {

                    /*
                    ** Getting root node to check if linked with event
                    */
                    $root = $category->isRoot()
                          ? $category
                          : $category->ancestors()
                          ->whereIsRoot()
                          ->first();
                    $belong = $root->event()
                            ->where('events.id', $event->id)
                            ->first();
                    if (is_object($belong)) {
                        $category->remove();
                        return response(['status' => 'Category removed', 200]);
                    }

                    return response(['status' => 'Category not linked to Event'], 403);
                }
                
                return response(['status' => 'Category does not exists'], 403);
            }
        
            return response(['status' => 'Access denied - not admin'], 403);
        }

        return response(['status' => 'Event does not exist'], 403);
    }
}