<?php

namespace App\Model;

class Page
{
    public $url;
    public $title;
    public $keywords;
    public $description;

    public function __construct($url,$title,$keywords,$description)
    {
        $this->url = $url;
        $this->title = $title;
        $this->keywords = $keywords;
        $this->description = $description;
    }
}