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
use \OCA\AppFramework\Db\Entity;
use \OCA\AppFramework\Db\DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException;

use \OCA\MultiInstance\Db\QueuedShare;

class QueuedShareMapper extends Mapper {



	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_queued_share');

	}
	/**
	 * Finds an item by id
	 * @throws DoesNotExistException: if the item does not exist
	 * @return the item
	 */
	public function find($shareWith, $uidOwner, $fileTarget, $fileSourceStorage, $fileSourcePath, $updatedAt, $destinationLocation){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `share_with` = ? AND `uid_owner` = ? AND `file_target` = ? AND `file_source_storage` = ? AND `file_source_path` = ? AND `updated_at` = ? AND `destination_location` = ?';
		$params = array($shareWith, $uidOwner, $fileTarget, $fileSourceStorage, $fileSourcePath, $stime, $destinationLocation);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException("QueuedShare with path_hash {$shareWith} storage {$uidOwner} and addedAt = {$stime} and destinationLocation {$destinationLocation} does not exist!");
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException("QueuedShare with path_hash {$shareWith} storage {$uidOwner} and addedAt = {$stime} and destinationLocation {$destinationLocation} returned more than one result.");
		}
		return new QueuedShare($row);

	}

	public function exists($shareWith, $uidOwner, $fileTarget, $fileSourceStorage, $fileSourcePath, $stime, $destinationLocation){
		try{
			$this->find($shareWith, $uidOwner, $fileTarget, $fileSourceStorage, $fileSourcePath, $stime, $destinationLocation);
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
			$entity = new QueuedShare($row);
			array_push($entityList, $entity);
		}

		return $entityList;
	}


	/**
	 * Saves an item into the database
	 * @param Item $queuedShare: the item to be saved
	 * @return the item with the filled in id
	 */
	public function save($queuedShare){
		if ($this->exists($queuedShare->getShareWith(), $queuedShare->getUidOwner(), $queuedShare->getFileTarget(), $queuedShare->getFileSourceStorage(), $queuedShare->getFileSourcePath(), $queuedShare->getStime(), $queuedShare->getDestinationLocation())) {
			return false;  //Already exists, do nothing
		}
		
		return $this->insert($queuedShare);
	} 


	/**
	 * Deletes an item
	 * @param string $shareWith: the path_hash of the QueuedShare
	 */
	public function delete(Entity $entity){
		$queuedShare = $entity;
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `share_with` = ? AND `uid_owner` = ? AND `file_target` = ? AND `file_source_storage` = ? AND `file_source_path` = ? AND `stime` = ? AND `destination_location`';
		$params = array(
			$queuedShare->getShareWith(),
			$queuedShare->getUidOwner(),
			$queuedShare->getFileTarget(),
			$queuedShare->getFileSourceStorage(),
			$queuedShare->getFileSourcePath(),
			$queuedShare->getStime(),
			$queuedShare->getDestinationLocation()
		);
		
		return $this->execute($sql, $params);
	}


}
