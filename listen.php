<?php

require_once "packet.php";
require_once "server.php";
require_once "requestProcessor.php";
require_once "storage.php";

$server = new dhcpServer();
$server->listen();