<?php

class Crawler 
{
    private $con;
    private $alreadyCrawled = [];
    private $crawling = [];
    private $alreadyFoundImages = [];
    private $maxCrawlDepth = 1000000000000000000000; // Maximum crawl depth

    public function __construct($con) 
    {
        $this->con = $con;
    }

    public function linkExists($url) 
    {
        $query = $this->con->prepare("SELECT * FROM sites WHERE url = :url");
        $query->bindParam(":url", $url);
        $query->execute();
        return $query->rowCount() != 0;
    }

    public function imageExists($src) 
    {
        $query = $this->con->prepare("SELECT * FROM images WHERE imageUrl = :src");
        $query->bindParam(":src", $src);
        $query->execute();
        return $query->rowCount() != 0;
    }

    public function insertLink($url, $title, $description, $keywords)
    {
        $query = $this->con->prepare("INSERT INTO sites(url, title, description, keywords)
                                    VALUES(:url, :title, :description, :keywords)");
        $query->bindParam(":url", $url);
        $query->bindParam(":title", $title);
        $query->bindParam(":description", $description);
        $query->bindParam(":keywords", $keywords);
        return $query->execute();
    }

    public function insertImage($url, $src, $alt, $title) 
    {
        $query = $this->con->prepare("INSERT INTO images(siteUrl, imageUrl, alt, title)
                                    VALUES(:siteUrl, :imageUrl, :alt, :title)");
        $query->bindParam(":siteUrl", $url);
        $query->bindParam(":imageUrl", $src);
        $query->bindParam(":alt", $alt);
        $query->bindParam(":title", $title);
        return $query->execute();
    }

    public function createLink($src, $url)
    {
        $scheme = parse_url($url)["scheme"]; // http
        $host = parse_url($url)["host"]; // www.example.com
        
        if(substr($src, 0, 2) == "//") 
            $src =  $scheme . ":" . $src;
        else if(substr($src, 0, 1) == "/") 
            $src = $scheme . "://" . $host . $src;
        else if(substr($src, 0, 2) == "./") 
            $src = $scheme . "://" . $host . dirname(parse_url($url)["path"]) . substr($src, 1);
        else if(substr($src, 0, 3) == "../") 
            $src = $scheme . "://" . $host . "/" . $src;
        else if(substr($src, 0, 5) != "https" && substr($src, 0, 4) != "http") 
            $src = $scheme . "://" . $host . "/" . $src;
    
        return $src;
    }

    public function getDetails($url, $depth)
    {
        if($depth === $this->maxCrawlDepth) {
            return; // Maximum depth reached, stop crawling
        }

        if (strpos($url, "youtube.com/watch") !== false) {
            $this->parseYouTubeVideo($url);
            return;
        }

        $parser = new DomDocumentParser($url);
        $titleArray = $parser->getTitleTags();
    
        if(sizeof($titleArray) == 0 || $titleArray->item(0) == NULL)
            return;
    
        $title = $titleArray->item(0)->nodeValue;
        $title = str_replace("\n", "", $title);
    
        if($title == "")
            return;
    
        $description = "";
        $keywords = "";
    
        $metasArray = $parser->getMetatags();
    
        foreach($metasArray as $meta) 
        {
            if($meta->getAttribute("name") == "description")
                $description = $meta->getAttribute("content");
    
            if($meta->getAttribute("name") == "keywords")
                $keywords = $meta->getAttribute("content");
        }	
    
        $description = str_replace("\n", "", $description);
        $keywords = str_replace("\n", "", $keywords);
    
        if($this->linkExists($url))
            echo "$url already exists<br>";
        else if($this->insertLink($url, $title, $description, $keywords))
            echo "SUCCESS: $url<br>";
        else
            echo "ERROR: Failed to insert $url<br>";
    
        $imageArray = $parser->getImages();
        foreach($imageArray as $image) 
        {
            $src = $image->getAttribute("src");
            $alt = $image->getAttribute("alt");
            $title = $image->getAttribute("title");
    
            if(!$title && !$alt)
                continue;
    
            $src = $this->createLink($src, $url);
    
            if(!in_array($src, $this->alreadyFoundImages)) 
            {
                $this->alreadyFoundImages[] = $src;
    
                if($this->imageExists($src))
                    echo "$src already exists<br>";
                else if($this->insertImage($url, $src, $alt, $title))
                    echo "SUCCESS: $src<br>";
                else
                    echo "ERROR: Failed to insert $src<br>";
            }
    
        }
    
        echo "<b>URL:</b> $url, <b>Title:</b> $title, <b>Description:</b> $description, <b>Keywords:</b> $keywords<br>";

        // Increase depth and continue crawling
        $depth++;
        $this->followLinks($url, $depth);
    }

    public function followLinks($url, $depth)
    {
        $parser = new DomDocumentParser($url);
        $linkList = $parser->getLinks();
    
        foreach($linkList as $link) 
        {
            $href = $link->getAttribute("href");
    
            if(strpos($href, "#") !== false) 
                continue;
            else if(substr($href, 0, 11) == "javascript:") 
                continue;
    
            $href = $this->createLink($href, $url);
    
            if(!in_array($href, $this->alreadyCrawled)) 
            {
                $this->alreadyCrawled[] = $href;
                $this->crawling[] = $href;
    
                $this->getDetails($href, $depth);
            }
        }
    
        array_shift($this->crawling);
    
        foreach($this->crawling as $site)
            $this->followLinks($site, $depth);
    }

    private function parseYouTubeVideo($url)
    {
        $videoId = $this->getYouTubeVideoId($url);
        $apiKey = 'AIzaSyAwdubSlnYr7aCeNe3CC9lEetC88T9ON3E';
        $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=$videoId&key=$apiKey&part=snippet";

        $data = file_get_contents($apiUrl);
        $json = json_decode($data, true);

        if (isset($json['items'][0])) {
            $videoDetails = $json['items'][0]['snippet'];
            $title = $videoDetails['title'];
            $description = $videoDetails['description'];
            $keywords = implode(', ', $videoDetails['tags'] ?? []);

            if($this->linkExists($url))
                echo "$url already exists<br>";
            else if($this->insertLink($url, $title, $description, $keywords))
                echo "SUCCESS: $url<br>";
            else
                echo "ERROR: Failed to insert $url<br>";
        } else {
            echo "ERROR: Failed to retrieve YouTube video details<br>";
        }
    }

    private function getYouTubeVideoId($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        return $queryParams['v'] ?? null;
    }
}

?>