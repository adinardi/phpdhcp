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

require_once "storage.php";
require_once "packet.php";

interface dhcpPacketProcessor {
	/*
	 * all methods are optional and should return true if we are to respond, or false if not (no response will be sent if they're not implemented)
	 */
	
	// handleClientDiscover(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response);
	// handleClientRequest(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response);
	// handleClientInform(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response);
	// handleClientDecline(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response);
	// handleClientRelease(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response);
}

class defaultPacketProcessor implements dhcpPacketProcessor {
	function handleClientDiscover(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response) {
		$response->setData('op', dhcpPacket::int2hex(2));
		$response->setData('xid', $packet->getData('xid'));
		$response->setData('yiaddr', dhcpPacket::ip2hex($attributes['yiaddr']));
		$response->setData('chaddr', $packet->getData('chaddr'));

		$response->setData('message_type', 'offer');
		$response->setData('subnet_mask', $attributes['subnet_mask']);
		$response->setData('router', $attributes['router']);
		$response->setData('lease_time', $attributes['lease_time']);
		$response->setData('server_id', array(10, 2, 3, 5));
		$response->setData('dns_server', $attributes['dns_server']);
		
		return true;
	}
	
	function handleClientRequest(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response) {
		$attributes = $storage->getAttributesForClient($packet->getMACAddress());
		
		$response->setData('op', dhcpPacket::int2hex(2));
		$response->setData('xid', $packet->getData('xid'));
		$response->setData('yiaddr', dhcpPacket::ip2hex($attributes['yiaddr']));
		$response->setData('chaddr', $packet->getData('chaddr'));

		$response->setData('message_type', 'ack');
		$response->setData('subnet_mask', $attributes['subnet_mask']);
		$response->setData('router', $attributes['router']);
		$response->setData('lease_time', $attributes['lease_time']);
		$response->setData('server_id', array(10, 2, 3, 5));
		$response->setData('dns_server', $attributes['dns_server']);

		return true;
	}
	
	function handleClientInform(dhcpServer $server, dhcpStorage $storage, dhcpPacket $packet, dhcpPacket $response) {
		$attributes = $storage->getAttributesForClient($packet->getMACAddress());
		
		$response->setData('op', dhcpPacket::int2hex(2));
		$response->setData('xid', $packet->getData('xid'));
		$response->setData('chaddr', $packet->getData('chaddr'));

		$response->setData('message_type', 'ack');
		$response->setData('subnet_mask', $attributes['subnet_mask']);
		$response->setData('router', $attributes['router']);
		$response->setData('server_id', array(10, 2, 3, 5));
		$response->setData('dns_server', $attributes['dns_server']);

		return true;
	}
}

class dhcpRequestProcessor {
	private $packet = null;
	private $response = null;
	private $storage = null;
	
	function __construct(dhcpServer $server, dhcpPacketProcessor $packetProcessor, $storage, $packet)
	{
		$this->dhcpServer = $server;
		$this->packetProcessor = $packetProcessor;
		$this->storage = $storage;
		$this->packet = $packet;
		$response = new dhcpPacket;
		
		$server->verbosity && print('Processing packet type: ' . $packet->getMessageType() . "\n");
		$server->verbosity > 1 && print_r($packet);
		
		switch($packet->getMessageType()) {
			case 'discover':
				$handled = method_exists($this->packetProcessor,'handleClientDiscover') && $this->packetProcessor->handleClientDiscover($this->dhcpServer, $this->storage,$this->packet,$response);
				break;
			
			case 'request':
				$handled = method_exists($this->packetProcessor,'handleClientRequest') && $this->packetProcessor->handleClientRequest($this->dhcpServer, $this->storage,$this->packet,$response);
				break;
			
			case 'decline':
				$handled = method_exists($this->packetProcessor,'handleClientDecline') && $this->packetProcessor->handleClientDecline($this->dhcpServer, $this->storage,$this->packet,$response);
				break;
			
			case 'release':
				$handled = method_exists($this->packetProcessor,'handleClientRelease') && $this->packetProcessor->handleClientRelease($this->dhcpServer, $this->storage,$this->packet,$response);
				break;
			
			case 'inform':
				$handled = method_exists($this->packetProcessor,'handleClientInform') && $this->packetProcessor->handleClientInform($this->dhcpServer, $this->storage,$this->packet,$response);
				break;
				
			default:
				$handled = false;
				break;
		}
		
		$this->response = $handled ? $response : NULL;
	}
	
	function getResponse() {
		if ($this->response) {
			return $this->response;
		} else {
			return null;
		}
	}
}