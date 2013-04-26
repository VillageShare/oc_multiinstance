<?php
/**
* ownCloud - MultiInstance App
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

use OCA\AppFramework\Db\Entity;

class ReceivedUserFacebookId extends Entity {

	public $uid;
	public $facebookId;
	public $facebookName;
	public $friendsSyncedAt;
	public $destinationLocation;

	public function __construct($uidFromRow, $facebookId=null, $facebookName=null, $syncedAt=null, $destinationLocation=null){
		if($facebookId === null){
			$this->fromRow($uidFromRow);
		}
		else {
			$this->setUid($uidFromRow);
			$this->setFacebookId($facebookId);
			$this->setFacebookName($facebookName);
			$this->setFriendsSyncedAt($syncedAt);
			$this->setDestinationLocation($destinationLocation);
		}
	}
}
