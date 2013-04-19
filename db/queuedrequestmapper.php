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
use OCA\MultiInstance\Db\QueuedRequest;

class QueuedRequestMapper extends Mapper {



	private $getTableName();

	/**
	 * @param API $api: Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api, 'multiinstance_queued_requests');
	}

	/**
	 * @brief: Checks to see if this request has already been made
	 * e.g. Has a particular user already been fetched?
	 */
	public function exists($type, $destinationLocation, $field1) {
		$sql = 'SELECT * FROM `'. $this->getTableName() . '` WHERE `request_type` = ? AND `destination_location` = ? AND `field1` = ?';
		$params = array($type, $destinationLocation, $field1);

		$result = $this->execute($sql, $params);
		if ($result->fetchRow()) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * @returns the result of saving, or if it is already in the db, true.
	 */
	public function save($request) {
		if ($this->exists($request->getType(), $request->getDestinationLocation(), $request->getField1())) {
			return true;
		}
		$sql = 'INSERT INTO `'. $this->getTableName() . '` (`request_type`, `sending_location`, `added_at`, `destination_location`, `field1`)'.
			' VALUES(?, ?, ?, ?, ?)';
		$params = array($request->getType(), $request->getSendingLocation(), $request->getAddedAt(), $request->getDestinationLocation(), $request->getField1());
		return $this->execute($sql, $params);
	}

	public function delete($id) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `id` = ?';
		$params = array($id);

		return $this->execute($sql, $params);
	}
}
