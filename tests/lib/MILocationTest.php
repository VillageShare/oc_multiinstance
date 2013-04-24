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

use OCA\MultiInstance\Db\Location;
use OCA\MultiInstance\Db\QueuedFriendship;
use OCA\MultiInstance\Db\QueuedUserFacebookId;

class MILocationTest extends \PHPUnit_Framework_TestCase {

    private $api;
    private $mapper;
    private $row;

    protected function setUp(){
        $this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getAppValue', 'log'), array('multiinstance'));

    }

	public function testUidContainsLocation(){
		$row1 = array(
			'id' => 1,
			'location' => 'UCSB',
			'ip' => '192.168.56.101'
		);
		$row2 = array(
			'id' => 2,
			'location' => 'Macha',
			'ip' => '192.168.56.102'
		);
		$locations = array(
			new Location($row1),
			new Location($row2)	
		);
		$this->mock = $this->getMock('OCA\MultiInstance\Lib\MILocation', array('getLocations'));
		$this->locationMapper = $this->getMock('OCA\MultiInstance\Db\LocationMapper', array('existsByLocation'), array($this->api));

		$this->mock->staticExpects($this->any())
			->method('getLocations')
			->will($this->returnValue($locations));
		$this->locationMapper->expects($this->at(0))
			->method('existsByLocation')
			->will($this->returnValue(true));
		$this->locationMapper->expects($this->at(1))
			->method('existsByLocation')
			->will($this->returnValue(true));
		$this->locationMapper->expects($this->at(2))
			->method('existsByLocation')
			->will($this->returnValue(false));

		$contains1 = MILocation::uidContainsLocation("Sarah@UCSB", $this->locationMapper); 
		$this->assertEquals(true, $contains1);

		$contains2 = MILocation::uidContainsLocation("Sarah@Me@Macha", $this->locationMapper);
		$this->assertEquals(true, $contains2);
		
		$notContain = MILocation::uidContainsLocation("Sarah@Kalene", $this->locationMapper);
		$this->assertEquals(false, $notContain);

		$notContain2 = MILocation::uidContainsLocation("Sarah", $this->locationMapper);
		$this->assertEquals(false, $notContain);
	
	}

	public function testUidContainsThisLocation(){

		$this->api->expects($this->any())
			->method('getAppValue')
			->with('location')
			->will($this->returnValue("UCSB"));

		$contains1 = MILocation::uidContainsThisLocation("Sarah@UCSB", $this->api); 
		$this->assertEquals(true, $contains1);

		$contains2 = MILocation::uidContainsThisLocation("Sarah@Me@UCSB", $this->api);
		$this->assertEquals(true, $contains2);
		
		$notContain = MILocation::uidContainsThisLocation("Sarah@Kalene", $this->api);
		$this->assertEquals(false, $notContain);

		$notContain2 = MILocation::uidContainsThisLocation("Sarah", $this->api);
		$this->assertEquals(false, $notContain);
	
	}

	public function testCreateQueuedFriendship(){
		$queuedFriendshipMapper = $this->getMock('OCA\MultiInstance\Db\QueuedFriendshipMapper', array('save'), array($this->api));

		$queuedFriendship = new QueuedFriendship("user1", "user2", "timestamp", 1);
		$queuedFriendshipMapper->expects($this->once())
			->method('save')
			->with($queuedFriendship)
			->will($this->returnValue(true));
		
		$this->assertEquals(true, MILocation::createQueuedFriendship("user1", "user2", "timestamp", 1, $queuedFriendshipMapper));
	}

	public function testCreateQueuedUserFacebookId(){
		$queuedUserFacebookIdMapper = $this->getMock('OCA\MultiInstance\Db\QueuedUserFacebookIdMapper', array('save'), array($this->api));
		$queuedUserFacebookId = new QueuedUserFacebookId("user1", "12345", "User", "timestamp");
		$queuedUserFacebookIdMapper->expects($this->once())
			->method('save')
			->with($queuedUserFacebookId)
			->will($this->returnValue(true));
		$this->assertEquals(true, MILocation::createQueuedUserFacebookId("user1", "12345", "User", "timestamp", $queuedUserFacebookIdMapper));
		
	}

	public function testRemovePathFromStorage() {
		$storage = 'local::/home/sjones/public_html/dev/owncloud/data/admin@Macha/';
		$result = MILocation::removePathFromStorage($storage);
		$this->assertEquals('data/admin@Macha/', $result);

		$storage2 = 'local::/home/sjones/public_html/dev/data/owncloud/data/admin@Macha/';
		$result2 = MILocation::removePathFromStorage($storage2);
		$this->assertEquals('data/admin@Macha/', $result2);

		$this->api->expects($this->once())
			->method('log');

		$storage3 = 'local::/home/sjones/public_html/dev/owncloud/admin@Macha/';
		$result3 = MILocation::removePathFromStorage($storage3, $this->api);
		$this->assertEquals(null, $result3);
		
	}
}
