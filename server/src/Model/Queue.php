<?php

namespace App\Model;


class Queue
{
    private $items = [];

    public function enqueue($item)
    {
        if(in_array($item, $this->items, true)) {
            return;
        }

        $this->items[] = $item;
    }

    public function dequeue()
    {
        return array_shift($this->items);
    }

    public function peek()
    {
        return count($this->items) ? $this->items[0] : null;
    }
}
