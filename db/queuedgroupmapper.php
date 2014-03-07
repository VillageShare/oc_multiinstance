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

class QueuedGroupMapper extends Mapper {



	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_queued_groups');
	}

	/**
	 * Finds an item by id
	 * @throws DoesNotExistException: if the item does not exist
	 * @return the item
	 */
	public function find($gid, $addedAt, $destinationLocation){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `gid` = ? AND `added_at` = ? AND `destination_location` = ?';
		$params = array($gid, $addedAt, $destinationLocation);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException("QueuedGroup with gid {$gid} and addedAt = {$addedAt} and destinationLocation {$destinationLocation} does not exist!");
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException("QueuedGroup with gid {$gid} and addedAt = {$addedAt} and destinationLocation {$destinationLocation} returned more than one result.");
		}
		return new QueuedGroup($row);

	}

	public function exists($gid, $addedAt, $destinationLocation){
		try{
			$this->find($gid, $addedAt, $destinationLocation);
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
		$result = $this->findAllQuery($this->getTableName());

		$entityList = array();
		while($row = $result->fetchRow()){
			$entity = new QueuedGroup($row);
			array_push($entityList, $entity);
		}

		return $entityList;
	}


	/**
	 * Saves an item into the database
	 * @param Item $queuedGroup: the item to be saved
	 * @return the item with the filled in id
	 */
	public function save($queuedGroup){
		if ($this->exists($queuedGroup->getGid(), $queuedGroup->getAddedAt(), $queuedGroup->getDestinationLocation())) {
			return false;  //Already exists, do nothing
		}

		$sql = 'INSERT INTO `'. $this->getTableName() . '` (`gid`, `added_at`, `destination_location`)'.
				' VALUES  (?, ?, ?)';

		$params = array(
			$queuedGroup->getGid(),
			$queuedGroup->getAddedAt(),
			$queuedGroup->getDestinationLocation()
		);

		return $this->execute($sql, $params);

	}


	/**
	 * Deletes an item
	 * @param string $gid: the gid of the QueuedGroup
	 */
	public function delete(Entity $queuedGroup){
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `gid` = ?  AND `added_at` = ? AND `destination_location`';
		$params = array(
			$queuedGroup->getGid(),
			$queuedGroup->getAddedAt(),
			$queuedGroup->getDestinationLocation()
		);
		
		return $this->execute($sql, $params);
	}


}
