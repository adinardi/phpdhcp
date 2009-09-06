<?php

class dhcpRequestProcessor {
    private $packet = null;
    private $response = null;
    
    function __construct($packet) {
        $this->packet = $packet;
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
            
            break;
        }
    }
    
    function handleClientDiscover() {
        $response = new dhcpPacket();
        $response->setData('op', dhcpPacket::int2hex(2));
        $response->setData('xid', $this->packet->getData('xid'));
        $response->setData('yiaddr', dhcpPacket::ip2hex(array(10, 2, 3, 4)));
        $response->setData('chaddr', $this->packet->getData('chaddr'));

        $response->setData('message_type', 'offer');
        $response->setData('subnet_mask', array(255,255,255,0));
        $response->setData('router', array(10, 2, 3, 1));
        $response->setData('lease_time', 86400);
        $response->setData('server_id', array(10, 2, 3, 5));
        $response->setData('dns_server', array(10, 2, 3, 1));
        
        $this->response = $response;
    }
    
    function handleClientRequest() {
        $response = new dhcpPacket();
        $response->setData('op', dhcpPacket::int2hex(2));
        $response->setData('xid', $this->packet->getData('xid'));
        $response->setData('yiaddr', dhcpPacket::ip2hex(array(10, 2, 3, 4)));
        $response->setData('chaddr', $this->packet->getData('chaddr'));

        $response->setData('message_type', 'ack');
        $response->setData('subnet_mask', array(255,255,255,0));
        $response->setData('router', array(10, 2, 3, 1));
        $response->setData('lease_time', 86400);
        $response->setData('server_id', array(10, 2, 3, 5));
        $response->setData('dns_server', array(10, 2, 3, 1));
        
        $this->response = $response;
    }
    
    function getResponse() {
        if ($this->response) {
            return $this->response->build();
        } else {
            return null;
        }
    }
}