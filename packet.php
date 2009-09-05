<?php

class dhcpPacket {
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
        53 => array('name' => 'message_type', 'type' => 'int'),
        54 => array('name' => 'server_id', 'type' => 'ip'),
        55 => array('name' => 'parameter_request', 'type' => 'binary'),
        57 => array('name' => 'max_message_size', 'type' => 'int'),
        58 => array('name' => 'renewal_time', 'type' => 'int'),
        59 => array('name' => 'rebinding_time', 'type' => 'int'),
        61 => array('name' => 'client_id', 'type' => 'mac')
        );
    public $packetData = array();
    
    function parse($binaryData) {
        $this->packetData = unpack("H2op/H2type/H2hlen/H2hops/H8xid/H4secs/H4flags/H8ciaddr/H8yiaddr/H8siaddr/H8giaddr/H32chaddr/H128sname/H256file/H8magic/H*options", $binaryData);
        
        $optionData = $this->packetData['options'];
        $pos = 0;
        while(strlen($optionData) > $pos) {
            $code = base_convert(substr($optionData, $pos, 2), 16, 10);
            $pos += 2;
            $len = base_convert(substr($optionData, $pos, 2), 16, 10);
            $pos += 2;
            $curoptdata = substr($optionData, $pos, $len*2);
            $pos += $len*2;

            // print_r(array('code'=>$code, 'len'=>$len, 'data'=>$curoptdata));
            $optinfo = dhcpPacket::$options[$code];
            if ($optinfo) {
                $translatedData = null;
                switch($optinfo['type']) {
                    case 'int':
                        $translatedData = base_convert($curoptdata, 16, 10);
                        break;
                    case 'string':
                        $translatedData = $this->hex2string($curoptdata);
                        break;
                    case 'ip':
                        $translatedData = array(
                            $this->hex2int($curoptdata[0] . $curoptdata[1]),
                            $this->hex2int($curoptdata[2] . $curoptdata[3]),
                            $this->hex2int($curoptdata[4] . $curoptdata[5]),
                            $this->hex2int($curoptdata[6] . $curoptdata[7])
                            );
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
    
    function string2hex($str) {
        $hex = '';
        for ($iter = 0; $iter < strlen($str); $iter++)
        {
            $hex .= dechex(ord($str[$iter]));
        }
        return $hex;
    }
    
    function hex2string($hex) {
        $str = '';
        for ($iter = 0; $iter < strlen($hex) - 1; $iter += 2)
        {
            print('hex: ' . $hex[$iter] . $hex[$iter + 1] . ' dec: ' . hexdec($hex[$iter] . $hex[$iter + 1]) . ' char: ' . chr(hexdec($hex[$iter] . $hex[$iter + 1])));
            $str .= chr(hexdec($hex[$iter] . $hex[$iter + 1]));
        }
        return $str;
    }
    
    function hex2int($hex) {
        return base_convert($hex, 16, 10);
    }
}