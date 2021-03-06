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

use \OCA\AppFramework\Core\API;
use \OCA\AppFramework\Db\Mapper;
use \OCA\AppFramework\Db\DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException;
use OCA\AppFramework\Db\Entity;

class ReceivedDeactivatedUserMapper extends Mapper {



	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_received_deactivatedusers');
	}

	/**
	 * Finds an item by id
	 * @throws DoesNotExistException: if the item does not exist
	 * @return the item
	 */
	public function find($uid, $addedAt, $destinationLocation){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `uid` = ? AND `added_at` = ? AND `destination_location` = ?';
		$params = array($uid, $addedAt, $destinationLocation);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException("ReceivedDeactivatedUser with uid {$uid} and addedAt = {$addedAt} and destinationLocation does not exist!");
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException("ReceivedDeactivatedUser with uid {$uid} and addedAt = {$addedAt} and destinationLocation returned more than one result.");
		}
		return new ReceivedDeactivatedUser($row);

	}

	public function exists($uid, $addedAt, $destinationLocation){
		try{
			$this->find($uid, $addedAt, $destinationLocation);
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
	 * Finds all Items
	 * @return array containing all items
	 */
	public function findAll(){
		$sql = "SELECT * FROM {$this->getTableName()}";
                $result = $this->execute($sql);

                $entityList = array();
                while($row = $result->fetchRow()){
                        $entity = new ReceivedDeactivatedUser($row);
                        array_push($entityList, $entity);
                }

                return $entityList;
	}


	/**
	 * Saves an item into the database
	 * @param Item $receivedUser: the item to be saved
	 * @return the item with the filled in id
	 */
	public function save($receivedUser){
		if ($this->exists($receivedUser->getUid(), $receivedUser->getAddedAt(), $receivedUser->getDestinationLocation())) {
			return false;  //Already exists, do nothing
		}

		$sql = 'INSERT INTO `'. $this->getTableName() . '` (`uid`, `added_at`, `destination_location`, `status`)'.
				' VALUES(?, ?, ?, ?)';

		$params = array(
			$receivedUser->getUid(),
			$receivedUser->getAddedAt(),
			$receivedUser->getDestinationLocation(),
			$receivedUser->getStatus()
		);

		return $this->execute($sql, $params);

	}


	/**
	 * Deletes an item
	 * @param string $uid: the uid of the ReceivedUser
	 */
	public function delete(Entity $receivedUser){
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `uid` = ?  AND `added_at` = ? AND `destination_location` = ?';
		$params = array(
			$receivedUser->getUid(),
			$receivedUser->getAddedAt(),
			$receivedUser->getDestinationLocation()
		);
		
		return $this->execute($sql, $params);
	}


}
