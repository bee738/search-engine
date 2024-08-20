<?php
class DomDocumentParser
{
    private $doc;

    public function __construct($url)
    {
        $html = '<?xml encoding="UTF-8">';

        // Ensure the URL starts with 'http' or 'https'
        if (strpos($url, 'http') !== 0) {
            $url = 'https://' . $url;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3");

        $getContents = curl_exec($ch);
        if ($getContents === false) {
            die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
        }
        curl_close($ch);

        $this->doc = new DOMDocument('1.0', 'utf-8');
        @$this->doc->loadHTML($html . $getContents);
    }

    public function getLinks()
    {
        return $this->doc->getElementsByTagName("a");
    }

    public function getTitleTags()
    {
        return $this->doc->getElementsByTagName("title");
    }

    public function getMetaTags()
    {
        return $this->doc->getElementsByTagName("meta");
    }

    public function getImages()
    {
        return $this->doc->getElementsByTagName("img");
    }
}
?>
