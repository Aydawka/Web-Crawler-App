<?php

namespace App\Operation;

use App\Exception\AccessException;
use App\SourceService;
use Exception;

class ShowSourceOperation implements Operation
{
    private $sourceService;

    public function __construct()
    {
        $this->sourceService = new SourceService();
    }

    function run()
    {
        global $argv;

        // Command line
        if(php_sapi_name() === "cli")
        {
            $password = $argv[2];
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

            if(!isset($input["password"]))
            {
                http_response_code(422);
                echo("Invalid request");
                return;
            }

            $password = $input["password"];
        }

        try{

            $source=  $this->sourceService->getSource($password);
        }
        catch (AccessException $e){
            http_response_code(403);
            echo("Access denied successfully");
            return;
        }

        $source = highlight_string($source, true);

        header('Content-Type: application/json');
        echo(json_encode($source, JSON_PRETTY_PRINT));
    }


    function supports()
    {
        global $argv;
        // Command line
        if(php_sapi_name() === "cli")
            return $argv[1] === "source";
        // API
        return $_SERVER['PATH_INFO'] === "/source" && $_SERVER['REQUEST_METHOD'] === "POST";
    }


}