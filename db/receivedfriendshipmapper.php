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

use \OCA\MultiInstance\Db\ReceivedFriendship;
use \OCA\AppFramework\Db\Entity;

class ReceivedFriendshipMapper extends Mapper {



	

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_received_friendships');
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
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `friend_uid1` = ? AND `friend_uid2` = ? AND `updated_at` = ? AND `destination_location = ?';
		$params = array($userId1, $userId2, $updatedAt);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();


		if ($row === false) {
			throw new DoesNotExistException('Friendship with users ' . $userId1 . ' and ' . $userId2 . ' does not exist!');
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException('Friendship with users ' .$userId1 . ' and ' . $userId2 . ' returned more than one result.');
		}
		return new Friendship($row);
	}	

	public function findAll(){
                $sql = "SELECT * FROM {$this->getTableName()}";
                $result = $this->execute($sql);

		$entityList = array();
		while($row = $result->fetchRow()){
			$entity = new ReceivedFriendship($row);
			array_push($entityList, $entity);
		}

		return $entityList;
	}

	public function delete(Entity $receivedFriendship) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE (`friend_uid1` = ? AND `friend_uid2` = ? AND `updated_at` = ? AND `destination_location` = ?)';
		$params = array($receivedFriendship->getFriendUid1(), $receivedFriendship->getFriendUid2(), $receivedFriendship->getUpdatedAt(), $receivedFriendship->getDestinationLocation());

		return $this->execute($sql, $params);
	}

}
