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


class ReceivedResponse {

	
	private $requestId;
	private $destinationLocation;
	private $answer;

	public function __construct($requestIdOrFromRow, $destinationLocation=null, $answer=null){
		if($destinationLocation === null){
			$this->fromRow($requestIdOrFromRow);
		}
		else {
			$this->destinationLocation = $destinationLocation;
			$this->requestId = $requestIdOrFromRow;
			$this->answer = $answer;
		}
	}

	public function fromRow($row){
		$this->id = $row['request_id'];
		$this->destinationLocation = $row['destination_location'];
		$this->answer = $row['answer'];
	}

	public function getRequestId() {
		return $this->requestId;
	}

	public function getDestinationLocation(){
		return $this->destinationLocation;
	}

	public function getAnswer(){
		return $this->answer;
	}

	
}
