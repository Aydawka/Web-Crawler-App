<?php

namespace App\Operation;

use App\Crawler;
use App\PageRepository;

class SearchOperation
{
    protected $pageRepository;

    function __construct()
    {
        $this->pageRepository = new PageRepository();
    }

    function run()
    {   global $argv;

        $search = "";


        // Command line
        if(php_sapi_name() === "cli")
        {
            $where = $argv[2];
            $term = array_key_exists(3, $argv) ? $argv[3] : '';
        }
        else
        {
            // for api
            if($_SERVER["CONTENT_TYPE"] !== "application/json")
            {
                http_response_code(406);
                echo("Unacceptable content type");
                return;
            }

            // Decode request body from json to array
            $input = json_decode(file_get_contents("php://input"), true);

            if(
                !isset($input["where"])
                || !isset($input["term"])
                || !in_array($input["where"], ["title", "keywords", "description" ,"all", "list"], true))
            {
                http_response_code(422);
                echo("Invalid request");
                return;
            }

            $where = $input["where"];
            $term = $input["term"];

        }

        $pages = $this->pageRepository->search($where,$term );

        header('Content-Type: application/json');
        echo(json_encode($pages, JSON_PRETTY_PRINT));
    }


    function supports()
    {
        global $argv;
        // Command line
        if(php_sapi_name() === "cli")
            return $argv[1] === "search";
        // API
        return $_SERVER['PATH_INFO'] === "/search" && $_SERVER['REQUEST_METHOD'] === "POST";
    }

}