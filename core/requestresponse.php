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
	private $queuedUserMapper;



	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $userUpdateMapper, $receivedResponseMapper, $receivedRequestMapper, $queuedResponseMapper, $queuedUserMapper){
		$this->api = $api;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->receivedResponseMapper = $receivedResponseMapper;
		$this->queuedResponseMapper = $queuedResponseMapper;
		$this->receivedRequestMapper = $receivedRequestMapper;
		$this->queuedUserMapper = $queuedUserMapper;
	}

	/**
	 * Should be called UCSB side only
	 */
	public function processRequests() {
		$receivedRequests = $this->receivedRequestMapper->findAll();
		foreach ($receivedRequests as $receivedRequest) {
			$id = $receivedRequest->getId();
			$sendingLocation = $receivedRequest->getSendingLocation();
			$type = $receivedRequest->getType();
			$addedAt = $receivedRequest->getAddedAt();
			$field1 = $receivedRequest->getField1();
			
			switch ($type) {
				case Request::USER_EXISTS: //Want same behavior for these two queries
				case Request::FETCH_USER: //for login for a user that doesn't exist in the db
					$userExists = $this->api->userExists($field1);	

					$this->api->beginTransaction();
					$response = new QueuedResponse($id, $sendingLocation, (string) $userExists);
					$this->queuedResponseMapper->save($response); //Does not throw Exception if already exists

					$userUpdate = $this->userUpdateMapper->find($field1);
					$displayName = $this->api->getDisplayName($field1);
					$password = $this->api->getPassword($field1);
					$queuedUser = new QueuedUser($field1, $displayName, $password, $userUpdate->getUpdatedAt(), $sendingLocation); 
					$this->queuedUserMapper->save($queuedUser); //Does not throw Exception if already exists
					$this->api->commit();
					
					break;
				default:
					throw \Exception("Invalid request_type {$type} for request from {$location} added_at {$addedAt}, field1 = {$field1}");
					break;
			}

			$request = $this->receivedRequestMapper->delete($id);
		}
	}


	/**
	 * Should be called Village side only
	 */
	public function processResponses() {
		$receivedResponses = $this->receivedResponseMapper->findAll();
		foreach ($receivedResponses as $receivedResponse) {
			$requestId = $receivedResponse->getRequestId();

			$queuedRequest = $this->queuedRequest->find($requestId); 
			$addedAt = $receivedResponse->getAddedAt();
			$field1 = $receivedResponse->getField1();
			$answer = $receivedResponse->getAnswer();

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
					$this->api->log("Invalid request_type {$type} for request id {$requestId} from {$location} added_at {$addedAt}, field1 = {$field1}");
					continue;
					break;
			}
		}
	}
}
