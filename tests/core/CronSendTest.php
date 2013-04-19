<?php

/**
* ownCloud - App Template plugin
*
* @author Bernhard Posselt
* @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\MultiInstance\Lib;

require_once(__DIR__ . "/../classloader.php");


class CronSendTest extends \PHPUnit_Framework_TestCase {

    private $api;
    private $mapper;
    private $row;

    protected function setUp(){
	//getSystemValue
        $this->api = $this->getMock('OCA\AppFramework\Core\Api', array('prepareQuery'), array('multiinstance'));
        //$this->mapper = new CronSend($this->api);
        $this->row = array(
            'id' => 1,
            'user' => 'john',
            'path' => '/test',
            'name' => 'right'
        );

    }


    public function testFindByUserId(){
        $userId = 1;
        $expected = 'SELECT * FROM `*PREFIX*multiinstance_items` WHERE `user` = ?';
	
	//CronSend::dump_queued_users();
    }


}
