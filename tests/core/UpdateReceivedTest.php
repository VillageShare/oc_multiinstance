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

use OCA\MultiInstance\Core\UpdateReceived;
use OCA\MultiInstance\Db\ReceivedFriendship;
use OCA\MultiInstance\Db\QueuedUser;
use OCA\MultiInstance\Db\QueuedFriendship;
use OCA\MultiInstance\Db\UserUpdate;
use OCA\Friends\Db\Friendship;
use OCA\AppFramework\Db\DoesNotExistException;

class UpdateReceivedTest extends \PHPUnit_Framework_TestCase {

    private $api;
    private $locationMapper;
    private $userUpdateMapper;
    private $receivedUserMapper;
    private $row;

    protected function setUp(){
        $this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getSystemValue', 'getAppValue', 'exec', 'microTime', 'glob', 'fileGetContents', 'filePutContents', 'log', 'baseName', 'getDisplayName', 'getPassword', 'beginTransaction', 'commit', 'userExists'), array('multiinstance'));
        $this->locationMapper = $this->getMock('OCA\Multiinstance\Db\LocationMapper', array('existsByLocation'), array($this->api));
	$this->receivedFriendshipMapper = $this->getMock('OCA\MultiInstance\Db\ReceivedFriendshipmapper', array('findAll', 'delete'), array($this->api));
	$this->friendshipMapper = $this->getMock('OCA\Friends\Db\FriendshipMapper', array('find', 'insert'), array($this->api));
	$this->queuedFriendshipMapper = $this->getMock('OCA\MultiInstance\Db\QueuedFriendshipMapper', array('save'), array($this->api));
	$this->queuedUserMapper = $this->getMock('OCA\MultiInstance\Db\QueuedUserMapper', array('save'), array($this->api));
	$this->userUpdateMapper = $this->getMock('OCA\MultiInstance\Db\UserUpdateMapper', array('find'), array($this->api));
	

	//$api, $receivedUserMapper, $userUpdateMapper, $receivedFriendshipMapper, $userFacebookIdMapper, $receivedUserFacebookIdMapper, $friendshipMapper, $queuedFriendshipMapper, $queuedUserMapper, $locationMapper
	$this->updateReceived = new UpdateReceived($this->api, null, $this->userUpdateMapper, $this->receivedFriendshipMapper, null, null, $this->friendshipMapper, $this->queuedFriendshipMapper, $this->queuedUserMapper, $this->locationMapper);
}
    public function testGetUidLocation(){
		
	$this->locationMapper->expects($this->at(0))
		->method('existsByLocation')
		->with("Macha")
		->will($this->returnValue(true));

	$this->locationMapper->expects($this->at(1))
		->method('existsByLocation')
		->with("UCSB")
		->will($this->returnValue(true));
	
	$this->locationMapper->expects($this->at(2))
		->method('existsByLocation')
		->with("fakey")
		->will($this->returnValue(false));
	
	$result = $this->updateReceived->getUidLocation("user5");
	$this->assertEquals(null, $result);

	$result = $this->updateReceived->getUidLocation("user5@Macha");
	$this->assertEquals("Macha", $result);

	$result = $this->updateReceived->getUidLocation("user5@cool@UCSB");
	$this->assertEquals("UCSB", $result);

	$result = $this->updateReceived->getUidLocation("user5@cool@fakey");
	$this->assertEquals(null, $result);
    }



	//At Village 1, a user from UCSB makes a friend request to a user in Village 2; processing at UCSB
    public function testUpdateFriendshipsWithReceivedFriendshipsCaseG() {
	$receivedFriendship = new ReceivedFriendship('user1@Macha', 'user2@UCSB', '2013-03-25 22:14:00', 4, 'UCSB', 'Kalene');
	$this->receivedFriendshipMapper->expects($this->once())
		->method('findAll')
		->with()
		->will($this->returnValue(array($receivedFriendship)));

	$this->api->expects($this->at(0))
		->method('getAppValue')
		->with('centralServer')
		->will($this->returnValue('UCSB'));
	$this->api->expects($this->at(1))
		->method('getAppValue')
		->with('location')
		->will($this->returnValue('UCSB'));

	$this->locationMapper->expects($this->any())
		->method('existsByLocation')
		->will($this->returnValue(true));

	$this->api->expects($this->once())
		->method('getDisplayName')
		->with('user2@UCSB')
		->will($this->returnValue('user2@UCSB'));
	$this->api->expects($this->once())
		->method('getPassword')
		->with('user2@UCSB')
		->will($this->returnValue('password'));

	$userUpdate = new UserUpdate('user2@UCSB', '2013-03-24 03:04:05');
	$queuedUser = new QueuedUser('user2@UCSB', 'user2@UCSB', 'password', '2013-03-24 03:04:05', 'Macha');	
	$queuedFriendship = new QueuedFriendship('user1@Macha', 'user2@UCSB', '2013-03-25 22:14:00', 4, 'Macha', 'UCSB');
	$friendship = new Friendship();
	$friendship->setFriendUid1('user1@Macha');
	$friendship->setFriendUid2('user2@UCSB');
	$friendship->setStatus(4);
	$friendship->setUpdatedAt('2013-03-25 22:14:00');

	$this->userUpdateMapper->expects($this->once())
		->method('find')
		->with('user2@UCSB')
		->will($this->returnValue($userUpdate));

	$this->queuedUserMapper->expects($this->once())
		->method('save')
		->with($this->equalTo($queuedUser));

	$this->queuedFriendshipMapper->expects($this->once())
		->method('save')
		->with($this->equalTo($queuedFriendship));

	$this->friendshipMapper->expects($this->once())
		->method('find')
		->with('user1@Macha', 'user2@UCSB')
		->will($this->throwException(new DoesNotExistException("")));

	$this->friendshipMapper->expects($this->once())
		->method('insert')
		->with($this->equalTo($friendship));

	$this->receivedFriendshipMapper->expects($this->once())
		->method('delete')
		->with($this->equalTo($receivedFriendship));

	$this->updateReceived->updateFriendshipsWithReceivedFriendships($this->locationMapper);
    }

}
