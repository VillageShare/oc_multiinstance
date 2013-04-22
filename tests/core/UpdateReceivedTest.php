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

class UpdateReceivedTest extends \PHPUnit_Framework_TestCase {

    private $api;
    private $locationMapper;
    private $userUpdateMapper;
    private $receivedUserMapper;
    private $row;

    protected function setUp(){
        $this->api = $this->getMock('OCA\MultiInstance\Core\MultiInstanceAPI', array('prepareQuery', 'getSystemValue', 'getAppValue', 'exec', 'microTime', 'glob', 'fileGetContents', 'filePutContents', 'log', 'baseName'), array('multiinstance'));
        $this->locationMapper = $this->getMock('OCA\Multiinstance\Db\LocationMapper', array('existsByLocation'), array($this->api));

	$this->updateReceived = new UpdateReceived($this->api, null, null, null, null, null, null, $this->locationMapper);
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

}
