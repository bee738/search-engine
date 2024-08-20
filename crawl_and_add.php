<?php
include("config.php");
include("classes/Crawler.php");
include("classes/DomDocumentParser.php");

// Function to add YouTube links to the database
function addYouTubeLinksToDatabase($youtube_links) {
    global $con;

    // Split the textarea value into an array of individual YouTube links
    $links_array = explode("\n", $youtube_links);

    foreach ($links_array as $link) {
        // Validate if the link is a valid YouTube URL
        if (preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([\w-]{11})$/', $link, $matches)) {
            $video_id = $matches[4];

            // Check if the video ID already exists in the database
            $query = $con->prepare("SELECT * FROM youtube_videos WHERE video_id = :video_id");
            $query->bindParam(":video_id", $video_id);
            $query->execute();

            if ($query->rowCount() == 0) {
                // Insert the YouTube link into the database
                $query = $con->prepare("INSERT INTO youtube_videos(video_id, video_url) VALUES(:video_id, :video_url)");
                $query->bindParam(":video_id", $video_id);
                $query->bindParam(":video_url", $link);
                $query->execute();
                echo "Successfully added: $link<br>";
            } else {
                echo "Skipping existing video: $link<br>";
            }
        } else {
            echo "Invalid YouTube link: $link<br>";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the textarea field is set and not empty
    if (isset($_POST["youtube_links"]) && !empty($_POST["youtube_links"])) {
        // Get the YouTube links from the textarea
        $youtube_links = $_POST["youtube_links"];

        // Add YouTube links to the database
        addYouTubeLinksToDatabase($youtube_links);
    } else {
        echo "Please enter YouTube links in the textarea.";
    }
}
?>
