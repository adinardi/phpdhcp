<?php

require_once "packet.php";

class dhcpServer {
    private $socket = null;
    private $responseSocket = null;
    
    function __construct() {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($this->socket, "0.0.0.0", 67);
        socket_set_option($this->socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
    }
    
    function listen() {
        while(1) {
            print('waiting...' . "\n");
            $data = socket_read($this->socket, 4608);
            print('got data' . "\n");
            $this->processPacket($data);
        }
    }
    
    function processPacket($packetData) {
        $packet = new dhcpPacket();
        $packet->parse($packetData);
        $processor = new dhcpRequestProcessor($packet);
        
        $response = $processor->getResponse();
        print("sending response" . "\n");
        
        $error = socket_sendto($this->socket, $response, strlen($response), 0, "255.255.255.255", 68);

        print('socket send error: ' . $error . "\n");
    }
}