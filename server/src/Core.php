<?php
namespace App;

use App\Operation\IndexOperation;
use App\Operation\ResetOperation;
use App\Operation\ShowSourceOperation;
use App\Operation\SearchOperation;

class Core
{
    private $operations = [];

    public function __construct
    ()
    {
        $this->operations =
            [
                new IndexOperation(),
               new ResetOperation(),
              new ShowSourceOperation(),
                new SearchOperation(),
            ];
    }

    // Entry point
    public function main()
    {
        global $argv;

        foreach ($this->operations as $operation)
        {
            if($operation->supports())
            {
                $operation->run();
                return;
            }
        }

        if(php_sapi_name() === "cli")
        {
            echo("Unsupported operation: " . implode(" ", $argv));
            return;
        }

        http_response_code(400);
        echo("Unsupported operation");
    }
}

