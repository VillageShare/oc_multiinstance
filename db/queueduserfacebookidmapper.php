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

use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Db\Mapper;
use \OCA\AppFramework\Db\DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException;


class QueuedUserFacebookIdMapper extends Mapper {



	

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_queued_user_facebook_ids');
	}

	public function find($uid, $syncedAt, $destinationLocation){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `uid` = ? AND `friends_synced_at` = ? AND `destination_location` = ?';
		$params = array($uid, $syncedAt, $destinationLocation);
		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException("UserFacebookId with uid {$uid} and syncedAt {$syncedAt} and destinationLocation {$destinationLocation}  does not exist!");
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException("UserFacebookId with uid {$uid} and syncedAt {$syncedAt} and destinationLocation {$destinationLocation}  returned more than one result.");
		}
		return new QueuedUserFacebookId($row);

	}

	/** 
	 * Checks to see if a row already exists
	 * @param $uid - the owncloud uid of a user
	 * @return boolean: whether or not it exists (note: will return true if more than one is found)
	 */
	public function exists($uid, $syncedAt, $destinationLocation){
		try{
			$this->find($uid, $syncedAt, $destinationLocation);
		}
		catch (DoesNotExistException $e){
			return false;
		}
		catch (MultipleObjectsReturnedException $e){
			return true;
		}
		return true;
	}

	/**
	 * 
	 * @param  $userFacebookId: the UserFacebookId object to be saved
	 * @return true if successful
	 */
	public function save($userFacebookId){
		if ($this->exists($userFacebookId->getUid(), $userFacebookId->getFriendsSyncedAt()), $userFacebookId()->getDestinationLocation()){
			return false;
		}
		$sql = 'INSERT INTO `'. $this->getTableName() . '` (`uid`, `facebook_id`, `facebook_name`, `friends_synced_at`, `destination_location`)'.
				' VALUES(?, ?, ?, ?, ?)';

		$params = array(
			$userFacebookId->getUid(),
			$userFacebookId->getFacebookId(),
			$userFacebookId->getFacebookName(),
			$userFacebookId->getFriendsSyncedAt(),
			$userFacebookId->getDestinationLocation()
		);

		return $this->execute($sql, $params);
	}

	/**
	 * 
	 * @param 
	 * @param 
	 */
	public function delete($userId, $syncedAt, $destinationLocation){
		
		//must check both ways to delete friend
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE (`uid` = ? AND `friends_synced_at` = ? AND `destination_location` = ?)';
		$params = array($uid, $syncedAt, $destinationLocation);
		
		return $this->execute($sql, $params);
	
	}


}
