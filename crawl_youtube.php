<?php
include("config.php");
include("classes/DomDocumentParser.php");

function videoExists($url) {
    global $con;
    $query = $con->prepare("SELECT * FROM videos WHERE url = :url");
    $query->bindParam(":url", $url);
    $query->execute();
    return $query->rowCount() != 0;
}

function insertVideo($url, $title) {
    global $con;
    $query = $con->prepare("INSERT INTO videos(url, title) VALUES(:url, :title)");
    $query->bindParam(":url", $url);
    $query->bindParam(":title", $title);
    return $query->execute();
}

function crawlYouTube($url) {
    $parser = new DomDocumentParser($url);
    
    $links = $parser->getLinks();
    $videoLinks = array();
    
    foreach ($links as $link) {
        $href = $link->getAttribute("href");
        
        if (strpos($href, "/watch?v=") !== false) {
            $fullUrl = "https://www.youtube.com" . $href;
            if (!in_array($fullUrl, $videoLinks)) {
                $videoLinks[] = $fullUrl;
            }
        }
    }

    return $videoLinks;
}

// Example URL (YouTube channel or search result page)
$youtubeURL = "https://www.youtube.com/results?search_query=technology";

$videos = crawlYouTube($youtubeURL);

foreach ($videos as $video) {
    // Extract video title for the database (this is just a placeholder, real implementation may vary)
    $videoTitle = "Example Title"; // You may use another parser to get the title from each video page
    
    if (!videoExists($video)) {
        insertVideo($video, $videoTitle);
        echo "Inserted: " . $video . "<br>";
    } else {
        echo "Already exists: " . $video . "<br>";
    }
}
?>
