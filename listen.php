<?php

require_once "packet.php";
require_once "server.php";
require_once "requestProcessor.php";

$server = new dhcpServer();
$server->listen();