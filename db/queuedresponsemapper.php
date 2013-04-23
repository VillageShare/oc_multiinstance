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

class QueuedResponseMapper extends Mapper {



	

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_queued_responses');
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

	/**
	 */
	public function save($response) {
		if ($this->exists($response->getRequestId(), $response->getDestinationLocation())) {
			//It has already been processed, do nothing
			return false;
		}
		return $this->insert($response);	
	}


	public function deleteAllBeforeMicrotime($microTime) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `added_at_micro_time` <= ?';
		$params = array($microTime);

		return $this->execute($sql, $params);
	}
}
