<?php

class PriorityQueue extends SplPriorityQueue {

    public function compare($priority1, $priority2) { 
        if ($priority1 === $priority2) return 0; 
        return $priority1 < $priority2 ? -1 : 1; 
    }

    public function toArray($limit = -1) {
        $array = [];

        $count = 0;
        foreach (clone $this as $item) {
            $array[] = $item;
            ++$count;
            if ($count == $limit) {
                break ;
            }
        }

        return $array;
    }
}