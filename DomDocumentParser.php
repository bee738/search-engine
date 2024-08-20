<?php
class DomDocumentParser 
{
    private $doc;

    public function __construct($url) 
    {
        $html = '<?xml encoding="UTF-8">';

        $options = array(
            'http'=>array('method'=>"GET", 'header'=>"User-Agent: doogleBot/0.1\n")
        );
        $context = stream_context_create($options);
        
        // Ensure the URL starts with 'http' or 'https'
        if (strpos($url, 'http') !== 0) {
            $url = 'https://' . $url;
        }
        
        $getContents = file_get_contents($url, false, $context);

        $this->doc = new DOMDocument('1.0', 'utf-8');
        @$this->doc->loadHTML($html . $getContents);
        //@ Error suppression is unnecessary, PHP>7.0 supports HTML5
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
