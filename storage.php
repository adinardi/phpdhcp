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