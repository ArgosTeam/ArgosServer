<?php
namespace App\Classes;
use App\Models\User;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Support\Facades\Log;

class CategoryFunctions
{

    /*
    ** Basic Operation on Category
    ** Private protected
    */
    private static function add($parent_id, $name) {
        $parent = null;
        
        if ($parent_id) {
            $parent = Category::find($parent_id);
            if (!is_object($parent)) {
                return null;
            }
        }
        
        $category = new Category([
            'name' => $name,
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
    public static function addToEvent($user, $parent_id, $event_id, $name) {
        $event = Event::find($event_id);
        if (is_object($event)) {
            $admin = $event->users()
                   ->where('admin', true)
                   ->where('users.id', $user->id)
                   ->first();
            if (is_object($admin)) {
                $category = CategoryFunctions::add($parent_id, $name);

                /*
                ** Add only root Nodes to relationship
                */
                if ($category) {
                    if ($category->isRoot()) {
                        $category->event()->associate($event->id);
                        $category->save();
                    }


                    return response(['id' => $category->id], 200);
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

    /*
    ** Users-Category handle
    */
    public static function updateUsersCategory($user, $event_id, $category_id, $count) {
        $event = Event::find($event_id);
        //Event exists ?
        if (is_object($event)) {

            $belong = $user->events()
                    ->where('events.id', $event->id)
                    ->where('status', 'accepted')
                    ->first();

            // Check if user in event
            if (is_object($belong)) {
                
                $category = Category::find($category_id);

                // Category exists ?
                if (is_object($category)) {
                    $userPivot = $category->users()
                               ->where('users.id', $user->id)
                               ->first();
                    /*
                    ** If user-category already exists, update entry
                    ** Else create new entry
                    ** Update global count in category object
                    */
                    if (is_object($userPivot)) {
                        if (($total = $userPivot->pivot->count + $count) >= 0) {
                            $category->users()->updateExistingPivot($user->id, [
                                'count' => $total
                            ]);
                            $category->count += $count;
                        }
                    } else {
                        $count = $count > 0 ? $count : 1;
                        $category->users()->attach($user->id, [
                            'count' => $count
                        ]);
                        $category->count += $count;
                    }
                    $category->save();
                    
                    return response(['status' => 'Success'], 200);
                }
                
                return response(['status' => 'Category does not exist'], 403);
            }
            
            return response(['status' => 'Not in event'], 403);
        }

        return response(['status' => 'Event does not exist'], 403);
    }

    public static function getInventory($user, $event_id) {
        $event = Event::find($event_id);
        if (is_object($event)) {
            $belong = $user->events()
                    ->where('events.id', $event->id)
                    ->where('status', 'accepted')
                    ->first();
            if (is_object($belong)) {
                $inventory = $event->inventory()
                           ->with('descendants')
                           ->get()
                           ->toTree();

                Log::info('DEBUG TREE INVENTORY : ' . print_r($inventory, true));
                return response($inventory->toJson(), 200);
            }

            return response(['status' => 'Not in event'], 403);
        }

        return response(['status' => 'Event does not exists'], 403);
    }
}