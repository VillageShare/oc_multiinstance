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

use \OCA\AppFramework\Core\API as API;
use \OCA\AppFramework\Db\Mapper as Mapper;
use \OCA\AppFramework\Db\DoesNotExistException as DoesNotExistException;
use \OCA\AppFramework\Db\MultipleObjectsReturnedException as MultipleObjectsReturnedException;
use OCA\Friends\Db\AlreadyExistsException as AlreadyExistsException;
use OCA\MultiInstance\Db\QueuedResponse;


use \OCA\AppFramework\Db\Entity;


class ReceivedResponseMapper extends Mapper {



	

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_received_responses');
	}

	public function findAll() {
                $sql = "SELECT * FROM {$this->getTableName()}";
                $result = $this->execute($sql);

		$entityList = array();
		while($row = $result->fetchRow()){
			$entity = new ReceivedRequest($row);
			array_push($entityList, $entity);
		}

		return $entityList;

	}


	public function find($requestId, $destinationLocation){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE `request_id` = ? AND `destination_location` = ?';
		$params = array($requestId, $destinationLocation);

		$result = array();
		
		$result = $this->execute($sql, $params);
		$row = $result->fetchRow();

		if ($row === false) {
			throw new DoesNotExistException("QueuedUser with uid {$requestId} and addedAt = {$destinationLocation} does not exist!");
		} elseif($result->fetchRow() !== false) {
			throw new MultipleObjectsReturnedException("QueuedUser with uid {$requestId} and addedAt = {$destinationLocation} returned more than one result.");
		}
		return new QueuedResponse($row);

	}

	public function exists($requestId, $destinationLocation){
		try{
			$this->find($requestId, $destinationLocation);
		}
		catch (DoesNotExistException $e){
			return false;
		}
		catch (MultipleObjectsReturnedException $e){
			return true;
		}
		return true;
	}



	public function delete(Entity $receivedResponse) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `request_id` = ?';
		$params = array($receivedResponse->getRequestId());

		return $this->execute($sql, $params);
	}

}
