<?php

namespace App\Operation;

use App\Crawler;
use App\PageRepository;

class IndexOperation implements Operation
{
    protected $pageRepository;
    protected $crawler;

    function __construct()
    {
        $this->pageRepository = new PageRepository();
        $this->crawler = new Crawler();
    }

    function run()
    {
        global $argv;

        $seed = "";
        $maxRows = 0;

        // Command line
        if(php_sapi_name() === "cli")
        {
            $seed = $argv[2];
            $maxRows = $argv[3];
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

            if(!isset($input["seedUrl"]) || !isset($input["maxRows"]))
            {
                http_response_code(422);
                echo("Invalid request");
                return;
            }

            $seed = $input["seedUrl"];
            $maxRows = $input["maxRows"];

            if($maxRows > 500)
            {
                http_response_code(422);
                echo("Max rows must not be greater than 500");
                return;
            }
        }

        $pages = $this->crawler->crawl($seed, $maxRows);
        $this->pageRepository->save($pages);

        // Send header to client
        header('Content-Type: application/json');
        echo(json_encode($pages, JSON_PRETTY_PRINT));

        if(php_sapi_name() === "cli")
            echo("\nTotal indexed: " . count($pages) . "\n");

    }

    function supports()
    {
        global $argv;

        // Command line
        if(php_sapi_name() === "cli")
            return $argv[1] === "index";

        // API
        return $_SERVER['PATH_INFO'] === "/save" && $_SERVER['REQUEST_METHOD'] === "POST";
    }
}
