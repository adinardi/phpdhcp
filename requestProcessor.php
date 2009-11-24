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

class dhcpRequestProcessor {
    private $packet = null;
    private $response = null;
    private $storage = null;
    
    function __construct($storage, $packet) {
        $this->storage = $storage;
        $this->packet = $packet;
        $this->response = new dhcpPacket();
        
        print_r('processing packet ' . $packet->getMessageType() . "\n");
        
        switch($packet->getMessageType()) {
            case 'discover':
                $this->handleClientDiscover();
            break;
            
            case 'request':
                $this->handleClientRequest();
            break;
            
            case 'decline':
            
            break;
            
            case 'release':
            
            break;
            
            case 'inform':
                $this->handleClientInform();
            break;
        }
    }
    
    function handleClientDiscover() {
        $attributes = $this->storage->getAttributesForClient($this->packet->getMACAddress());
        $response = $this->response;
        
        $response->setData('op', dhcpPacket::int2hex(2));
        $response->setData('xid', $this->packet->getData('xid'));
        $response->setData('yiaddr', dhcpPacket::ip2hex($attributes['yiaddr']));
        $response->setData('chaddr', $this->packet->getData('chaddr'));

        $response->setData('message_type', 'offer');
        $response->setData('subnet_mask', $attributes['subnet_mask']);
        $response->setData('router', $attributes['router']);
        $response->setData('lease_time', $attributes['lease_time']);
        $response->setData('server_id', array(10, 2, 3, 5));
        $response->setData('dns_server', $attributes['dns_server']);
    }
    
    function handleClientRequest() {
        $attributes = $this->storage->getAttributesForClient($this->packet->getMACAddress());
        $response = $this->response;
        
        $response->setData('op', dhcpPacket::int2hex(2));
        $response->setData('xid', $this->packet->getData('xid'));
        $response->setData('yiaddr', dhcpPacket::ip2hex($attributes['yiaddr']));
        $response->setData('chaddr', $this->packet->getData('chaddr'));

        $response->setData('message_type', 'ack');
        $response->setData('subnet_mask', $attributes['subnet_mask']);
        $response->setData('router', $attributes['router']);
        $response->setData('lease_time', $attributes['lease_time']);
        $response->setData('server_id', array(10, 2, 3, 5));
        $response->setData('dns_server', $attributes['dns_server']);
    }
    
    function handleClientInform() {
        $attributes = $this->storage->getAttributesForClient($this->packet->getMACAddress());
        $response = $this->response;
        
        $response->setData('op', dhcpPacket::int2hex(2));
        $response->setData('xid', $this->packet->getData('xid'));
        $response->setData('chaddr', $this->packet->getData('chaddr'));

        $response->setData('message_type', 'ack');
        $response->setData('subnet_mask', $attributes['subnet_mask']);
        $response->setData('router', $attributes['router']);
        $response->setData('server_id', array(10, 2, 3, 5));
        $response->setData('dns_server', $attributes['dns_server']);
    }
    
    function getResponse() {
        if ($this->response) {
            return $this->response;
        } else {
            return null;
        }
    }
}