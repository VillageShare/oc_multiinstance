<?php
/**
* ownCloud - MultiInstance App
*
* @author Sarah Jones
* @copyright 2013 Sarah Jones sarahe.e.p.jones@gmail.com
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


class ReceivedRequest {

	private $id;
	private $type;
	private $sendingLocation;
	private $addedAt;
	private $field1;

	public function __construct($typeOrFromRow, $id=null, $sendingLocation=null, $addedAt=null, $field1=null){
		if($id === null){
			$this->fromRow($typeOrFromRow);
		}
		else {
			$this->id = $id;
			$this->type = $typeOrFromRow;
			$this->sendingLocation = $location;
			$this->addedAt = $addedAt;
			$this->field1 = $field1;
		}
	}

	public function fromRow($row){
		$this->id = $row['id'];
		$this->type = $row['request_type'];
		$this->sendingLocation = $row['sending_location'];
		$this->addedAt = $row['added_at'];
		$this->field1 = $row['field1'];
	}

	public function getId() {
		return $this->id;
	}

	public function getType(){
		return $this->type;
	}

	public function getSendingLocation(){
		return $this->sendingLocation;
	}

	public function getAddedAt(){
		return $this->addedAt;
	}

	public function getField1(){
		return $this->field1;
	}

}
