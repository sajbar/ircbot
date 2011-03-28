<?

class phpBot
{

    private $host = "bigchief.hemligt.net";
    private $port = 6667;
    private $nick = "MyBotZ0r";
    private $ident = "MyBotz0r";
    private $chan = "#news";
    private $realname = "MyBot";
    private $fp;
    private $line;
    public $quit;
    private $serverHost;
    private $moduledir = "./modules/";

    public function connect()
    {

        // open a socket connection to the IRC server
        $this->fp = fsockopen($this->host, $this->port, $erno, $errstr, 30);

        // print the error if there is no connection
        if (!$this->fp) {
            echo $errstr . " (" . $errno . ")\n";
        } else {
            // write data through the socket to join the channel
            fwrite($this->fp, "NICK " . $this->nick . "\r\n");

            fwrite($this->fp, "USER " . $this->ident . " " . $this->host . " bla :" . $this->realname . "\r\n");
            $this->parseMessages();
        }
    }

    private function parseMessages()
    {
        while (!feof($this->fp)) {

            $this->line = trim(fgets($this->fp, 128));
            if (preg_match("/:([\S]+) 002 ([\S]+) :Your host is ([\S]+), running version ([\S]+)$/i", $this->line, $match)) {
                $this->serverHost = $match[3];
            }
            if (preg_match("/\001([\S]+)\001$/i", $this->line, $match)) {
                $userinfo = $this->getUserinfo();
                $this->parseCTCP($match[1], $userinfo['nick']);
            } elseif (strpos($this->line, "PING") !== FALSE) {
                $this->parsePing();
            } elseif (strpos($this->line, "PRIVMSG") !== FALSE) {
                $this->parsePrivMsg();
            } elseif (strpos($this->line, "MODE") !== FALSE) {
                $this->parseMode();
            } elseif (strpos($this->line, "KICK") !== FALSE) {
                $this->join();
            } elseif (strpos($this->line, "PART") !== FALSE) {
                $this->parsePart();
            } elseif (strpos($this->line, "INVITE") !== FALSE) {
                $this->parseInvite();
            } elseif (strpos($this->line, "JOIN") !== FALSE) {
                $this->parseJoins();
            } else {
                echo date("d-m-Y H:i:s") . "  " .$this->line . "\n";
            }
        }

        fclose($this->fp);
    }

    private function join()
    {
        fwrite($this->fp, "JOIN " . $this->chan . "\r\n");
    }

    private function parsePing()
    {
        if (preg_match("/PING :([\S]+)$/", $this->line, $match)) {
            fwrite($this->fp, "PONG " . $match[1] . "\r\n");
            echo(date("d-m-Y H:i:s") . " sent PONG\n");
        } elseif ($this->line == "PING :" . $this->serverHost) {
            fwrite($this->fp, "PONG \r\n");
            echo("sent PONG\n");
        } elseif (preg_match("/:([\S]+)!([\S]+)@([\S]+) PRIVMSG ([\S]+) :PING ([\d]+) ([\d]+)$/i", $this->line, $match)) {
            $userInfo = $this->getUserinfo();
            $response = time() . " " . $match[6];
            $this->sendCTCPresponse($userInfo['nick'], PING, $response);
        } else {
            echo($this->line . "\n");
        }
    }

    private function parseKick()
    {
        if (strpos($this->line, $this->nick) !== FALSE) {
            $this->join();
        }
    }

    private function parseInvite()
    {
        preg_match("/INVITE ([\S]+) :([\S]+)$/i", $this->line, $match);
        $this->joinChannel($match[2]);
    }

    private function parseJoins()
    {
        preg_match("/JOIN :#([\S\s]+)$/i", $this->line, $match);
        $userInfo = $this->getUserinfo();
        $channel = "#" . $match[1];
    }

    private function parseMode()
    {
        echo($this->line . "\n");
        //:Daniel!~sajbar@hemligt-63C3C317.hemligt.net MODE #news +o MyBotZ0r
        preg_match("/MODE #([\S]+) ([\S]+) ([\S]+)$/i", $this->line, $match);
        $userInfo = $this->getUserinfo();
        $channel = "#" . $match[1];
        $mode = $match[2];
        $target = $match[3];
    }

    private function parsePart()
    {
        echo($this->line . "\n");
        preg_match("/PART #([\S]+)([\S\s]*)$/i", $this->line, $match);
        $userInfo = $this->getUserinfo();
        $channel = "#" . $match[1];
    }

    private function joinChannel($channel)
    {
        fwrite($this->fp, "JOIN " . $channel . "\r\n");
    }

    private function parsePrivMsg()
    {
        $userInfo = $this->getUserInfo();
        preg_match("/PRIVMSG ([\S]+) :([\S\s]+)$/i", $this->line, $match);

        //check if it's a channel message or not
        if ($match[1][0] == "#") {
            $this->parseChannelMessage($userInfo['nick'], $match[1], $match[2]);
        } else {//prvate message
            $this->parsePrivateMessage($userInfo['nick'], $match[0]);
        }
    }

    private function parseChannelMessage($nick, $channel, $msg)
    {
        if (preg_match("/^quit([\S\s]*)$/i", $msg, $match)) {
            $quit = "QUIT :" . trim($match[1]) . "\r\n";
            $this->quit = true;
            fwrite($this->fp, $quit);
        } elseif (preg_match("/^loadmodules([\S\s]*)$/i", $msg, $match)) {
            $this->loadModules();
        } elseif (preg_match("/^prut([\S\s]*)$/i", $msg, $match)) {
            $this->sendMessage("PRUUUUUUUUUUT!", $channel);
        } elseif (preg_match("/^fisse([\S\s]*)$/i", $msg, $match)) {
            $this->sendMessage("FISSSSSSSSSSSSSSSEEEEEEEEE!!", $channel);
        } elseif (preg_match("/^!op([\S\s]*)$/i", $msg, $match)) {
            if (trim($match[1]) != "") {
                $this->setMode("+o", $channel, trim($match[1]));
            }
        }
    }

    private function sendMessage($msg, $target)
    {
        $msg = "PRIVMSG " . $target . " :" . trim($msg) . "\r\n";
        fwrite($this->fp, $msg);
    }

    private function parseCTCP($ctcp, $nick)
    {
        if ($ctcp == "VERSION") {
            fwrite($this->fp, "NOTICE " . $nick . " :\001VERSION 1.0 Nanoy's bot\001\r\n");
        } else {
            echo($this->line . "\n");
        }
    }

    private function sendCTCPresponse($nick, $type, $message)
    {
        echo("NOTICE " . $nick . " :\001" . $type . " " . $message . "\001\r\n");
        fwrite($this->fp, "NOTICE " . $nick . " :\001" . $type . " " . $message . "\001\r\n");
    }

    private function parsePrivateMessage($nick, $msg)
    {
        if (strpos($msg, "\001VERSION\001") !== FALSE) {
            fwrite($this->fp, "NOTICE " . $nick . " :\001VERSION 1.0 Nanoy's bot\001\r\n");
        }
    }

    private function getUserinfo()
    {
        preg_match("/^:([\S]+)!([\S]+)@([\S]+) ([\S]+) :([\S\s]+)$/i", $this->line, $match);
        $userInfo['nick'] = $match[1];
        $userInfo['ident'] = $match[2];
        $userInfo['hostname'] = $match[3];
        $userInfo['fullHostInfo'] = $userInfo['nick'] . "!" . $userInfo['ident'] . "@" . $userInfo['hostname'];

        return $userInfo;
    }

    private function setMode($mode, $channel, $param = null)
    {
        if (is_null($param)) {
            $str = "MODE " . $channel . "  " . $mode;
        } else {
            $str = "MODE " . $channel . "  " . $mode . "  " . $param;
        }
        echo($str . "\r\n");
        fwrite($this->fp, $str . "\r\n");
    }

    public function loadModules()
    {
        echo("hello");
        if (is_dir($this->moduledir)) {
            $dh = opendir($this->moduledir);
            if ($dh != false) {
                while (($file = readdir($dh)) !== false) {
                    //include_once $this->moduledir . $file;
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
    $bot->connect();
}
?>