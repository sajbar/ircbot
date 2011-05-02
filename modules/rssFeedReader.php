<?php

class rssFeedReader
{

    private $_feeds = array ();
    private $_headlines = array ();
    protected $_firstRun = true;

    public function __construct()
    {
        $this->_feeds['newz'] = array (
            'url' => 'http://newz.dk/rss/news/nopicture',
            'lastCheck' => 0
        );
        $this->_feeds['eb'] = array (
            'url' => 'http://ekstrabladet.dk/rss2/?mode=normal',
            'lastCheck' => 0
        );
        $this->_feeds['gaffa'] = array (
            'url' => 'http://gaffa.dk/feeds',
            'lastCheck' => 0
        );
        $this->_feeds['business'] = array (
            'url' => 'http://www.business.dk/seneste/rss',
            'lastCheck' => 0
        );
        $this->_feeds['pcworld'] = array (
            'url' => 'http://www.pcworld.dk/rss/all',
            'lastCheck' => 0
        );
        $this->_feeds['version2'] = array (
            'url' => 'http://www.version2.dk/feeds/nyheder',
            'lastCheck' => 0
        );
        $this->_feeds['dr'] = array (
            'url' => 'http://www.dr.dk/nyheder/service/feeds/allenyheder',
            'lastCheck' => 0
        );
        $this->_feeds['tv2'] = array (
            'url' => 'http://tv2.dk.feedsportal.com/c/33117/fe.ed/http://rss.tv2.dk/rss/show/site/news.tv2.dk/feed/Nyhederne/',
            'lastCheck' => 0
        );
        $this->_feeds['bt'] = array (
            'url' => 'http://www.bt.dk/bt/seneste/rss',
            'lastCheck' => 0
        );
        $this->_feeds['jp'] = array (
            'url' => 'http://jp.dk/rss/topnyheder.jsp',
            'lastCheck' => 0
        );
        $this->_feeds['pol'] = array (
            'url' => 'http://politiken.dk/rss/senestenyt.rss',
            'lastCheck' => 0
        );
        $this->_feeds['cworld'] = array (
            'url' => 'http://www.computerworld.dk/rss/all',
            'lastCheck' => 0
        );
        $this->_feeds['railgun'] = array (
            'url' => 'http://railgun.newz.dk/rss',
            'lastCheck' => 0
        );
        $this->_feeds['macnation'] = array (
            'url' => 'http://macnation.newz.dk/rss',
            'lastCheck' => 0
        );

        //$this->_getNewEntriesForAllSites();
    }

    protected function _getNewEntriesForAllSites()
    {

        return $this->getNewEntries();
    }

    public function getNewEntries()
    {
        $newHeadlines = array ();

        foreach ($this->_feeds as $shortName => $array) {
            if ($array['lastCheck'] < time()) {
                if (!isset($this->_headlines[$shortName])) {
                    $this->_headlines[$shortName] = array ();
                }
                $newHeadlines = array_merge($newHeadlines, $this->_getSpecificNewEntries($shortName));
                echo("updated for " .$shortName . "\n");
            } else {
                echo("no go\n");
            }
        }
        if ($this->_firstRun) {
            $this->_firstRun = false;
            return array ();
        }
        return $newHeadlines;
    }

    public function getLastEntry($shortName)
    {

        $shortName = trim(strtolower($shortName));

        $this->_getSpecificNewEntries($shortName);
        $headline = reset($this->_headlines[$shortName]);

        return "(" . $shortName . ") " . date("H:i:s", strtotime($headline['pubDate'])) . " " .$headline['title'] . " " .$this->_getTinyUrl($headline['link']) ;
    }

    protected function _getSpecificNewEntries($shortName)
    {
        if (array_key_exists($shortName, $this->_feeds)) {
            $url = $this->_feeds[$shortName]['url'];
            $doc = new DOMDocument();
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
                        if (! $this->_firstRun) {
                            $newHeadlines[] = "(" . $shortName . ") " . date("H:i:s", strtotime($headline['pubDate'])) . " " . $headline['title'] . " " . $this->_getTinyUrl($headline['link']);
                            file_put_contents('test.txt', "(" . $shortName . ") " . date("H:i:s", strtotime($headline['pubDate'])) . " " . $headline['title'] . " " . $this->_getTinyUrl($headline['link']) . "\n", FILE_APPEND);
                        }
                    }
                }

                $this->_feeds[$shortName]['lastCheck'] = (time() + 120);
                return $newHeadlines;
            }
        }
    }

    protected function _getTinyUrl($url)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url=' . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}
