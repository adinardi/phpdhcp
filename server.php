<?php
/*
  Copyright 2009 Angelo R. DiNardi (angelo@dinardi.name)
 
  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at
 
    http://www.apache.org/licenses/LICENSE-2.0
 
  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

require_once "packet.php";

class dhcpServer {
    private $socket = null;
    private $responseSocket = null;
    private $storage = null;
    
    function __construct() {
        $this->storage = new dhcpStorage();
        
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
        $processor = new dhcpRequestProcessor($this->storage, $packet);
        
        $responsePacket = $processor->getResponse();
        $responseData = $responsePacket->build();
        print("sending response" . "\n");
        $ciaddr = $packet->getClientAddress();
        if ($ciaddr == '0.0.0.0') {
            print("Switching to broadcast address...\n");
            $ciaddr = '255.255.255.255';
        }
        print("attempting to send packet to " . $ciaddr . "\n");
        $error = socket_sendto($this->socket, $responseData, strlen($responseData), 0, $ciaddr, 68);
        if ($error === FALSE) {
            print("send failed for specific address, broadcast.\n");
            $error = socket_sendto($this->socket, $responseData, strlen($responseData), 0, "255.255.255.255", 68);
        }
        print('socket send error: ' . $error . "\n");
    }
}