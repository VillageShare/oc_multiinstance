<?php
/**
* ownCloud - App Template Example
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


class QueuedUserFacebookId {

	private $uid;
	private $facebookId;
	private $facebookName;
	private $friendsSyncedAt;
	private $destinationLocation;

	public function __construct($uidFromRow, $facebookId=null, $facebookName=null, $syncedAt=null, $destinationLocation=null){
		if($facebookId === null){
			$this->fromRow($uidFromRow);
		}
		else {
			$this->uid = $uidFromRow;
			$this->facebookId = $facebookId;
			$this->facebookName = $facebookName;
			$this->friendsSyncedAt = $syncedAt;
		}
	}

	public function fromRow($row){
		$this->uid = $row['uid'];
		$this->facebookId = $row['facebook_id'];
		$this->facebookName = $row['facebook_name'];
		$this->friendsSyncedAt = $row['friends_synced_at'];
		$this->destinationLocation = $row['destination_location'];
	}


	public function getUid(){
		return $this->uid;
	}

	public function getFacebookId(){
		return $this->facebookId;
	}

	public function getFacebookName(){
		return $this->facebookName;
	}
		
	public function getFriendsSyncedAt(){
		return $this->friendsSyncedAt;
	}


	public function getDestinationLocation(){
		return $this->destinationLocation;
	}
}
