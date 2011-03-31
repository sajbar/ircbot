<?php

class rssFeedReader
{

    private $_feeds = array ();
    private $_headline;

    public function __construct()
    {
        $this->_feeds['newz'] = array (
            'url' => 'http://newz.dk/rss/news/nopicture'
        );
        $this->_feeds['eb'] = array (
            'url' => 'http://ekstrabladet.dk/rss2/?mode=normal'
        );
        $this->_run();
    }

    protected function _run()
    {
        while (true) {
            $this->_getNewEntries();
            // $this->_getLastEntry('eb');
            sleep(120);
        }
    }

    protected function _getNewEntries()
    {
        $doc = new DOMDocument();
        foreach ($this->_feeds as $shortName => $array) {
            if (!is_array($this->_headlines[$shortName])) {
                $this->_headlines[$shortName] = array ();
            }
            $xml = file_get_contents($array['url']);
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
                            }else if ($i->nodeName == 'link') {
                                $headline[$i->nodeName] = $i->nodeValue;
                            }else if ($i->nodeName == 'pubDate') {
                                $headline[$i->nodeName] = $i->nodeValue;
                            }
                        }
                    }
                    if (!array_key_exists($headline['guid'], $this->_headlines[$shortName])) {

                        $this->_headlines[$shortName][$headline['guid']] = $headline;
                        echo(date('d-m-Y H:i:s') . " " . $shortName . ": " . $headline['title'] . "\n");
                    }
                    
                }
            }
        }
    }

    protected function _getLastEntry($shortName)
    {
        $shortName = strtolower($shortName);
        $headline = reset($this->_headlines[$shortName]);

        echo($shortName . ": " . $headline['title'] . "\n");
    }

}

$foo = new rssFeedReader();
