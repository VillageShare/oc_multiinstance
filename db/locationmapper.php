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


class LocationMapper extends Mapper {




	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_locations');
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
			$entity = new Location($row);
			array_push($entityList, $entity);
		}

		return $entityList;
	}


	public function existsByLocation($location){

		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `location` = ?';
		$params = array($location);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row) {
			return true;
		}
		else {
			return false;
		}
	}

	public function findIPByLocation($location){
		$sql = "SELECT * FROM `{$this->getTableName()}` WHERE `location` = ?";
		$params = array($location);
	
		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();
		
		return $row['ip'];
		


	}


}
