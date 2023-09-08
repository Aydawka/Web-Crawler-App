<?php

namespace App;

use PDO;

class PageRepository
{
    protected $con;

    public function __construct()
    {
        $host = 'undcsmysql.mysql.database.azure.com';
        $db   = 'aydan_gasimova';
        $user = 'aydan.gasimova@undcsmysql';
        $pass = 'aydan7182';

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $this->con = new PDO($dsn, $user, $pass, $options);
        $this->con->exec("set wait_timeout = 3600");
    }

    public function save(array $pages)
    {
        $this->con->beginTransaction();

        $deleteSql="delete from web_scraping where URL=?";
        $deleteStatement = $this->con->prepare ($deleteSql);

        $insertSql = "insert into web_scraping(URL, Title, Keywords, Description) values(?, ?, ?, ?)";
        $insertStatement = $this->con->prepare ($insertSql);

         foreach($pages as $page){
             $deleteStatement->execute([$page->url]);
             $insertStatement->execute([$page->url, $page->title, implode(", ", $page->keywords), $page->description]);
        }

        $this->con->commit();
    }

    protected function buildSearchSql($where)
    {
        // where can be ["title", "keywords", "description" ,"all", "list"]

        $where =strtolower($where);

        $select = "SELECT URL as urls,Title as title, Keywords as keywords, Description as description";

        if($where === "list")
            return "{$select} FROM web_scraping";

        if($where === "title")
            return "{$select} FROM web_scraping where title like :term";

        if($where === "keywords")
            return "{$select} FROM web_scraping where keywords like :term";

        if($where === "description")
            return "{$select} FROM web_scraping where description like :term";

        if($where === "all")
           return "{$select} FROM web_scraping where Title like :term or URL like :term or Keywords like :term or Description like :term";

        throw new \Exception("invalid search: {$where}");
    }

    protected function buildArgs($where, $term)
    {
        $where =strtolower($where);

        if($where === "list")
            return [];

        return ["term" => "%$term%"];
    }

    public function search($where, $term )
    {
        $searchstmt = $this->con->prepare($this->buildSearchSql($where));
        $searchstmt->execute($this->buildArgs( $where,$term));
        return $searchstmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reset()
    {
        $this->con->exec('TRUNCATE TABLE web_scraping');
    }
}

