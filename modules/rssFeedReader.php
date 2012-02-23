<?php

class rssFeedReader
{

    private $_feeds = array ();
    private $_headlines = array ();
    protected $_firstRun = true;
    protected $_chan = "#info";

    public function __construct()
    {
        $this->_feeds['newz'] = array (
            'url' => 'http://feeds.newzmedia.dk/c/32893/f/582670/index.rss',
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
                $newEntries = $this->_getSpecificNewEntries($shortName);
                if(is_array($newEntries)) {
                    $newHeadlines = array_merge($newHeadlines, $newEntries);
                }
                //echo("updated for " . $shortName . "\n");
            } else {
                //echo("no go for " . $shortName . "\n");
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
        
        $headline1 = reset($this->_headlines[$shortName]);
        $headline2 = end($this->_headlines[$shortName]);
        
        if(strtotime($headline1['pubDate']) > strtotime($headline2['pubDate']) ) {
            $headline = $headline1;
        } else {
            $headline = $headline2;
         }
        return "(" . $shortName . ") " . date("H:i:s", strtotime($headline['pubDate'])) . " " . $headline['title'] . " " . $this->_getTinyUrl($headline['link']);
    }

    protected function _getSpecificNewEntries($shortName)
    {
        if (array_key_exists($shortName, $this->_feeds)) {
            $url = $this->_feeds[$shortName]['url'];
            $doc = new DOMDocument();
            $newHeadlines = array ();
            $xml = @file_get_contents($url);
            if(!xml || $xml == '') {
                $this->_feeds[$shortName]['lastCheck'] = (time() + 120);
                return;
            }
            if (@$doc->loadXML($xml)) {
                $items = $doc->getElementsByTagName('item');
                $headlines = array ();

                foreach ($items as $item) {
                    $headline = array ();

                    if ($item->childNodes->length) {
                        foreach ($item->childNodes as $i) {
                            if ($i->nodeName == 'guid') {
                                $headline[$i->nodeName] = $i->nodeValue;
                            } else if ($i->nodeName == 'title') {
                                $title = html_entity_decode($i->nodeValue);
                                if($this->_is_utf8($title)) {
                                    $title = utf8_decode($title);
                                }
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
                        if (!$this->_firstRun) {
                            $newHeadlines[] = "(" . $shortName . ") " . date("H:i:s", strtotime($headline['pubDate'])) . " " . $headline['title'] . " " . $this->_getTinyUrl($headline['link']);
                           // file_put_contents('test.txt', "(" . $shortName . ") " . date("H:i:s", strtotime($headline['pubDate'])) . " " . $headline['title'] . " " . $this->_getTinyUrl($headline['link']) . "\n", FILE_APPEND);
                        }
                    }
                }

                $this->_feeds[$shortName]['lastCheck'] = (time() + 120);
                return $newHeadlines;
            }
        }
    }

    protected function _is_utf8($str)
    {
        $c = 0;
        $b = 0;
        $bits = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254))
                    return false;
                elseif ($c >= 252)
                    $bits = 6;
                elseif ($c >= 248)
                    $bits = 5;
                elseif ($c >= 240)
                    $bits = 4;
                elseif ($c >= 224)
                    $bits = 3;
                elseif ($c >= 192)
                    $bits = 2;
                else
                    return false;
                if (($i + $bits) > $len)
                    return false;
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191)
                        return false;
                    $bits--;
                }
            }
        }
        return true;
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
        
        $pos = strpos($data, "http://");
        if($pos === false) {
            return $url;
        } else {      
            return trim($data);
        }
    }
    
    public function getChan() {
        return $this->_chan;
    }
}
