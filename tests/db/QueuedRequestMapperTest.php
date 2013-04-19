<?php

/**
 * ownCloud - App Framework
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


namespace OCA\MultiInstance\Db;

require_once(__DIR__ . "/../classloader.php");

use OCA\Friends\Db\AlreadyExistsException as AlreadyExistsException;

class QueuedRequestMapperTest extends \PHPUnit_Framework_TestCase {

	private $api;

	protected function setUp(){
		$this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getTime'), array('multiinstance'));
		$this->mapper = new QueuedRequestMapper($this->api);
		$this->row4 = array(
			'id' => 1,
			'request_type' => 1,
			'sending_location' => "Macha",
			'field1' => "user5@Macha"
		);
	}


	public function testExistsTrue(){
		$params = array(1, "UCSB", "user5@Macha");

		$expected = 'SELECT * FROM `*PREFIX*multiinstance_queued_requests` WHERE `request_type` = ? AND `destination_location` = ? AND `field1` = ?';

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->at(0))
			->method('fetchRow')
			->will($this->returnValue($this->row4));

		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue($cursor));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$result = $this->mapper->exists(1, "UCSB", "user5@Macha");
		$this->assertEquals(true, $result);
	}

	public function testExistsFalse(){
		$params = array(1, "UCSB", "user5@Macha");

		$expected = 'SELECT * FROM `*PREFIX*multiinstance_queued_requests` WHERE `request_type` = ? AND `destination_location` = ? AND `field1` = ?';

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->at(0))
			->method('fetchRow')
			->will($this->returnValue(false));

		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue($cursor));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$result = $this->mapper->exists(1, "UCSB", "user5@Macha");
		$this->assertEquals(false, $result);
	}

	public function testSaveAlreadyExists(){
		$requestType = 1;
		$destinationLocation = "UCSB";
		$sendingLocation = "Macha";
		$field1 = "user5@Kalene";
		$params = array($requestType, $destinationLocation, $field1);

		$queuedRequestMapper = $this->getMock('OCA\MultiInstance\Db\QueuedRequestMapper', array('exists'), array($this->api));
		$queuedRequestMapper->expects($this->once())
			->method('exists')
			->with($this->equalTo($requestType), $this->equalTo($destinationLocation), $this->equalTo($field1))
			->will($this->returnValue(true));

		$this->api->expects($this->never())
			->method('execute');
	
		$queuedRequest = new QueuedRequest($requestType,  $sendingLocation, "datetime", $destinationLocation, $field1 );

		$result = $queuedRequestMapper->save($queuedRequest);
		$this->assertEquals(true, $result);
		

	}

	public function testSave(){
		$requestType = 1;
		$destinationLocation = "UCSB";
		$sendingLocation = "Macha";
		$field1 = "user5@Kalene";
		$addedAt = "datetime";

		$params = array($requestType, $sendingLocation, $addedAt, $destinationLocation, $field1);
		$expected = 'INSERT INTO `*PREFIX*multiinstance_queued_requests` (`request_type`, `sending_location`, `added_at`, `destination_location`, `field1`) VALUES(?, ?, ?, ?, ?)';
	
		$queuedRequestMapper = $this->getMock('OCA\MultiInstance\Db\QueuedRequestMapper', array('exists'), array($this->api));
		$queuedRequestMapper->expects($this->once())
			->method('exists')
			->with($this->equalTo($requestType), $this->equalTo($destinationLocation), $this->equalTo($field1))
			->will($this->returnValue(false));
		
		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue(true));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));
	
		$queuedRequest = new QueuedRequest($requestType,  $sendingLocation, $addedAt, $destinationLocation, $field1 );

		$result = $queuedRequestMapper->save($queuedRequest);
		$this->assertEquals(true, $result);
		

	}


	public function testDelete(){
		
		$id = 3;
		$params = array($id);
		$expected = 'DELETE FROM `*PREFIX*multiinstance_queued_requests` WHERE `id` = ?';
		
		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue(true));

		$this->api->expects($this->any())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));
	
		$result = $this->mapper->delete($id);

		$this->assertEquals(true, $result);

	}

}
