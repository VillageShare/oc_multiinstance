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

use OCA\MultiInstance\Core\CronTask;

class CronTaskTest extends \PHPUnit_Framework_TestCase {

    private $api;
    private $locationMapper;
    private $userUpdateMapper;
    private $receivedUserMapper;
    private $row;

    protected function setUp(){
        $this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getSystemValue', 'getAppValue', 'exec', 'microTime', 'glob', 'fileGetContents', 'filePutContents', 'log', 'baseName'), array('multiinstance'));
        $this->locationMapper = $this->getMock('OCA\Multiinstance\Db\LocationMapper', array(), array($this->api));
	$this->userUpdateMapper = $this->getMock('OCA\Multiinstance\Db\UserUpdateMapper', array(), array($this->api));	
	$this->receivedUserMapper = $this->getMock('OCA\Multiinstance\Db\ReceivedUserMapper', array(), array($this->api));	
 

        $this->row = array(
            'id' => 1,
            'user' => 'john',
            'path' => '/test',
            'name' => 'right'
        );

	$this->api->expects($this->at(0))
		->method('getSystemValue')
		->with('dbuser')
		->will($this->returnValue('owncloud'));
	$this->api->expects($this->at(1))
		->method('getSystemValue')
		->with('dbpassword')
		->will($this->returnValue('password'));
	$this->api->expects($this->at(2))
		->method('getSystemValue')
		->with('dbname')
		->will($this->returnValue('dev-owncloud'));
	$this->api->expects($this->at(3))
		->method('getSystemValue')
		->with('dbtableprefix')
		->will($this->returnValue('oc_'));
	$this->api->expects($this->at(4))
		->method('getAppValue')
		->with('dbSyncRecvPath')
		->will($this->returnValue("/home-blah-blah/db_sync_recv/"));
	$this->api->expects($this->at(5))
		->method('getAppValue')
		->with($this->equalTo('dbSyncPath'))
		->will($this->returnValue("/home-blah-blah/db_sync/"));


	$this->cronTask = new CronTask($this->api, null, null, null, null, null);
}
    public function testToAckFormat(){
	
	$insert = "INSERT  IGNORE INTO `oc_multiinstance_received_users` VALUES ('Matt@UCSB','kitty','matt',NULL, UCSB)";
	$insertResult = $this->cronTask->toAckFormat($insert, 'multiinstance_queued_users.sql');
	$this->assertEquals("DELETE IGNORE FROM \`oc_multiinstance_queued_users\` WHERE \`uid\` = 'Matt@UCSB' AND \`added_at\` = NULL;\n", $insertResult);

	$insertDate = "INSERT  IGNORE INTO `oc_multiinstance_received_users` VALUES ('Maria@UCSB','','Maria','2013-03-07 23:07:00', UCSB)";
	$insertDateResult = $this->cronTask->toAckFormat($insertDate, 'multiinstance_queued_users.sql');
	$this->assertEquals("DELETE IGNORE FROM \`oc_multiinstance_queued_users\` WHERE \`uid\` = 'Maria@UCSB' AND \`added_at\` = '2013-03-07 23:07:00';\n", $insertDateResult);

	$comment = "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */";
	$commentResult = $this->cronTask->toAckFormat($comment, 'multiinstance_queued_users.sql');
	$this->assertEquals("", $commentResult);

	$sqlNotInsert = "LOCK TABLES `oc_multiinstance_received_users` WRITE";
	$sqlNotInsertResult =$this->cronTask->toAckFormat($sqlNotInsert, 'multiinstance_queued_users.sql');
	$this->assertEquals("", $sqlNotInsertResult);

	$insert = "INSERT  IGNORE INTO `oc_multiinstance_received_friendships` VALUES ('user15@UCSB','user13@Kalene',3,'2013-04-22 20:12:41', 'UCSB', 'Kalene')";
	$insertResult = $this->cronTask->toAckFormat($insert, 'multiinstance_queued_friendships.sql');
	$this->assertEquals("DELETE IGNORE FROM \`oc_multiinstance_queued_friendships\` WHERE \`friend_uid1\` = 'user15@UCSB' AND \`friend_uid2\` = 'user13@Kalene' AND \`updated_at\` = '2013-04-22 20:12:41';\n", $insertResult);

	$insert = "INSERT  IGNORE INTO `oc_multiinstance_received_filecache` VALUES ('/user23@Kalene/','','','httpd/unix-directory',1,-1,1367963523,0,'5189778439299','UCSB',NULL,'2013-05-07 21:52:04',1,'Kalene', 117)";
	$insertResult = $this->cronTask->toAckFormat($insert, 'multiinstance_queued_filecache.sql');
	$this->assertEquals("DELETE IGNORE FROM \`oc_multiinstance_queued_filecache\` WHERE \`storage\` = '/user23@Kalene/' AND \`path\` = '' AND \`added_at\` = '2013-05-07 21:52:04';\n", $insertResult);


    }


	public function testAck() {
		$this->api->expects($this->once())
			->method('microTime')
			->with()
			->will($this->returnValue('1234567'));

		$this->api->expects($this->once())
			->method('exec')
			->with("echo \"my ack string\" >> /home-blah-blah/db_sync/Village1/a1234567");
		
		$this->cronTask->ack("my ack string", "Village1");	
		
	}

	public function testProcessAck() {
		$this->api->expects($this->at(0))
			->method('glob')
			->with($this->equalTo('/home-blah-blah/db_sync_recv/*'), $this->equalTo(true))
			->will($this->returnValue(array('/home-blah-blah/db_sync_recv/Macha', '/home-blah-blah/db_sync_recv/UCSB')));

		$this->api->expects($this->at(1))
			->method('glob')
			->with('/home-blah-blah/db_sync_recv/Macha/a*')
			->will($this->returnValue(array('/home-blah-blah/db_sync_recv/Macha/a1245.6', '/home-blah-blah/db_sync_recv/Macha/a1258.4', '/home-blah-blah/db_sync_recv/Macha/a1260.1', '/home-blah-blah/db_sync_recv/Macha/a1263.7')));


		$this->api->expects($this->at(2))
			->method('fileGetContents')
			->with('/home-blah-blah/db_sync_recv/Macha/last_read.txt')
			->will($this->returnValue("1250.1"));
		
		$this->api->expects($this->at(3))
			->method('fileGetContents')
			->with('/home-blah-blah/db_sync_recv/Macha/last_updated.txt')
			->will($this->returnValue("1263.5"));
		
		$this->api->expects($this->at(4))
			->method('baseName')
			->with('/home-blah-blah/db_sync_recv/Macha/a1245.6')
			->will($this->returnValue("a1245.6"));

		$this->api->expects($this->at(5))
			->method('baseName')
			->with('/home-blah-blah/db_sync_recv/Macha/a1258.4')
			->will($this->returnValue("a1258.4"));
		
		$this->api->expects($this->at(6))
			->method('exec')
			->with('mysql -uowncloud -ppassword dev-owncloud < /home-blah-blah/db_sync_recv/Macha/a1258.4');
			
		$this->api->expects($this->at(7))
			->method('baseName')
			->with('/home-blah-blah/db_sync_recv/Macha/a1260.1')
			->will($this->returnValue('a1260.1'));
	
		$this->api->expects($this->at(8))
			->method('exec')
			->with('mysql -uowncloud -ppassword dev-owncloud < /home-blah-blah/db_sync_recv/Macha/a1260.1');

		$this->api->expects($this->at(9))
			->method('baseName')
			->with('/home-blah-blah/db_sync_recv/Macha/a1263.7')
			->will($this->returnValue('a1263.7'));


		$this->api->expects($this->at(10))
			->method('filePutContents')
			->with('/home-blah-blah/db_sync_recv/Macha/last_read.txt', "1263.5")
			->will($this->returnValue(true));	

		$this->api->expects($this->at(11))
			->method('glob')
			->with('/home-blah-blah/db_sync_recv/UCSB/a*')
			->will($this->returnValue(array()));

		$this->api->expects($this->at(12))
			->method('fileGetContents')
			->with('/home-blah-blah/db_sync_recv/UCSB/last_read.txt')
			->will($this->returnValue('1234.3'));
		
		$this->api->expects($this->at(13))
			->method('fileGetContents')
			->with('/home-blah-blah/db_sync_recv/UCSB/last_updated.txt')
			->will($this->returnValue('1225.3'));

		$this->api->expects($this->at(14))
			->method('filePutContents')
			->with('/home-blah-blah/db_sync_recv/UCSB/last_read.txt', '1225.3')
			->will($this->returnValue(true));

		$this->cronTask->processAcks();
	}
}
