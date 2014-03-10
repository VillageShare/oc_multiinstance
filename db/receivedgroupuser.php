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

use OCA\AppFramework\Db\Entity;

class ReceivedGroupUser extends Entity{
	
	const CREATED = 0;
	const DELETED = 1;

	public $gid;
	public $uid;
	public $addedAt;
	public $destinationLocation;
	public $status;

	public function __construct($gid, $uid=null, $addedAt=null, $destinationLocation=null, $status=null){
		if ($uid) {
			$this->setGid($gid);
			$this->setUid($uid);
			$this->setAddedAt($addedAt);
			$this->setDestinationLocation($destinationLocation);
			$this->setStatus($status);
		}
		else {
			$this->fromRow($gid);
		}

	}
}
