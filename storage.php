<?php

class dhcpStorage {
    function getAttributesForClient($mac) {
        return array(
            'yiaddr' => array(10, 2, 3, 4),
            'subnet_mask' => array(255, 255, 255, 0),
            'router' => array(10, 2, 3, 1),
            'dns_server' => array(10, 2, 3, 1),
            'lease_time' => 86400,
            'domain_name' => 'csh.rit.edu'
        );
    }
}