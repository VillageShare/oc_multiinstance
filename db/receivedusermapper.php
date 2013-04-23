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

use OCA\MultiInstance\Db\ReceivedUser;
use \OCA\AppFramework\Db\Entity;

class ReceivedUserMapper extends Mapper {


	

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_received_users');
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
			$entity = new ReceivedUser($row);
			array_push($entityList, $entity);
		}

		return $entityList;
	}


	/**
	 * Deletes an item
	 * @param string $uid: the uid of the ReceivedUser
	 */
	public function delete(Entity $receivedUser){
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `uid` = ? AND `added_at` = ? AND `destination_location` = ?';
		$params = array(
			$receivedUser->getUid(),
			$receivedUser->getAddedAt(),
			$receivedUser->getDestinationLocation()
		);
		
		return $this->execute($sql, $params);
	}


}
