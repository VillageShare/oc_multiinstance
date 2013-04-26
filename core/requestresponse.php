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

namespace OCA\MultiInstance\Core;

use OCA\MultiInstance\Db\Location;
use OCA\MultiInstance\Db\Request;
use OCA\MultiInstance\Db\QueuedResponse;
use OCA\MultiInstance\Db\QueuedUser;

class RequestResponse {
	

	private $api; 
	private $userUpdateMapper;
	private $receivedResponseMapper;
	private $queuedResponseMapper;
	private $receivedRequestMapper;
	private $queuedRequestMapper;
	private $queuedUserMapper;



	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $userUpdateMapper, $receivedResponseMapper, $receivedRequestMapper, $queuedResponseMapper, $queuedRequestMapper, $queuedUserMapper){
		$this->api = $api;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->receivedResponseMapper = $receivedResponseMapper;
		$this->queuedResponseMapper = $queuedResponseMapper;
		$this->receivedRequestMapper = $receivedRequestMapper;
		$this->queuedRequestMapper = $queuedRequestMapper;
		$this->queuedUserMapper = $queuedUserMapper;

		$this->dbuser = $this->api->getSystemValue('dbuser'); 
		$this->dbpassword = $this->api->getSystemValue('dbpassword'); 
		$this->dbname = $this->api->getSystemValue('dbname'); 
		$this->dbtableprefix = $this->api->getSystemValue('dbtableprefix');
		$this->recvPathPrefix = $this->api->getAppValue('dbSyncRecvPath'); 
		$this->sendPathPrefix = $this->api->getAppValue('dbSyncPath');
	}


	/**
	 * Dumps all Responses for each location into their folder of the db_sync
	 * directory.  Responses are accumulated and dumped because in order to
	 * reduce how often sync is necessary.
	 */
	public function dumpResponses() {
		$queuedTable = $this->dbtableprefix . "multiinstance_queued_responses";
		$receivedTable = $this->dbtableprefix . "multiinstance_received_responses";

		if ($this->api->getAppValue('location') === $this->api->getAppValue('centralServer')) {
			$locations = $this->locationMapper->findAll();
		}
		else {
			$location = new Location();
			$location->setLocation($this->api->getAppValue('centralServer')); 
			$locations = array($location);
		}

		$cutOffTime = $this->api->microTime();

		foreach ($locations as $location) {
			if (strpos($location->getLocation(), ";") !== false) {
				$this->api->log("Location {$location->getLocation()} has a semicolon in it.  This is not allowed.");
				continue;
			}
			if ($location->getLocation() === $this->api->getAppValue('location')) {
				continue; //never send to yourself
			}
			$file = "{$this->sendPathPrefix}{$location->getLocation()}/r{$cutOffTime}";
			#TODO: add if this directory is writable

			$cmd = "mysqldump --add-locks --insert --skip-comments --no-create-info --no-create-db -u{$this->dbuser} -p{$this->dbpassword} {$this->dbname} {$queuedTable} --where=\"destination_location='{$location->getLocation()}'\" > {$file}";
			$this->api->exec($cmd);
			$replace = "sed -i 's/{$queuedTable}/{$receivedTable}/g' {$file}";
			$this->api->exec(escapeshellcmd($replace));
			$eof = "sed -i '1i-- done;' {$file}";
			$this->api->exec($eof);
		}

		$this->queuedResponseMapper->deleteAllBeforeMicrotime($cutOffTime);
	}

	/**
	 * Should be called UCSB side only
	 */
	public function processRequests() {
		$receivedRequests = $this->receivedRequestMapper->findAll();
		foreach ($receivedRequests as $receivedRequest) {
			$id = $receivedRequest->getId();
			$sendingLocation = $receivedRequest->getSendingLocation();
			$type = $receivedRequest->getRequestType();
			$addedAt = $receivedRequest->getAddedAt();
			$field1 = $receivedRequest->getField1();
			
			switch ($type) {
				case Request::USER_EXISTS: //Want same behavior for these two queries
				case Request::FETCH_USER: //for login for a user that doesn't exist in the db
					$userExists = $this->api->userExists($field1);	

					$this->api->beginTransaction();
					$response = new QueuedResponse($id, $sendingLocation, (string) $userExists);
					$this->queuedResponseMapper->save($response); //Does not throw Exception if already exists

					if ($userExists) {
						$userUpdate = $this->userUpdateMapper->find($field1);
						$displayName = $this->api->getDisplayName($field1);
						$password = $this->api->getPassword($field1);
						$queuedUser = new QueuedUser($field1, $displayName, $password, $userUpdate->getUpdatedAt(), $sendingLocation); 
						$this->queuedUserMapper->save($queuedUser); //Does not throw Exception if already exists
					}
					$this->api->commit();
					
					break;
				default:
					$this->api->log("Invalid request_type {$type} for request from {$sendingLocation} added_at {$addedAt}, field1 = {$field1}");
					break;
			}

			$request = $this->receivedRequestMapper->delete($receivedRequest);
		}
	}


	/**
	 * Should be called Village side only
	 */
	public function processResponses() {
		$receivedResponses = $this->receivedResponseMapper->findAll();
		foreach ($receivedResponses as $receivedResponse) {
			$requestId = $receivedResponse->getRequestId();
			$addedAt = $receivedResponse->getAddedAt();
			$field1 = $receivedResponse->getField1();
			$answer = $receivedResponse->getAnswer();
			$type = $receivedResponse->getRequestType();

			switch ($type) {
				case Request::USER_EXISTS:
					if ($answer !== "true" AND $answer !== "false") {
						$this->api->log("ReceivedResponse for Request USER_EXISTS, request_id = {$receivedResponse->getId()} had invalid response = {$answer}"); 
						continue;
					}
					if ($answer === "false") {
						$friendshipRequests = $this->friendshipMapper->findAllRecipientFriendshipRequestsByUser($field1);
						foreach ($friendshipRequests as $friendshipRequest) {
							$this->friendshipMapper->delete($friendshipRequest-getUid1(), $friendshipRequest->getUid2());
						}
					}
					$this->api->beginTransaction();
					//Don't need destination for delete since they should all be this instance
					$this->receivedResponseMapper->delete($requestId);
 					$this->queuedRequestMapper->delete($requestId);
					$this->api->commit();
					break;
				case Request::FETCH_USER: 
					if ($answer !== "true" AND $answer !== "false") {
						$this->api->log("ReceivedResponse for Request FETCH_USER, request_id = {$receivedResponse->getId()} had invalid response = {$answer}"); 
						continue;
					}

					$this->api->beginTransaction();
					//Don't need destination for delete since they should all be this instance
					$this->receivedResponseMapper->delete($requestId);
 					$this->queuedRequestMapper->delete($requestId);
					$this->api->commit();
					
					break;	
				default:
					$this->api->log("Invalid request_type {$type} for request id {$requestId} from {$receivedResponse->getDestinationLocation()} added_at {$addedAt}, field1 = {$field1}");
					continue;
					break;
			}
		}
	}
}
