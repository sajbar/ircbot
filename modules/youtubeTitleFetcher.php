<?php 
class youtubeTitleFetcher
{

    public function getTitle($url)
    {
        if (false !== strpos($url, "@")) {
            return false;
        }

        if (false !== strpos($url, "youtube.com") || false !== strpos($url, "youtu.be")) {
            $url = explode(" ", $url);
            $url = $url[0];

            if (false === strpos($url, "http://")) {
                $url = "http://" . $url;
            }

            $content = @file_get_contents($url, null, null, null, 3000);
            if (false === $content) {
                return false;
                ;
            }
            eregi('<title>(.*)</title>', $content, $matches);
            return $url . " - " . trim(str_replace(array ("\n", "  ", "- YouTube"), " ", $matches[1]));
        } else {
            return false;
        }
    }

}