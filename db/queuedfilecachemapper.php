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


class QueuedFileCacheMapper extends Mapper {


	private $tableName;

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*multiinstance_queued_filecache';
	}

	/**
	 * Finds an item by id
	 * @throws DoesNotExistException: if the item does not exist
	 * @return the item
	 */
	public function find($pathHash, $storage, $addedAt, $destinationLocation){
		$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `path_hash` = ? AND `storage` = ? AND `added_at` = ? AND `destination_location` = ?';
		$params = array($pathHash, $storage, $addedAt, $destinationLocation);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException("QueuedFileCache with path_hash {$pathHash} storage {$storage} and addedAt = {$addedAt} and destinationLocation {$destinationLocation} does not exist!");
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException("QueuedFileCache with path_hash {$pathHash} storage {$storage} and addedAt = {$addedAt} and destinationLocation {$destinationLocation} returned more than one result.");
		}
		return new QueuedFileCache($row);

	}

	public function exists($pathHash, $addedAt, $destinationLocation){
		try{
			$this->find($pathHash, $addedAt, $destinationLocation);
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
		$result = $this->findAllQuery($this->tableName);

		$entityList = array();
		while($row = $result->fetchRow()){
			$entity = new QueuedFileCache($row);
			array_push($entityList, $entity);
		}

		return $entityList;
	}


	/**
	 * Saves an item into the database
	 * @param Item $queuedFileCache: the item to be saved
	 * @return the item with the filled in id
	 */
	public function save($queuedFileCache){
		if ($this->exists($queuedFileCache->getPathHash(), $queuedFileCache->getStorage(), $queuedFileCache->getAddedAt(), $queuedFileCache->getDestinationLocation())) {
			return false;  //Already exists, do nothing
		}

		$sql = 'INSERT INTO `'. $this->tableName . '` (`storage`, `path`, `path_hash`, `parent`, `name`,  `mimetype`, `mimepart`, `size`, `mtime`, `encrypted`, `etag`, `added_at`, `destination_location`'.
				' VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

		$params = array(
			$queuedFileCache->getStorage(),
			$queuedFileCache->getPath(),
			$queuedFileCache->getPathHash(),
			$queuedFileCache->getParent(),
			$queuedFileCache->getName(),
			$queuedFileCache->getMimeType(),
			$queuedFileCache->getMimePart(),
			$queuedFileCache->getSize(),
			$queuedFileCache->getMTime(),
			$queuedFileCache->getEncrypted(),
			$queuedFileCache->getEtag(),
			$queuedFileCache->getAddedAt(),
			$queuedFileCache->getDestinationLocation()
		);

		return $this->execute($sql, $params);

	}


	/**
	 * Deletes an item
	 * @param string $pathHash: the path_hash of the QueuedFileCache
	 */
	public function delete($queuedFileCache){
		$sql = 'DELETE FROM `' . $this->tableName . '` WHERE `path_hash` = ? AND `storage` = ? AND `added_at` = ? AND `destination_location`';
		$params = array(
			$queuedFileCache->getPathHash(),
			$queuedFileCache->getStorage(),
			$queuedFileCache->getAddedAt(),
			$queuedFileCache->getDestinationLocation()
		);
		
		return $this->execute($sql, $params);
	}


}
