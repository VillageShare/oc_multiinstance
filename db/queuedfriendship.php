<?php
/**
* ownCloud - App Template Example
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


class QueuedFriendship extends Entity {

	public $uid1;
	public $uid2;
	public $updatedAt;
	public $status;
	public $destinationLocation;
	public $sendingLocation;

	public function __construct($uid1OrFromRow, $uid2=null, $updatedAt=null, $status=null, $destinationLocation=null){
		if($uid2 === null){
			$this->fromRow($uid1OrFromRow);
		}
		else {
			$this->setUid1($uid1OrFromRow);
			$this->setUid2($uid2);
			$this->setUpdatedAt($updatedAt);
			$this->setStatus($status);
			$this->setDestinationLocation($destinationLocation);
			$this->setSendingLocation($sendingLocation);
		}
	}
}
