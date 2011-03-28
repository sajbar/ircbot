<?php
$msg = ":Daniel!~sajbar@hemligt-63C3C317.hemligt.net PRIVMSG MyBotZ0r :PING 1234123612 550300";
preg_match ("/:([\S]+)!([\S]+)@([\S]+) PRIVMSG ([\S]+) :PING ([\d]+) ([\d]+)$/i", $msg, $match);
var_dump($match);
?>
