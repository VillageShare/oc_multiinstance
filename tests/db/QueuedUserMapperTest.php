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

class QueuedUserMapperTest extends \PHPUnit_Framework_TestCase {

	private $api;

	protected function setUp(){
		$this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getTime'), array('friends'));
		$this->mapper = new QueuedUserMapper($this->api);
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
			'friend_uid1' => 'thisisuser1',
			'friend_uid2' => 'thisisuser2'
		);
	}

/*

 	public function testFindAllFriendsByUser(){
		$userId = 'thisisuser1';
		$expected = 'SELECT friend_uid2 as friend FROM `*PREFIX*friends_friendships` WHERE friend_uid1 = ?
			UNION
			SELECT friend_uid1 as friend FROM `*PREFIX*friends_friendships` WHERE friend_uid2 = ?';	

		$cursor = $this->getMock('cursor', array('fetchRow'));
		$cursor->expects($this->at(0))
			->method('fetchRow')
			->will($this->returnValue($this->row1));
		$cursor->expects($this->at(1))
			->method('fetchRow')
			->will($this->returnValue($this->row2));
		$cursor->expects($this->at(2))
			->method('fetchRow')
			->will($this->returnValue(false));

		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo(array($userId, $userId)))
			->will($this->returnValue($cursor));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));

		$queuedUsers = $this->mapper->findAllFriendsByUser($userId);

		$this->assertEquals(array('thisisuser2', 'thisisuser3'), $queuedUsers);

 	}

	public function testFind(){
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2';
		$params = array($userId1, $userId2, $userId2, $userId1);

		$expected = 'SELECT * FROM `*PREFIX*friends_friendships` WHERE friend_uid1 = ? AND friend_uid2 = ?
			UNION
			SELECT * FROM `*PREFIX*friends_friendships` WHERE friend_uid1 = ? AND friend_uid2 = ?';

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

		$queuedUser = $this->mapper->find($userId1, $userId2);
		$this->assertEquals($userId1, $queuedUser->getUid1());
		$this->assertEquals($userId2, $queuedUser->getUid2());
	}


	public function testExists(){
		$userId1 = 'thisisuser1';
		$userId2 = 'thisisuser2';
		$params = array($userId2, $userId1);

		$queuedUserMapper = $this->getMock('OCA\Friends\Db\FriendshipMapper', array('find'), array($this->api));
		$queuedUserMapper->expects($this->once())
			->method('find')
			->with($userId1, $userId2)
			->will($this->returnValue(true));
		

		$result = $queuedUserMapper->exists($userId1, $userId2);
		$this->assertEquals(true, $result);
	}

*/
	public function testSave(){

		$uid = 'thisisuser1';
		$displayname = 'thisisuser1name';
		$password = 'password';
		$added_at = 'timestamp';
		$params = array($uid, $displayname, $password, $added_at);
		$expected = 'INSERT INTO `*PREFIX*multiinstance_queued_users` (`uid`, `displayname`, `password`, `added_at`) VALUES(?, ?, ?, ?)';
	
		
		$query = $this->getMock('query', array('execute'));
		$query->expects($this->once())
			->method('execute')
			->with($this->equalTo($params))
			->will($this->returnValue(true));

		$this->api->expects($this->once())
			->method('prepareQuery')
			->with($this->equalTo($expected))
			->will($this->returnValue($query));
	
		$this->api->expects($this->once())
			->method('getTime')
			->with()
			->will($this->returnValue('timestamp'));
	
		$queuedUser = new QueuedUser($uid,  $displayname, $password );

		$result = $this->mapper->save($queuedUser);
		$this->assertEquals(true, $result);
		

	}

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

}
