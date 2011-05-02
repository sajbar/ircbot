<?

class phpBot {

    private $_host = "bigchief.hemligt.net";
    private $port = 6667;
    private $_nick = "Infobot";
    private $_ident = "Infobot";
    private $_chan = "#info";
    private $_realname = "Infobot";
    private $_connected = false;
    private $_fp;
    private $_line;
    public $quit;
    private $_serverHost;
    private $_moduleDir = "./modules/";
    private $_rssFeedReader;

    public function __construct() {
        include($this->_moduleDir . "rssFeedReader.php");

        $this->_rssFeedReader = new rssFeedReader();
    }


    public function connect() {

        // open a socket connection to the IRC server
        $this->_fp = fsockopen($this->_host, $this->port, $erno, $errstr, 30);

        // print the error if there is no connection
        if (!$this->_fp) {
            echo $errstr . " (" . $errno . ")\n";
        } else {
            // write data through the socket to join the channel
            fwrite($this->_fp, "NICK " . $this->_nick . "\r\n");

            fwrite($this->_fp, "USER " . $this->_ident . " " . $this->_host . " bla :" . $this->_realname . "\r\n");

            $this->_parseMessages();
        }
    }

    private function _parseMessages() {
        while (true) {
            if (!feof($this->_fp)) {

                $this->_line = trim(fgets($this->_fp, 128));
                if (preg_match("/:([\S]+) 002 ([\S]+) :Your host is ([\S]+), running version ([\S]+)$/i", $this->_line, $match)) {
                    $this->_serverHost = $match[3];
                }
                if (preg_match("/\001([\S]+)\001$/i", $this->_line, $match)) {
                    $userinfo = $this->getUserinfo();
                    $this->_parseCTCP($match[1], $userinfo['nick']);
                } elseif (strpos($this->_line, "PING") !== FALSE) {
                    $this->_parsePing();
                } elseif (strpos($this->_line, "PRIVMSG") !== FALSE) {
                    $this->_parsePrivMsg();
                } elseif (strpos($this->_line, "MODE") !== FALSE) {
                    $this->_parseMode();
                } elseif (strpos($this->_line, "KICK") !== FALSE) {
                    $this->_join();
                } elseif (strpos($this->_line, "PART") !== FALSE) {
                    $this->_parsePart();
                } elseif (strpos($this->_line, "INVITE") !== FALSE) {
                    $this->_parseInvite();
                } elseif (strpos($this->_line, "JOIN") !== FALSE) {
                    $this->_parseJoins();
                } else {
                    echo date("d-m-Y H:i:s") . "  " . $this->_line . "\n";
                }
            } else {
                fclose($this->_fp);
                break;
            }
            if ($this-> _connected) {
                foreach($this->_rssFeedReader->getNewEntries() as $msg) {
                     $this->_sendMessage($msg, $this->_chan);
                     sleep(1);
                }
            }
        }
    }

    private function _join() {
        fwrite($this->_fp, "JOIN " . $this->_chan . "\r\n");
    }

    private function _parsePing() {
        if (preg_match("/PING :([\S]+)$/", $this->_line, $match)) {
            fwrite($this->_fp, "PONG " . $match[1] . "\r\n");
            echo(date("d-m-Y H:i:s") . " sent PONG\n");
        } elseif ($this->_line == "PING :" . $this->_serverHost) {
            fwrite($this->_fp, "PONG \r\n");
            echo("sent PONG\n");
        } elseif (preg_match("/:([\S]+)!([\S]+)@([\S]+) PRIVMSG ([\S]+) :PING ([\d]+) ([\d]+)$/i", $this->_line, $match)) {
            $userInfo = $this->_getUserinfo();
            $response = time() . " " . $match[6];
            $this->_sendCTCPresponse($userInfo['nick'], PING, $response);
        } else {
            echo($this->_line . "\n");
        }
    }

    private function _parseKick() {
        if (strpos($this->_line, $this->_nick) !== FALSE) {
            $this->_join();
        }
    }

    private function _parseInvite() {
        preg_match("/INVITE ([\S]+) :([\S]+)$/i", $this->_line, $match);
        $this->_joinChannel($match[2]);
    }

    private function _parseJoins() {
        preg_match("/JOIN :#([\S\s]+)$/i", $this->_line, $match);
        $userInfo = $this->_getUserinfo();
        $channel = "#" . $match[1];
        if ($channel == $this->_chan && false == $this_ > _connected) {
            $this->_connected = true;
        }
    }

    private function _parseMode() {
        echo($this->_line . "\n");
        //:Daniel!~sajbar@hemligt-63C3C317.hemligt.net MODE #news +o MyBotZ0r
        preg_match("/MODE #([\S]+) ([\S]+) ([\S]+)$/i", $this->_line, $match);
        $userInfo = $this->_getUserinfo();
        $channel = "#" . $match[1];
        $mode = $match[2];
        $target = $match[3];
    }

    private function _parsePart() {
        echo($this->_line . "\n");
        preg_match("/PART #([\S]+)([\S\s]*)$/i", $this->_line, $match);
        $userInfo = $this->_getUserinfo();
        $channel = "#" . $match[1];
    }

    private function _joinChannel($channel) {
        fwrite($this->_fp, "JOIN " . $channel . "\r\n");
    }

    private function _parsePrivMsg() {
        $userInfo = $this->_getUserInfo();
        preg_match("/PRIVMSG ([\S]+) :([\S\s]+)$/i", $this->_line, $match);

        //check if it's a channel message or not
        if ($match[1][0] == "#") {
            $this->_parseChannelMessage($userInfo['nick'], $match[1], $match[2]);
        } else {//prvate message
            $this->_parsePrivateMessage($userInfo['nick'], $match[0]);
        }
    }

    private function _parseChannelMessage($nick, $channel, $msg) {
        if (preg_match("/^quit([\S\s]*)$/i", $msg, $match)) {
            $quit = "QUIT :" . trim($match[1]) . "\r\n";
            $this->quit = true;
            fwrite($this->_fp, $quit);
        } elseif (preg_match("/^loadmodules([\S\s]*)$/i", $msg, $match)) {
            $this->loadModules();
        } elseif (preg_match("/^prut([\S\s]*)$/i", $msg, $match)) {
            $this->_sendMessage("PRUUUUUUUUUUT!", $channel);
        } elseif (preg_match("/^fisse([\S\s]*)$/i", $msg, $match)) {
            $this->_sendMessage("FISSSSSSSSSSSSSSSEEEEEEEEE!!", $channel);
        } elseif (preg_match("/^!op([\S\s]*)$/i", $msg, $match)) {
            if (trim($match[1]) != "") {
                $this->_setMode("+o", $channel, trim($match[1]));
            }
        }elseif (preg_match("/^!n([\S\s]*)$/i", $msg, $match)) {
            $this->_sendMessage($this->_rssFeedReader->getLastEntry($match[1]), $channel);
        } 
    }

    private function _sendMessage($msg, $target) {
        $msg = "PRIVMSG " . $target . " :" . trim($msg) . "\r\n";
        fwrite($this->_fp, $msg);
    }

    private function _parseCTCP($ctcp, $nick) {
        if ($ctcp == "VERSION") {
            fwrite($this->_fp, "NOTICE " . $nick . " :\001VERSION 1.0 Nanoy's bot\001\r\n");
        } else {
            echo($this->_line . "\n");
        }
    }

    private function _sendCTCPresponse($nick, $type, $message) {
        echo("NOTICE " . $nick . " :\001" . $type . " " . $message . "\001\r\n");
        fwrite($this->_fp, "NOTICE " . $nick . " :\001" . $type . " " . $message . "\001\r\n");
    }

    private function _parsePrivateMessage($nick, $msg) {
        if (strpos($msg, "\001VERSION\001") !== FALSE) {
            fwrite($this->_fp, "NOTICE " . $nick . " :\001VERSION 1.0 Nanoy's bot\001\r\n");
        }
    }

    private function _getUserinfo() {
        preg_match("/^:([\S]+)!([\S]+)@([\S]+) ([\S]+) :([\S\s]+)$/i", $this->_line, $match);
        $userInfo['nick'] = $match[1];
        $userInfo['ident'] = $match[2];
        $userInfo['hostname'] = $match[3];
        $userInfo['fullHostInfo'] = $userInfo['nick'] . "!" . $userInfo['ident'] . "@" . $userInfo['hostname'];

        return $userInfo;
    }

    private function _setMode($mode, $channel, $param = null) {
        if (is_null($param)) {
            $str = "MODE " . $channel . "  " . $mode;
        } else {
            $str = "MODE " . $channel . "  " . $mode . "  " . $param;
        }
        echo($str . "\r\n");
        fwrite($this->_fp, $str . "\r\n");
    }

    public function loadModules() {
        echo("hello");
        if (is_dir($this->moduledir)) {
            $dh = opendir($this->moduledir);
            if ($dh != false) {
                while (($file = readdir($dh)) !== false) {
                    if (preg_match("/([\S]+).class.php$/i", $file, $match)) {
                        $str = file_get_contents($this->moduledir . $file);
                        echo($this->moduledir . $file . "\n");

                        $flaf = eval($str);
                        $$match[1] = new $match[1]();
                        if (is_a($$match[1], "absModules")) {
                            echo("yes\n");
                        }
                    }
                }
                closedir($dh);
            }
        }
    }

}

$bot = new phpBot();
$bot->connect();
if ($bot->quit == false) {
    //$bot->connect();
}
?>