<?php

namespace App;

use App\Exception\AccessException;
use Exception;
use SplFileInfo;

class SourceService
{

    public function getSource($password)
    {
        if($password !== "hugemouse60")
            throw new AccessException("Wrong password");

        return $this->loadSource(__DIR__);
    }

    private function loadSource($directory)
    {
        $allfiles = scandir($directory);

        $allfiles = array_filter($allfiles,
            function ($file){
                return !preg_match("/^\./i", $file);
            });

        $allfiles=array_map(function ($file)use($directory){
            return $directory.'/'.$file;
        },$allfiles);

        $files = array_filter($allfiles,
        function ($file){
            return !is_dir($file ) && preg_match("/\.php$/i", $file);
        });

        $filecodes=array_map(function ($file){
            return file_get_contents($file);
        },
            $files);

        $folders = array_filter($allfiles,
            function ($file){
                return is_dir($file);
            });

        /*var_dump($directory);
        var_dump($folders); die;*/

        $folderCodes=array_map(function ($folder){
            return $this->loadSource($folder);
        },
            $folders);

       $codes= array_merge($filecodes,$folderCodes);

       if(!count($codes))
            return "";

        return implode("\n",$codes);


        /////////////////////////////////////////////////////////////////////////////////////////

     /*   $direct=array_filter($allfiles,
        function ()use($allfiles){
           return is_dir($allfiles);
        });

        $files=array_map($allfiles,
           function ()
           { return $this->loadSource($directory);
           });


        $files=array_filter($files, preg_match("/\.php$/i",$files));

        $files=array_map(file_get_contents($files),$files);

        return $direct.$files;*/




        }





         // check the extextnsion

        //filter the directory and filter to get a list of files


}
