<?php

class rssFeedReader
{

    private $_feeds = array ();
    private $_headlines;

    public function __construct()
    {
        $this->_feeds['newz'] = array (
            'url' => 'http://newz.dk/rss/news/nopicture'
        );
        $this->_feeds['eb'] = array (
            'url' => 'http://ekstrabladet.dk/rss2/?mode=normal'
        );
        $this->_getNewEntriesForAllSites();
    }

    protected function _getNewEntriesForAllSites()
    {
        $newHeadlines = array();
        $doc = new DOMDocument();
        foreach ($this->_feeds as $shortName => $array) {
            if (!isset($this->_headlines[$shortName])) {
                $this->_headlines[$shortName] = array ();
            }
            $newHeadlines = array_merge($newHeadlines, $this->_getSpecificNewEntries($array['url']));
        }
    }

    public function getLastEntry($shortName)
    {
        $shortName = strtolower($shortName);
        $headline = reset($this->_headlines[$shortName]);

        return($shortName . ": " . $headline['title'] . "\n");
    }

    protected function _getSpecificNewEntries($url)
    {
        $newHeadlines = array ();
        $xml = file_get_contents($url);
        if ($doc->loadXML($xml)) {
            $items = $doc->getElementsByTagName('item');
            $headlines = array ();

            foreach ($items as $item) {
                $headline = array ();

                if ($item->childNodes->length) {
                    foreach ($item->childNodes as $i) {
                        if ($i->nodeName == 'guid') {
                            $headline[$i->nodeName] = $i->nodeValue;
                        } else if ($i->nodeName == 'title') {
                            $headline[$i->nodeName] = $i->nodeValue;
                        } else if ($i->nodeName == 'link') {
                            $headline[$i->nodeName] = $i->nodeValue;
                        } else if ($i->nodeName == 'pubDate') {
                            $headline[$i->nodeName] = $i->nodeValue;
                        }
                    }
                }
                if (!array_key_exists($headline['guid'], $this->_headlines[$shortName])) {

                    $this->_headlines[$shortName][$headline['guid']] = $headline;
                    $newHeadlines[] = date('d-m-Y H:i:s') . " " . $shortName . ": " . $headline['title'];
                    // file_put_contents('test.txt', date('d-m-Y H:i:s') . " " . $shortName . ": " . $headline['title'] . "\n");
                }
            }
            return $newHeadlines;
        }
    }

}

$foo = new rssFeedReader();
