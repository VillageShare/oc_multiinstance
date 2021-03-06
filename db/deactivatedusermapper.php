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

class DeactivatedUserMapper extends Mapper {



	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_deactivatedusers');
	}

	/**
	 * Finds an item by id
	 * @throws DoesNotExistException: if the item does not exist
	 * @return the item
	 */
	public function find($uid){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `uid` = ?';
		$params = array($uid);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			shell_exec("echo \"DeactivatedUser uid = {$uid} DoesNotExistException\" >> /home/owncloud/public_html/apps/multiinstance/reactivate.log");
			throw new DoesNotExistException("DeactivatedUser with uid {$uid} does not exist!");
		} elseif($result->fetchRow() !== false) {
			shell_exec("echo \"DeactivatedUser MultipleObjectsReturnedException\" >> /home/owncloud/public_html/apps/multiinstance/reactivate.log");
			throw new MultipleObjectsReturnedException("QueuedUser with uid {$uid}  returned more than one result.");
		}
		return new DeactivatedUser($row);

	}

	public function exists($uid){
		try{
			$this->find($uid);
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
                        $entity = new DeactivatedUser($row);
                        array_push($entityList, $entity);
                }

                return $entityList;
	}


	/**
	 * Saves an item into the database
	 * @param Item $queuedUser: the item to be saved
	 * @return the item with the filled in id
	 */
	public function save($queuedUser){
		if ($this->exists($queuedUser->getUid(), $queuedUser->getAddedAt())) {
			return false;  //Already exists, do nothing
		}

		$sql = 'INSERT INTO `'. $this->getTableName() . '` (`uid`, `added_at`)'.
				' VALUES(?, ?)';

		$params = array(
			$queuedUser->getUid(),
			$queuedUser->getAddedAt(),
		);

		return $this->execute($sql, $params);

	}


	/**
	 * Deletes an item
	 * @param string $uid: the uid of the QueuedUser
	 */
	public function delete($uid){
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `uid` = ?';
		$params = array(
			$uid
		);
		
		return $this->execute($sql, $params);
	}


}
