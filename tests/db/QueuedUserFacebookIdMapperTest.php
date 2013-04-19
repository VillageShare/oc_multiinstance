<?php

/**
 * ownCloud - App Framework
 *
 * @author Sarah Jones 
 * @copyright 2013 Sarah Jones sarah.e.p.jones@gmail.com
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

class QueuedUserFacebookIdMapperTest extends \PHPUnit_Framework_TestCase {

	private $api;

	protected function setUp(){
		$this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getTime'), array('friends'));
		$this->mapper = new QueuedUserFacebookIdMapper($this->api);
		$this->row1 = array(
			//'friend_uid1' => 'thisisuser1',
			//'friend_uid2' => 'thisisuser2'
			'friend' => 'thisisuser2'
		);
		$this->row2 = array(
			//'friend_uid1' => 'thisisuser3',
			//'friend_uid2' => 'thisisuser1'
			'friend' => 'thisisuser3'
		);
		$this->row3 = array(
			'uid' => 'thisisuser1',
			'facebook_id' => '12345',
			'facebook_name' => 'user1',
			'friends_synced_at' => 'timestamp'
		);
	}

	public function testSave(){

		$uid = 'thisisuser1';
		$facebookId = '12345';
		$facebookName = 'thisisuser1name';
		$syncedAt = 'timestamp';
		$params = array($uid, $facebookId, $facebookName, $syncedAt);
		$expected = 'INSERT INTO `*PREFIX*multiinstance_queued_user_facebook_ids` (`uid`, `facebook_id`, `facebook_name`, `friends_synced_at`) VALUES(?, ?, ?, ?)';
	
		$mapper = $this->getMock('OCA\MultiInstance\Db\QueuedUserFacebookIdMapper', array('exists'), array($this->api));
		
		$mapper->expects($this->once())
			->method('exists')
			->with($uid, $syncedAt)
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
	
	
		$queuedUserFacebookId = new QueuedUserFacebookId($uid, $facebookId, $facebookName, $syncedAt);

		$result = $mapper->save($queuedUserFacebookId);
		$this->assertEquals(true, $result);
		

	}

	public function testFind(){
		$uid = 'thisisuser1';
		$syncedAt = 'timestamp';
		$params = array($uid, $syncedAt);

		$expected = 'SELECT * FROM `*PREFIX*multiinstance_queued_user_facebook_ids` WHERE `uid` = ? AND `friends_synced_at` = ?';

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->at(0))
			->method('fetchRow')
			->will($this->returnValue($this->row3));
		$cursor->expects($this->at(1))
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

		$queuedUserFacebookId = $this->mapper->find($uid, $syncedAt);
		$this->assertEquals($uid, $queuedUserFacebookId->getUid());
		$this->assertEquals($syncedAt, $queuedUserFacebookId->getFriendsSyncedAt());
		$this->assertEquals('user1', $queuedUserFacebookId->getFacebookName());
		$this->assertEquals('12345', $queuedUserFacebookId->getFacebookId());
	}


	public function testExists(){
		$uid = 'thisisuser1';
		$syncedAt = 'timestamp';
		$params = array($uid, $syncedAt);

		$queuedUserFacebookIdMapper = $this->getMock('OCA\MultiInstance\Db\QueuedUserFacebookIdMapper', array('find'), array($this->api));
		$queuedUserFacebookIdMapper->expects($this->once())
			->method('find')
			->with($uid, $syncedAt)
			->will($this->returnValue(true));
		

		$result = $queuedUserFacebookIdMapper->exists($uid, $syncedAt);
		$this->assertEquals(true, $result);
	}
/*
	public function testDelete(){
		
		$uid = 'thisisuser1';
		$added_at = 'timestamp';
		$params = array($uid, $added_at);
		$expected = 'DELETE FROM `*PREFIX*multiinstance_queued_users` WHERE `uid` = ? AND `added_at` = ?';
		
		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue(true));

		$this->api->expects($this->any())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));
	
		$result = $this->mapper->delete($uid, $added_at);

		$this->assertEquals(true, $result);

	}
*/
}
