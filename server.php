<?php

require_once "packet.php";

class dhcpServer {
    private $socket = null;
    private $responseSocket = null;
    
    function __construct() {
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($this->socket, "0.0.0.0", 67);
        socket_set_option($this->socket, SOL_SOCKET, SO_BROADCAST, 1);
        
        $this->responseSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($this->responseSocket, "0.0.0.0", 68);
        socket_set_option($this->responseSocket, SOL_SOCKET, SO_BROADCAST, 1);
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
        socket_sendto($this->responseSocket, $response, strlen($response), 0, "255.255.255.255", 68);
    }
}