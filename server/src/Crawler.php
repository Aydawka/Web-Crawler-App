<?php

namespace App;

use App\Model\Page;
use App\Model\Queue;
use DOMDocument;
use DOMXPath;

class Crawler
{
    // Returns array of pages
    public function crawl($seedUrl, $maxRows)
    {
        ini_set('memory_limit','256M');

        $processedSeed = $this->getSeed($seedUrl);

        $queue = new Queue();
        $pages = [];

        $queue->enqueue($seedUrl);

        // Breadth first search into the urls until limit or none remain
        while($queue->peek() && count($pages)<$maxRows)
        {
            $page = $this->index($queue, $pages, $processedSeed);
            if ($page){
                $pages[] = $page;
                //echo("Total: " . count($pages) . "\n");
            }
        }

        return $pages;
    }

    protected function getSeed($seedUrl)
    {
        // Seed url may be redirected
        $content = file_get_contents($seedUrl);
        $location = $this->getHeader($http_response_header, "Location");

        if($location)
            $seedUrl = $this->normalize($location, $seedUrl);

        $parts = parse_url($seedUrl);
        $scheme = array_key_exists("scheme", $parts) ? $parts["scheme"] : '';
        $host =   array_key_exists("host", $parts) ? $parts["host"]:'';
        $port = array_key_exists("port", $parts) ? $parts["port"]:'';
        $path =   array_key_exists("path", $parts) ? $parts["path"]:'';

        // remove file segment
        $segments = explode("/", $path);
        $path = implode("/", array_slice($segments, 0, count($segments) - 1));

        $seedUrl = "{$scheme}://{$host}";
        $seedUrl .= $this->getPort($scheme, $port);
        $seedUrl .= $path;

        return $seedUrl . "/";
    }

    // Returns Page|null
    protected function index(Queue $queue, array $pages, $seedUrl)
    {
        $currentUrl = $queue->dequeue();

        // 15 second timeout
        $context = stream_context_create(
            ['http'=> ['timeout' => 15]]
        );

        $page_contents = @file_get_contents($currentUrl, false, $context);

        if(!$page_contents || !trim($page_contents))
            return null;

        $matches = [];
        preg_match('/([0-9])\d+/',$http_response_header[0],$matches);
        $responsecode = intval($matches[0]);

        // Check status code
        if($responsecode<600 && 400<=$responsecode){
            return null;
        }

        // Follow redirects
        $location = $this->getHeader($http_response_header, "Location");

        // Normalize any redirect
        if($location)
        {
            $currentUrl = $this->normalize($location, $currentUrl);
        }

        //if pages contains a page with the current url (already done)
        $found = array_filter($pages,
            function(Page $page)use ($currentUrl){
                return $page->url===$currentUrl;
            }
        );

        if(count($found)){
            return null;
        }

        // Only index these content types
        $whitelist =
            [
                "text/html",
                "application/xhtml+xml",
                "application/xml",
                "text/xml",
            ];

        $contentType = $this->getHeader($http_response_header, "Content-type");

        $parts=explode(';',$contentType);

        $contentType = trim($parts[0]);

        if (!in_array(strtolower($contentType), $whitelist)){
            return null;
        }

        $currentUrl = $this->getBaseUrl($currentUrl);

        $dom = new DOMDocument();
        $success = @$dom->loadHTML($page_contents, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING );

        if(!$success)
           return null;

        $xpath = new DOMXpath($dom);
        $titleElements = $xpath->query("//head/title");



        $title = "";
        if($titleElements->length)
        {
            $title = $titleElements->item(0)->textContent;
        }

        $nameAttr = "translate(@name,'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')";

        $query = "//head/meta[{$nameAttr}='keywords']/@content";
        $keywordsAttributes = $xpath->query($query);
        // xpath to extract the anchors
        $keywords = [];
        if($keywordsAttributes->length)
        {
            $keywords = array_map(
                function($keyword)
                {
                    return trim($keyword);
                },
                explode(",", $keywordsAttributes->item(0)->value)
            );
        }

        $query1 = "//head/meta[{$nameAttr}='description']/@content";
        $descriptionAttributes = $xpath->query($query1);


        $description = $descriptionAttributes->length ? $descriptionAttributes->item(0)->textContent : '';

        $anchorAttributes = $xpath->query("//a/@href");

        $urls = array_map(
            function($attribute)
            {
                return trim($attribute->value);
            },
            iterator_to_array($anchorAttributes)
        );

        //echo("Total original found: " . count($urls) . "\n");

        $urls = array_filter(
            $urls,
            function($url)
            {
                return !preg_match('/^\s*(file|javascript|ftp):/i', $url);
            }
        );

        //echo("Total valid found: " . count($urls) . "\n");

        // Normalize urls
        $urls = array_map(
            function($url) use($currentUrl)
            {
                return rtrim($this->normalize($url, $currentUrl),"/");
            },
            $urls
        );

        //echo("Total normal found: " . count($urls) . "\n");

        // Remove duplicates
        $urls = array_unique($urls);

        // if url doesn't start with seed url return false
        $urls=
            array_filter($urls,
                function ($url) use ($seedUrl){
                    return strpos(trim($url), trim($seedUrl)) === 0;
            });

        //echo("Total prefix found: " . count($urls) . "\n");

        // Filter out urls that have already been processed page
        $urls = array_filter($urls,
           function ($url) use($pages)
           {
               return !count(array_filter(
                   $pages,
                   function(Page $page) use($url)
                   {
                       return $url === $page->url;
                   }
               ));
           });

       //echo("Total non-processed found: " . count($urls) . "\n");

        foreach($urls as $url)
        {
            $queue->enqueue($url);
        }

        return new Page($currentUrl, $title, $keywords, $description);
    }

    protected function getHeader(array $headers, $name)
    {
        $headers =
            array_values(array_filter(
                $headers,
                function($header) use($name)
                {
                    return preg_match(sprintf('/^\s*%s:/i', $name), $header);
                }
            ));

        if(!count($headers))
            return null;

        $last = $headers[count($headers)-1];

        // Name: Value
        return preg_replace(sprintf('/^\s*%s:\s*/i', $name), "", $last);
    }

    protected function getBaseUrl($location)
    {
        return $location;
    }

    // Make sure has all parts and remove traversal
    protected function normalize($url, $baseUrl)
    {
        // Make sure has all parts
        $qualified = $this->qualify($url, $baseUrl);

        $parts = parse_url($qualified);

        $scheme = $parts["scheme"];
        $host = array_key_exists("host", $parts) ? $parts["host"]:'';
        $port = array_key_exists("port", $parts) ? $parts["port"]:'';
        $path = array_key_exists("path", $parts) ? $parts["path"]:'';
        $query = array_key_exists("query", $parts) ? $parts["query"]:'';

        $path = $this->removeTraversal($path);

        $normal = "{$scheme}://{$host}";

        $normal .= $this->getPort($scheme, $port);

        //$normal .= rtrim($path, "/");
        $normal .= $path;

        if($query)
            $normal .= "?{$query}";

        return $normal;
    }

    function getPort($scheme, $port)
    {
        if(!$port)
            return "";

        if($scheme === "https" && $port === 443)
            return "";

        if($scheme === "http" && $port === 80)
            return "";

        return ":{$port}";
    }

    // Make sure has all parts
    protected function qualify($url, $baseUrl)
    {
        $parts = parse_url($baseUrl);
        $scheme = array_key_exists("scheme", $parts) ? $parts["scheme"] : '';
        $host =   array_key_exists("host", $parts) ? $parts["host"]:'';
        $port = array_key_exists("port", $parts) ? $parts["port"]:'';
        $path =   array_key_exists("path", $parts) ? $parts["path"]:'';

        $port = $port ? ":{$port}" : '';

        // remove file segment
        $segments = explode("/", $path);
        $path = implode("/", array_slice($segments, 0, count($segments) - 1));

        // Fully qualified
        if(preg_match('/^\s*https?:/i', $url))
            return $url;

        // Scheme relative
        if (preg_match('/^\s*\/\//i', $url))
            return "{$scheme}:{$url}";

        // Absolute
        if (preg_match('/^\s*\//i', $url))
            return "{$scheme}://{$host}{$port}{$url}";

        // Relative
        return "{$scheme}://{$host}{$port}{$path}/{$url}";
    }

    protected function removeTraversal($path)
    {
        $segments = explode("/", $path);

        $stack = [];
        foreach ($segments as $segment)
        {
            if($segment === "")
            {
                if(!count($stack) || $stack[count($stack) - 1] !== "")
                    $stack[] = $segment;
                continue;
            }

            if($segment === ".")
                continue;

            if($segment === "..")
            {
                array_pop($stack);
                continue;
            }

            $stack[] = $segment;
        }

        return implode("/", $stack);
    }
}