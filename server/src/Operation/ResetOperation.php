<?php
namespace App\Operation;

use App\Crawler;
use App\PageRepository;

class ResetOperation
{
    protected $pageRepository;

    function __construct()
    {
        $this->pageRepository = new PageRepository();
    }

    function run()
    {
        $this->pageRepository->reset();
    }

    function supports()
    {
        global $argv;
        // Command line
        if(php_sapi_name() === "cli")
            return $argv[1] === "reset";

        // API
        return $_SERVER['PATH_INFO'] === "/reset" && $_SERVER['REQUEST_METHOD'] === "POST";
    }

}
