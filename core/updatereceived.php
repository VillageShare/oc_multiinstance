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

namespace OCA\MultiInstance\Core;

use \OCA\AppFramework\Db\DoesNotExistException;

use OCA\MultiInstance\Db\UserUpdate;
use \OC_User;
use OCA\Friends\Db\Friendship;

/* Methods for updating instance db rows based on received rows */
class UpdateReceived {
	

	private $api; 
	private $receivedUserMapper;
	private $userUpdateMapper;
	private $receivedFriendshipMapper;
	private $receivedUserFacebookIdMapper;
	private $friendshipMapper;


	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $receivedUserMapper, $userUpdateMapper, $receivedFriendshipMapper, $receivedUserFacebookIdMapper, $friendshipMapper){
		$this->api = $api;
		$this->receivedUserMapper = $receivedUserMapper;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->receivedFriendshipMapper = $receivedFriendshipMapper;
		$this->receivedUserFacebookIdMapper = $receivedUserFacebookIdMapper;
		$this->friendshipMapper = $friendshipMapper;
	}


	public function updateUsersWithReceivedUsers() {
		$receivedUsers = $this->receivedUserMapper->findAll();		

		foreach ($receivedUsers as $receivedUser){
			$id = $receivedUser->getUid();
			$receivedTimestamp = $receivedUser->getAddedAt();

			$this->api->beginTransaction();
			if ($this->api->userExists($id)) {

				//TODO: All of this should be wrapped in a try block with a rollback...
				$userUpdate = $this->userUpdateMapper->find($id);	
				//if this is new
				if ($receivedTimestamp > $userUpdate->getUpdatedAt()) {
					$userUpdate->setUpdatedAt($receivedTimestamp);	
					$this->userUpdateMapper->update($userUpdate);
					OC_User::setPassword($id, $receivedUser->getPassword());
					OC_User::setDisplayName($id, $receivedUser->getDisplayname());
					
				}
				$this->receivedUserMapper->delete($receivedUser);
			}
			else {
				$userUpdate = new UserUpdate($id, $receivedTimestamp);

				//TODO: createUser will cause the user to be sent back to UCSB, maybe add another parameter?
				$this->api->createUser($id, $receivedUser->getPassword());
				$this->userUpdateMapper->save($userUpdate);
				$this->receivedUserMapper->delete($receivedUser);
			}
			$this->api->commit();

		}

	}

	public function updateFriendshipsWithReceivedFriendships() {
		$receivedFriendships = $this->receivedFriendshipMapper->findAll();
		
		foreach ($receivedFriendships as $receivedFriendship) {
			//TODO: try block with rollback?
			$this->api->beginTransaction();
			try {
				$friendship = $this->friendshipMapper->find($receivedFriendship->getUid1(), $receivedFriendship->getUid2());
				if ($receivedFriendship->getAddedAt() > $friendship->getUpdatedAt()) { //if newer than last update
					$friendship->setStatus($receivedFriendship->getStatus());
					$friendship->setUpdatedAt($receivedFriendship->getUpdatedAt());
					$this->friendshipMapper->update($receivedFriendship);
				}
			}
			catch (DoesNotExistException $e) {
				$friendship = new Friendship();
				$friendship->setUid1($receivedFriendship->getUid1());
				$friendship->setUid2($receivedFriendship->getUid2());
				$friendship->setStatus($receivedFriendship->getStatus());
				$friendship->setUpdatedAt($receivedFriendship->getUpdatedAt());
				$this->friendshipMapper->insert($receivedFriendship);
			}
			$this->receivedFriendshipMapper->delete($receivedFriendship);
			$this->api->commit();
		}
	}

	public function updateUserFacebookIdsWithReceivedUserFacebookIds() {
		$receivedUserFacebookIds = $this->receivedUserFacebookIdMapper->findAll();
	
		foreach ($receivedUserFacebookIds as $receivedUserFacebookId) {
			//TODO: try block with rollback?
			$this->api->beginTransaction();
			try {
				$userFacebookId = $this->userFacebookIdMapper->find($receivedUserFacebookId->getUid());
				//TODO: check if I need to convert to datetimes?
				if ($receivedUserFacebookId->getAddedAt() > $friendship->getUpdatedAt()) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
				}
			}
			catch (DoesNotExistException $e) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
			}
			$this->receivedUserFacebookIdMapper->delete($receivedUserFacebookId->getUid(), $receivedUserFacebookId->getAddedAt());
			$this->api->commit();
		}
	}


}
