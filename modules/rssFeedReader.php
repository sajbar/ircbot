<?php

class rssFeedReader {

    private $_feeds = array();
    private $_headlines = array();

    public function __construct() {
        $this->_feeds['newz'] = array(
            'url' => 'http://newz.dk/rss/news/nopicture',
            'lastCheck' => 0
        );
        $this->_feeds['eb'] = array(
            'url' => 'http://ekstrabladet.dk/rss2/?mode=normal',
            'lastCheck' => 0
        );

        // return $this->_getNewEntriesForAllSites();
    }

    protected function _getNewEntriesForAllSites() {

        return $this->getNewEntries();
    }

    public function getNewEntries() {
        $newHeadlines = array();
        foreach ($this->_feeds as $shortName => $array) {
            if ($array['lastCheck'] < time()) {
                if (!isset($this->_headlines[$shortName])) {
                    $this->_headlines[$shortName] = array();
                }
                $newHeadlines = array_merge($newHeadlines, $this->_getSpecificNewEntries($shortName));
            } else {
                echo("no go\n");
            }
        }

        return $newHeadlines;
    }

    public function getLastEntry($shortName) {

        $shortName = trim(strtolower($shortName));

        $this->_getSpecificNewEntries($shortName);
        $headline = reset($this->_headlines[$shortName]);

        return($headline['pubDate'] . " " . $shortName . ": " . $headline['title'] . " ". $headline['link'] . "\n");
    }

    protected function _getSpecificNewEntries($shortName) {
        if (array_key_exists($shortName, $this->_feeds)) {
            $url = $this->_feeds[$shortName]['url'];
            $doc = new DOMDocument();
            $newHeadlines = array();
            $xml = file_get_contents($url);
            if ($doc->loadXML($xml)) {
                $items = $doc->getElementsByTagName('item');
                $headlines = array();

                foreach ($items as $item) {
                    $headline = array();

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
                    if (!is_array($this->_headlines[$shortName])) {
                        var_dump($shortname, $this->_headlines[$shortName]);
                        die;
                    }
                    if (!@array_key_exists($headline['guid'], $this->_headlines[$shortName])) {
                        $this->_headlines[$shortName][$headline['guid']] = $headline;
                        $newHeadlines[] = $headline['pubDate']  . " " . $shortName . ": " . $headline['title'] . " " . $headline['link'];
                    }
                }
                $this->_feeds[$shortName]['lastCheck'] = (time() + 120);
                return $newHeadlines;
            }
        }
    }

}

$foo = new rssFeedReader();
