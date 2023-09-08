<?php

namespace App\Operation;

interface Operation
{
    function run();
    function supports();
}