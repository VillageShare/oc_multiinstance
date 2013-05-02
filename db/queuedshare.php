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


use \OCA\AppFramework\Db\Entity;


class QueuedShare extends Entity {
	
	public $shareType;
	public $shareWith;
	public $uidOwner;
	public $itemType;
	public $fileSourceStorage;
	public $fileSourcePath;
	public $fileTarget;
	public $permissions;
	public $stime;
	public $accepted;
	public $expiration;
	public $token;
	public $destinationLocation;
	public $sendingLocation;
	public $queueType;

	const CREATE = 0;
	const EXPIRATION = 1;
	const ACCEPTED = 2;
	const DELETE = 3;

	//also need parent information
	public function __construct($shareType, $shareWith, $uidOwner, $itemType, $fileSourceStorage, $fileSourcePath, $fileTarget, $permissions, $stime, $token, $destinationLocation, $sendingLocation, $queueType){
		$this->setShareType($shareType);
		$this->setShareWith($shareWith);
		$this->setUidOwner($uidOwner);
		$this->setItemType($itemType);
		$this->setFileSourceStorage($fileSourceStorage);
		$this->setFileSourcePath($fileSourcePath);
		$this->setFileTarget($fileTarget);
		$this->setPermissions($permissions);
		$this->setStime($stime);
		$this->setToken($token);
		$this->setDestinationLocation($destinationLocation);
		$this->setSendingLocation($sendingLocation);
		$this->setQueueType($queueType);
	}

}
