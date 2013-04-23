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

use \OCA\AppFramework\Core\API as API;
use \OCA\AppFramework\Db\Mapper as Mapper;
use \OCA\AppFramework\Db\DoesNotExistException as DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException as MultipleObjectsReturnedException;
use OCA\Friends\Db\AlreadyExistsException as AlreadyExistsException;
use OCA\MultiInstance\Db\QueuedFriendship;

class QueuedFriendshipMapper extends Mapper {



	

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_queued_friendships');
	}



	/**
	 * Finds a friendship  
	 * @param string $userId1: the id of one of the users
	 * @param string $userId2: the id  of the other user
	 * @throws DoesNotExistException: if the item does not exist
	 * @throws MultipleObjectsReturnedException: if more than one friendship with those ids exists
	 * @return a friendship object
	 */
	public function find($userId1, $userId2, $updatedAt, $destinationLocation){
		//$uids = $this->sortUids($userId1, $userId2);
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `friend_uid1` = ? AND `friend_uid2` = ? AND `updated_at` = ? AND `destination_location` = ?';
		$params = array($userId1, $userId2, $updatedAt, $destinationLocation);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();


		if ($row === false) {
			throw new DoesNotExistException("Friendship with users {$userId1} and {$userId2} does not exist!");
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException("Friendship with users  {$userId1} and {$userId2}  returned more than one result.");
		}
		return new QueuedFriendship($row);
	}	


	/** 
	 * Checks to see if a row already exists
	 * @param $userId1 - the first user id
	 * @param $userId2 - the second user id
	 * @return boolean: whether or not it exists (note: will return true if more than one is found)
	 */
	public function exists($userId1, $userId2, $updatedAt, $destinationLocation){
		try{
			//sorted in find
			$f = $this->find($userId1, $userId2, $updatedAt, $destinationLocation);
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
	 * Uid1 and Uid2 must be in the correct order
	 */
	public function save($queuedFriendship) {
		if ($this->exists($queuedFriendship->getFriendUid1(), $queuedFriendship->getFriendUid2(), $queuedFriendship->getUpdatedAt(), $queuedFriendship->getDestinationLocation())){
			return false;
		}
		return $this->insert($queuedFriendship);
	}

}
