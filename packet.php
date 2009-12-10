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

class dhcpPacket {
	const BOOTREPLY = '02';
	const BOOTREQUEST = '01';
	
  // (012) DHO_HOST_NAME
  // (014) DHO_MERIT_DUMP
  // (015) DHO_DOMAIN_NAME
  // (017) DHO_ROOT_PATH
  // (018) DHO_EXTENSIONS_PATH
  // (047) DHO_NETBIOS_SCOPE
  // (056) DHO_DHCP_MESSAGE
  // (060) DHO_VENDOR_CLASS_IDENTIFIER
  // (062) DHO_NWIP_DOMAIN_NAME
  // (064) DHO_NIS_DOMAIN
  // (065) DHO_NIS_SERVER
  // (067) DHO_BOOTFILE
  // (086) DHO_NDS_TREE_NAME
  // (098) DHO_USER_AUTHENTICATION_PROTOCOL
  
    public static $options = array(
        1 => array('name' => 'subnet_mask', 'type' => 'ip'),
        2 => array('name' => 'time_offset', 'type' => 'int'),
        3 => array('name' => 'router', 'type' => 'ip'),
        6 => array('name' => 'dns_server', 'type' => 'ip'),
        12 => array('name' => 'host_name', 'type' => 'string'),
        15 => array('name' => 'domain_name', 'type' => 'string'),
        28 => array('name' => 'broadcast_address', 'type' => 'ip'),
        50 => array('name' => 'requested_ip_address', 'type' => 'ip'),
        51 => array('name' => 'lease_time', 'type' => 'int'),
        53 => array('name' => 'message_type', 'type' => 'messageType'),
        54 => array('name' => 'server_id', 'type' => 'ip'),
        55 => array('name' => 'parameter_request', 'type' => 'binary'),
        57 => array('name' => 'max_message_size', 'type' => 'int'),
        58 => array('name' => 'renewal_time', 'type' => 'int'),
        59 => array('name' => 'rebinding_time', 'type' => 'int'),
        61 => array('name' => 'client_id', 'type' => 'mac'),
		60 => array('name' => 'vendor_class_identifier', 'type' => 'string'),
		66 => array('name' => 'tftp_server', 'type' => 'string'),
        91 => array('name' => 'client-last-transaction-time', 'type' => 'int'),
        92 => array('name' => 'associated-ip', 'type' => 'string')
    );
    public static $messageTypes = array(
        1 => 'discover',
        2 => 'offer',
        3 => 'request',
        4 => 'decline',
        5 => 'ack',
        6 => 'nak',
        7 => 'release',
        8 => 'inform',
        10 => 'DHCPLEASEQUERY',
        11 => 'DHCPLEASEUNASSIGNED',
        12 => 'DHCPLEASEUNKNOWN',
        13 => 'DHCPLEASEACTIVE'
    );
    public $packetData = array(
        'op' => '02',
        'htype' => '01',
        'hlen' => '06',
        'hops' => '00',
        'xid' => '00000000',
        'secs' => '0000',
        'flags' => '0000',
        'ciaddr' => '00000000',
        'yiaddr' => '00000000',
        'siaddr' => '00000000',
        'giaddr' => '00000000',
        'chaddr' => '00000000000000000000000000000000',
        'sname' => '00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        'file' => '0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
        'magic' => '63825363'
    );
    
    function setData($key, $value) {
        $this->packetData[$key] = $value;
    }
    
    function getData($key) {
        return $this->packetData[$key];
    }
    
    function getMessageType() {
        return $this->packetData['message_type'];
    }
    
    function getClientAddress() {
        $ciaddr = $this->getData('ciaddr');
        $ciaddr = self::hex2ip($ciaddr);
        return $ciaddr[0] . '.' .
            $ciaddr[1] . '.' .
            $ciaddr[2] . '.' .
            $ciaddr[3];
    }
    
    function getMACAddress() {
        $chaddr = $this->getData('chaddr');
        $mac = substr($chaddr, 0, 12);
        return $mac;
    }
    
    function parse($binaryData) {
        $this->packetData = unpack("H2op/H2htype/H2hlen/H2hops/H8xid/H4secs/H4flags/H8ciaddr/H8yiaddr/H8siaddr/H8giaddr/H32chaddr/H128sname/H256file/H8magic/H*options", $binaryData);
        
        $optionData = $this->packetData['options'];
        $pos = 0;
        while(strlen($optionData) > $pos) {
            $code = base_convert(substr($optionData, $pos, 2), 16, 10);
            $pos += 2;
            $len = base_convert(substr($optionData, $pos, 2), 16, 10);
            $pos += 2;
            $curoptdata = substr($optionData, $pos, $len*2);
            $pos += $len*2;

            $optinfo = @self::$options[$code];
            if ($optinfo) {
                $translatedData = null;
                switch($optinfo['type']) {
                    case 'int':
                        $translatedData = base_convert($curoptdata, 16, 10);
                        break;
                    case 'string':
                        $translatedData = self::hex2string($curoptdata);
                        break;
                    case 'ip':
                        $translatedData = self::hex2ip($curoptdata);
                        break;
                    case 'messageType':
                        $translatedData = self::$messageTypes[self::hex2int($curoptdata)];
                        break;
                    default:
                        $translatedData = $curoptdata;
                        break;
                }
                $this->packetData[$optinfo['name']] = $translatedData;
            } else {
                $this->packetData[$code] = $curoptdata;
            }
        }
    }
    
    function build() {
        $p = $this->packetData;
        
        $optionsData = '';
        foreach(self::$options as $optcode => $optinfo) {
            if (isset($this->packetData[$optinfo['name']])) {
                $itemdata = $this->packetData[$optinfo['name']];
                $code = self::int2hex($optcode);
                
                switch($optinfo['type']) {
                    case 'int':
                        $translatedData = self::int2hex($itemdata);
                        break;
                    case 'string':
                        $translatedData = self::string2hex($itemdata);
                        break;
                    case 'ip':
                        $translatedData = self::ip2hex($itemdata);
                        break;
                    case 'messageType':
                        $translatedData = self::int2hex(array_search($itemdata, self::$messageTypes));
                        break;
                    default:
                        $translatedData = $itemdata;
                        break;
                }
                
                // print($code . ' ' . $translatedData . ' ' . self::int2hex(strlen($translatedData)/2) . "\n");
                $optionsData .= $code . self::int2hex(strlen($translatedData)/2) . $translatedData;
            }
        }
        $optionsData .= self::int2hex(255);
        // $optionsData .= self::int2hex(1);
        $optionsData .= '00';
        // print_r($p);
        // print($optionsData);
        $data = pack("H2H2H2H2H8H4H4H8H8H8H8H32H128H256H8H*",
            $p['op'],
            $p['htype'],
            $p['hlen'],
            $p['hops'],
            $p['xid'],
            $p['secs'],
            $p['flags'],
            $p['ciaddr'],
            $p['yiaddr'],
            $p['siaddr'],
            $p['giaddr'],
            $p['chaddr'],
            $p['sname'],
            $p['file'],
            $p['magic'],
            $optionsData
        );
        
        return $data;
    }
    
    public static function ip2hex($ip) {
        return self::int2hex($ip[0]) .
            self::int2hex($ip[1]) .
            self::int2hex($ip[2]) .
            self::int2hex($ip[3]);
    }
    
    public static function hex2ip($hex) {
        return array(
            self::hex2int($hex[0] . $hex[1]),
            self::hex2int($hex[2] . $hex[3]),
            self::hex2int($hex[4] . $hex[5]),
            self::hex2int($hex[6] . $hex[7])
        );
    }
    
    public static function string2hex($str) {
        $hex = '';
        for ($iter = 0; $iter < strlen($str); $iter++) {
            $hex .= dechex(ord($str[$iter]));
        }
        return $hex;
    }
    
    public static function hex2string($hex) {
        $str = '';
        for ($iter = 0; $iter < strlen($hex) - 1; $iter += 2) {
            $str .= chr(hexdec($hex[$iter] . $hex[$iter + 1]));
        }
        return $str;
    }
    
    public static function hex2int($hex) {
        return base_convert($hex, 16, 10);
    }
    
    public static function int2hex($int) {
        $hex = base_convert($int, 10, 16);
        // TODO: This is a quick hack. Fix this.
        if (strlen($hex) == 1) {
            $hex = '0' . $hex;
        } else if (strlen($hex) == 3) {
            $hex = '0' . $hex;
        } else if (strlen($hex) == 5) {
            $hex = '000' . $hex;
        } else if (strlen($hex) == 7) {
            $hex = '0' . $hex;
        }
        return '' . $hex;
    }
}