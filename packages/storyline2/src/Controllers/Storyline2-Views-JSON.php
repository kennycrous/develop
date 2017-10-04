<?php

/**
 * Created by PhpStorm.
 * User: jharing10
 * Date: 2017/01/19
 * Time: 9:21 AM
 */

namespace EONConsulting\Storyline2\Controllers;

use Illuminate\Routing\Controller as BaseController;
use EONConsulting\Storyline2\Models\Course;
use EONConsulting\Storyline2\Models\Storyline;
use EONConsulting\Storyline2\Models\StorylineItem;
use Symfony\Component\HttpFoundation\Request;
use EONConsulting\ContentBuilder\Models\Category;
use EONConsulting\ContentBuilder\Models\Content;
use EONConsulting\ContentBuilder\Controllers\ContentBuilderCore as ContentBuilder;

class Storyline2ViewsJSON extends BaseController {

    /**
     *
     * @param Course $course
     * @return type
     */
    public function render() {

        /*
          $var = $course::find(20);
          $storyline = $var->latest_storyline();
          $items = $var['items'];
         */

        $storyline = Storyline::find(47);

        $items = $storyline['items'];

        return $this->items_to_tree($items);
    }

    /**
     * Undocumented function
     *
     * @param [type] $storyline
     * @return void
     */
    public function show_items($storyline) {

        $result = Storyline::find($storyline)->items;

        return response()->json($this->nest($this->items_to_tree($result)));
    }
    

    public function nest($items) { 
        $new = array(); 

        //list assigns values to $id, and $item
        //each returns a key value pair from the array $items, and steps to the next item
        while(list($id, $item) = each($items)) { 
            
            $temp = $item;
             
            //if this is true, item has children
            if($item['rgt'] - $item['lft'] != 1) { 
                $temp['children'] = $this->nest($items, true); 
            } 

            $new[] = $temp;

            //key() returns the position of the current internal counter
            $next_id = key($items);

            //check if next child is a sibling, if not, return new
            if($next_id && $items[$next_id]['parent_id'] != $item['parent_id']) { 
                usort($new,'self::compare');
                return $new;
            }
        }

        usort($new, array($this, "self::compare"));
        return $new;
    }  


    public function compare($a,$b){
        if($a['lft'] == $b['lft']){return 0;}
        return ($a['lft'] < $b['lft']) ? -1 : 1;
    }


    /**
     *
     * @param type $items
     * @return type
     */
    public function items_to_tree($items) {

        $map = [];

        foreach ($items as $k => $node) {

            $map[$k] = [
                'id' => (string) $node['id'],
                'text' => $node['name'],
                'parent_id' => ($node['parent_id'] === null) ? "#" : $node['parent_id'],
                'rgt' => $node['_rgt'],
                'lft' => $node['_lft']
            ];
        }

        return $map;
    }


    /**
     * 
     * @param Request $request
     * @return type
     */
    public function rename(Request $request) {

        $data = $request->json()->all();

        $ItemId = (int) $data['id'];
        $text = $data['text'];

        $Item = StorylineItem::find($ItemId);
        $Item->name = $text;

        if ($Item->save()) {
            $msg = 'success2';
        } else {
            $msg = 'failed';
        }

        return response()->json(['msg' => $msg]);
    }

    public function create(Request $request) {

        $data = $request->json()->all();

        $parent_id = (int) $data['parent'];
        $text = $data['original']['text'];

        $root_i = count($data['parents']) - 1;

        $root_parent = (int) $data['parents'][$root_i];
        //dd($text);

        $Item = StorylineItem::where('id', '=', $parent_id)->first();

        $newItem = StorylineItem::create(['name' => $text, 'storyline_id' => $Item->storyline_id, 'parent_id' => $parent_id, 'root_parent' => $root_parent]);

        if ($Item->moveToLeftOf($newItem)) {
            $msg = 'success';
        } else {
            $msg = 'failed';
        }

        return response()->json(['msg' => $msg, 'id' => $newItem->id]);
    }

    private function findPosition($decendants, $position) {
        $i = 0;
        foreach ($decendants as $decendant) {
            if ($i == $position) {
                return $decendant;
            }
            $i++;
        }
    }

    /**
     * 
     * @param Request $request
     * @return type
     */
    public function move(Request $request) {

        $data = $request->json()->all();
        $parentId = (int) $data['node']['parent'];
        $itemId = (int) $data['node']['id'];
        $position = (int) $data['position'];
        $old_position = (int) $data['old_position'];
        $parent = StorylineItem::find($parentId);
        $decendants = $parent->getImmediateDescendants();
        $num_children = count($decendants);
        $node = StorylineItem::find($itemId);

        if($num_children === 0 || $position === 0){
            $msg = $node->makeFirstChildOf($parent) ? "Made First Child of Parent" : "Failure";
        } else {

            if($position === $num_children-1){
                $msg = $node->makeLastChildOf($parent) ? "Made Last Child of Parent" : "Failure";
            }else {
                $msg = $node->makeLastChildOf($parent);

                $decendants = $parent->getImmediateDescendants();
                $msg = $node->moveToLeftOf($decendants[$position]) ? "Moved To Position ".$position : "Failure";

            }
        }
        
        return response()->json(['msg' => $msg]);
    }

    /**
     * 
     * @param Request $request
     */
    public function delete(Request $request) {

        $data = $request->json()->all();

        $ItemId = (int) $data[0];

        $Item = StorylineItem::where('id', '=', $ItemId)->first();
        if ($Item->delete()) {
            $msg = 'success2';
        } else {
            $msg = 'failed';
        }
        return response()->json(['msg' => $msg]);
    }

}
