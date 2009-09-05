<?php

require_once "packet.php";

$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_bind($socket, "0.0.0.0", 67);
socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
while(1) {
    $data = socket_read($socket, 4608);
    // print_r(unpack("H*data", $data));
    $packet = new dhcpPacket();
    $packet->parse($data);
    print_r($packet->packetData);
}