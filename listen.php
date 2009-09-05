<?php

$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_bind($socket, "0.0.0.0", 67);
socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
while(1) {
    $data = socket_read($socket, 4608);
    // print_r(unpack("H*data", $data));

    $hexData = unpack("H2op/H2type/H2hlen/H2hops/H8xid/H4secs/H4flags/H8ciaddr/H8yiaddr/H8siaddr/H8giaddr/H32chaddr/H128sname/H256file/H8magic/H*options", $data);
    print_r($hexData);
    parse_options($hexData['options']);
}

function parse_options($optdata) {
    $data = $optdata;
    $options = array();
    $pos = 0;
    while(strlen($data) > $pos) {
        $code = base_convert(substr($data, $pos, 2), 16, 10);
        $pos += 2;
        $len = base_convert(substr($data, $pos, 2), 16, 10);
        $pos += 2;
        $curoptdata = substr($data, $pos, $len*2);
        $pos += $len*2;
        
        print_r(array('code'=>$code, 'len'=>$len, 'data'=>$curoptdata));
    }
}