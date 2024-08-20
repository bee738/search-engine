<?php
class SiteResultsProvider
{
    private $con;

    public function __construct($con) 
    {
        $this->con = $con;
    }

    public function getNumResults($term) 
    {
        $query = $this->con->prepare("SELECT COUNT(*) as total 
                                      FROM sites WHERE title LIKE :term 
                                      OR url LIKE :term 
                                      OR keywords LIKE :term 
                                      OR description LIKE :term");

        $searchTerm = "%" . $term . "%";
        $query->bindParam(":term", $searchTerm);
        $query->execute();

        $row = $query->fetch(PDO::FETCH_ASSOC);
        return $row["total"];
    }

    public function getResultsHtml($page, $pageSize, $term) 
    {
        $fromLimit = ($page - 1) * $pageSize;

        $query = $this->con->prepare("SELECT * 
                                      FROM sites WHERE title LIKE :term 
                                      OR url LIKE :term 
                                      OR keywords LIKE :term 
                                      OR description LIKE :term
                                      ORDER BY clicks DESC
                                      LIMIT :fromLimit, :pageSize");

        $searchTerm = "%" . $term . "%";
        $query->bindParam(":term", $searchTerm);
        $query->bindParam(":fromLimit", $fromLimit, PDO::PARAM_INT);
        $query->bindParam(":pageSize", $pageSize, PDO::PARAM_INT);
        $query->execute();

        $resultsHtml = "<div class='siteResults'>";

        while($row = $query->fetch(PDO::FETCH_ASSOC)) 
        {
            $id = $row["id"];
            $url = $row["url"];
            $title = $row["title"];
            $description = $row["description"];

            $title = $this->trimField($title, 55);
            $description = $this->trimField($description, 230);

            if (strpos($url, "youtube.com/watch") !== false) {
                // Customize display for YouTube videos
                $videoId = $this->getYouTubeVideoId($url);
                $thumbnailUrl = "https://img.youtube.com/vi/$videoId/0.jpg";

                $resultsHtml .= "<div class='resultContainer'>
                                    <div class='videoThumbnail'>
                                        <img src='$thumbnailUrl' alt='$title'>
                                    </div>
                                    <div class='videoDetails'>
                                        <h3 class='title'>
                                            <a class='result' href='$url' data-linkId='$id'>
                                                $title
                                            </a>
                                        </h3>
                                        <span class='url'>$url</span>
                                        <span class='description'>$description</span>
                                    </div>
                                 </div>";
            } else {
                // Standard result display
                $resultsHtml .= "<div class='resultContainer'>
                                    <h3 class='title'>
                                        <a class='result' href='$url' data-linkId='$id'>
                                            $title
                                        </a>
                                    </h3>
                                    <span class='url'>$url</span>
                                    <span class='description'>$description</span>
                                </div>";
            }
        }

        $resultsHtml .= "</div>";

        return $resultsHtml;
    }

    private function trimField($string, $characterLimit) 
    {
        $dots = strlen($string) > $characterLimit ? "..." : "";
        return substr($string, 0, $characterLimit) . $dots;
    }

    private function getYouTubeVideoId($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
        return $queryParams['v'] ?? null;
    }
}
?>
