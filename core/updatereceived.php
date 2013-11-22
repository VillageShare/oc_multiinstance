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

use \OCA\MultiInstance\Db\UserUpdate;
use \OC_User;
use \OCP\Share;
use \OCA\Friends\Db\Friendship;
use \OCA\MultiInstance\Db\QueuedFriendship;
use \OCA\MultiInstance\Db\QueuedUser;
use \OCA\MultiInstance\Db\QueuedPermission;
use \OCA\MultiInstance\Db\PermissionUpdate;
use \OCA\MultiInstance\Db\QueuedShare;
use \OCA\MultiInstance\Db\ShareUpdate;

use \OCA\MultiInstance\Lib\MILocation;
use \OCA\MultiInstance\Lib\ShareSupport;
use \OC\Files\Cache\Cache;
use \OCA\MultiInstance\Db\QueuedFileCache;
use \OCA\MultiInstance\Db\FilecacheUpdate;

/* Methods for updating instance db rows based on received rows */
class UpdateReceived {
	

	private $api; 
	private $receivedUserMapper;
	private $userUpdateMapper;
	private $receivedFriendshipMapper;
	private $userFacebookIdMapper;
	private $receivedUserFacebookIdMapper;
	private $friendshipMapper;
	private $queuedFriendshipMapper;
	private $queuedUserMapper;
	private $locationMapper;
	private $receivedFilecacheMapper;
	private $queuedFilecacheMapper;
	private $filecacheUpdateMapper;
	private $receivedPermissionMapper;
	private $permissionUpdateMapper;
	private $shareUpdateMapper;
        private $recievedShareMapper;
	private $queuedShareMapper;

	/**
	 * @param API $api: an api wrapper instance
	 */
	public function __construct($api, $receivedUserMapper, $userUpdateMapper, $receivedFriendshipMapper, $userFacebookIdMapper, $receivedUserFacebookIdMapper, $friendshipMapper, $queuedFriendshipMapper, $queuedUserMapper, $locationMapper, $receivedFilecacheMapper, $filecacheUpdateMapper, $queuedFilecacheMapper, $receivedPermissionMapper, $permissionUpdateMapper, $receivedShareMapper, $shareUpdateMapper, $queuedShareMapper){
		$this->api = $api;
		$this->receivedUserMapper = $receivedUserMapper;
		$this->userUpdateMapper = $userUpdateMapper;
		$this->receivedFriendshipMapper = $receivedFriendshipMapper;
		$this->userFacebookIdMapper = $userFacebookIdMapper;
		$this->receivedUserFacebookIdMapper = $receivedUserFacebookIdMapper;
		$this->friendshipMapper = $friendshipMapper;
		$this->queuedFriendshipMapper = $queuedFriendshipMapper;
		$this->queuedUserMapper = $queuedUserMapper;
		$this->locationMapper = $locationMapper;
		$this->receivedFilecacheMapper = $receivedFilecacheMapper;
		$this->filecacheUpdateMapper = $filecacheUpdateMapper;
		$this->queuedFilecacheMapper = $queuedFilecacheMapper;
		$this->receivedPermissionMapper = $receivedPermissionMapper;
		$this->permissionUpdateMapper = $permissionUpdateMapper;
		$this->shareUpdateMapper = $shareUpdateMapper;
                $this->receivedShareMapper = $receivedShareMapper;
		$this->queuedShareMapper = $queuedShareMapper;
	}


	public function updateUsersWithReceivedUsers() {
		$receivedUsers = $this->receivedUserMapper->findAll();		

		foreach ($receivedUsers as $receivedUser){
			$uid = $receivedUser->getUid();
			$receivedTimestamp = $receivedUser->getAddedAt();

			$this->api->beginTransaction();
			if ($this->api->userExists($uid)) {

				//TODO: All of this should be wrapped in a try block with a rollback...
				$userUpdate = $this->userUpdateMapper->find($uid);	
				//if this is new
				if ($receivedTimestamp > $userUpdate->getUpdatedAt()) {
					$userUpdate->setUpdatedAt($receivedTimestamp);	
					$this->userUpdateMapper->update($userUpdate);
					$this->api->setPassword($uid, $receivedUser->getPassword());
					//OC_User::setDisplayName($uid, $receivedUser->getDisplayname()); //display name has no hook at this time
					
				}
			}
			else {
				//TODO: createUser will cause the user to be sent back to UCSB, maybe add another parameter?
				$this->api->createUser($uid, 'dummy');  //create user with dummy password; this will create a UserUpdate with current time, not with received time
				$this->api->setPassword($uid, $receivedUser->getPassword());
				$userUpdate = $this->userUpdateMapper->find($uid);
				$userUpdate->setUpdatedAt($receivedTimestamp);	
				$this->userUpdateMapper->update($userUpdate);
			}
			$this->receivedUserMapper->delete($receivedUser);
			$this->api->commit();

		}

	}

	public function updateFriendshipsWithReceivedFriendships($mockLocationMapper=null) {
		$receivedFriendships = $this->receivedFriendshipMapper->findAll();
		
		foreach ($receivedFriendships as $receivedFriendship) {

			$location1 = MILocation::getUidLocation($receivedFriendship->getFriendUid1(), $mockLocationMapper);
			$location2 = MILocation::getUidLocation($receivedFriendship->getFriendUid2(), $mockLocationMapper);
			$centralServer = $this->api->getAppValue('centralServer');
			$thisLocation = $this->api->getAppValue('location');
			
			//If a user from another instance is involved, push info to that instance
			if ($receivedFriendship->getSendingLocation() !== $centralServer) {
				if ($location1 !== $receivedFriendship->getSendingLocation() && $location1 !== $centralServer) {
					$uid = $receivedFriendship->getFriendUid2();
					$userUpdate = $this->userUpdateMapper->find($uid);
					$queuedUser = new QueuedUser($uid, $this->api->getDisplayName($uid), $this->api->getPassword($uid), $userUpdate->getUpdatedAt(), $location1); 
					$queuedFriendship = new QueuedFriendship($receivedFriendship->getFriendUid1(), $receivedFriendship->getFriendUid2(), $receivedFriendship->getUpdatedAt(), $receivedFriendship->getStatus(), $location1, $thisLocation);	

					$this->api->beginTransaction();
					$this->queuedFriendshipMapper->save($queuedFriendship);
					$this->queuedUserMapper->save($queuedUser);
					$this->api->commit();
				}
				if ($location2 !== $receivedFriendship->getSendingLocation() && $location2 !== $centralServer) {
					$uid = $receivedFriendship->getFriendUid1();
					$userUpdate = $this->userUpdateMapper->find($uid);
					$queuedUser = new QueuedUser($uid, $this->api->getDisplayName($uid), $this->api->getPassword($uid), $userUpdate->getUpdatedAt(), $location2); 
					$queuedFriendship = new QueuedFriendship($receivedFriendship->getFriendUid1(), $receivedFriendship->getFriendUid2(), $receivedFriendship->getUpdatedAt(), $receivedFriendship->getStatus(), $location2, $thisLocation);	

					$this->api->beginTransaction();
					$this->queuedFriendshipMapper->save($queuedFriendship);
					$this->queuedUserMapper->save($queuedUser);
					$this->api->commit();
				}
			}

			//TODO: try block with rollback?
			$this->api->beginTransaction();
			try {
				$friendship = $this->friendshipMapper->find($receivedFriendship->getFriendUid1(), $receivedFriendship->getFriendUid2());
				if ($receivedFriendship->getUpdatedAt() > $friendship->getUpdatedAt()) { //if newer than last update
					$friendship->setStatus($receivedFriendship->getStatus());
					$friendship->setUpdatedAt($receivedFriendship->getUpdatedAt());
					$this->friendshipMapper->update($friendship);
				}
			}
			catch (DoesNotExistException $e) {
				$friendship = new Friendship();
				$friendship->setFriendUid1($receivedFriendship->getFriendUid1());
				$friendship->setFriendUid2($receivedFriendship->getFriendUid2());
				$friendship->setStatus($receivedFriendship->getStatus());
				$friendship->setUpdatedAt($receivedFriendship->getUpdatedAt());
				$this->friendshipMapper->insert($friendship);
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
				if ($receivedUserFacebookId->getFriendsSyncedAt() > $userFacebookId->getFriendsSyncedAt()) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
				}
			}
			catch (DoesNotExistException $e) {
					$this->userFacebookIdMapper->save($receivedUserFacebookId);
			}
			$this->receivedUserFacebookIdMapper->delete($receivedUserFacebookId);
			$this->api->commit();
		}
	}

	public function updateFilecacheFromReceivedFilecaches() {
		$receivedFilecaches = $this->receivedFilecacheMapper->findAll();
		$dataPath = $this->api->getSystemValue('datadirectory');

		foreach ($receivedFilecaches as $receivedFilecache) {
			$this->api->beginTransaction();

			$fullPath = $dataPath . $receivedFilecache->getStorage();
			$storagePath = "local::". $fullPath;
			$cache = new Cache($storagePath);
			$storageNumericId = $cache->getNumericStorageId();
	
			$state = ($receivedFilecache->getQueueType() === QueuedFileCache::DELETE) ? FilecacheUpdate::DELETED : FilecacheUpdate::VALID;

			$filecache = $cache->get($receivedFilecache->getPath());

			//Check if this file has ever existed before by doing a find
			try {
				$filecacheUpdate = $this->filecacheUpdateMapper->find(md5($receivedFilecache->getPath()), $receivedFilecache->getStorage());
				
				if ($receivedFilecache->getAddedAt() <= $filecacheUpdate->getUpdatedAt()) {
					$this->receivedFilecacheMapper->delete($receivedFilecache);
					$this->api->commit();
					continue;
				}
				else { //new event
					$filecacheUpdate->setUpdatedAt($receivedFilecache->getAddedAt());
					$filecacheUpdate->setState($state);
					$this->filecacheUpdateMapper->update($filecacheUpdate);
				}
			}
			catch (DoesNotExistException $e) {  //Make one if it has never existed before
				$filecacheUpdate = new FilecacheUpdate(md5($receivedFilecache->getPath()), $receivedFilecache->getStorage(), $receivedFilecache->getAddedAt(), $state);
				$this->filecacheUpdateMapper->insert($filecacheUpdate);
			}
			
			//New (not old) event if made it this far
		
			//If create/update
			if (($receivedFilecache->getQueueType() === QueuedFilecache::CREATE) || ($receivedFilecache->getQueueType() === QueuedFilecache::UPDATE)) {
				$mimetype = ($filecache && array_key_exists('mimetype', $filecache)) ? $filecache['mimetype'] : null; //if typ update, it won't in the ReceivedFilecache
				if ($mimetype === 'httpd/unix-directory') {
					// do nothing, would create directory, but already exists
				}
				else if ($receivedFilecache->getMimetype() === 'httpd/unix-directory' ) {
					//make directory
					$this->api->mkdir($this->api->getSystemValue('datadirectory').$receivedFilecache->getStorage().$receivedFilecache->getPath());
				}
				else {
					MILocation::copyFileToDataFolder($this->api, $receivedFilecache->getPath(), $receivedFilecache->getStorage(), $receivedFilecache->getSendingLocation(), $receivedFilecache->getFileid());
				}

				$data = array();  //build data array, the rest are derived
				if ($receivedFilecache->getEncrypted() !== null) {
					$data['encrypted'] = $receivedFilecache->getEncrypted();
				}
				if ($receivedFilecache->getSize() !== null) {
					$data['size'] = $receivedFilecache->getSize();
				}
				if ($receivedFilecache->getMtime() !== null) {
					$data['mtime'] = $receivedFilecache->getMtime();
				}
				if ($receivedFilecache->getEtag() !== null) {
					$data['etag'] = $receivedFilecache->getEtag();
				}
				if ($receivedFilecache->getMimetype() !== null) {
					$data['mimetype'] = $receivedFilecache->getMimetype();

				}

				if (empty($filecache)) {  //if new file
					$cache->put($receivedFilecache->getPath(), $data);
				}
				else {  //not new file
					$cache->update($filecache['fileid'], $data);
				}

			}
			//This piece was for rename, but rename does not use "move" method as expected.  It creates and deletes.
			/*else if ($receivedFilecache->getQueueType() === QueuedFilecache::RENAME) {
				$cache->move($receivedFilecache->getPath(), $receivedFilecache->getPathVar());
				$filecacheUpdate->setPathHash(md5($receivedFilecache->getPathVar()));
				$filecacheUpdateMapper->update($filecacheUpdate);
			}*/
			else if ($receivedFilecache->getQueueType() === QueuedFilecache::DELETE) {
				if ($filecache) {
					$cache->remove($receivedFilecache->getPath());	

					$index = strpos($receivedFilecache->getPath(), "/");
					$rootDir = substr($receivedFilecache->getPath(), 0, $index);
					$path = substr($receivedFilecache->getPath(), $index);
					$view = new \OC\Files\View($receivedFilecache->getStorage() . $rootDir); //   /user/files
					$view->unlink($path);			//   /rest-of-path
				}
			}

			//TODO: Propogate changes (if the file is shared, should progagate to each of those users' location).  Note: in order to prevent
			//	the receiving noncentral server from pushing back to the central server afterwards, perhaps add an optional param to the
			//	put, update, move, and remove functions in core lib/files/cache/cache.php that can be a parameter in the hook that
			//	indicates whether or not the app lib/hooks.php function should queue the file event.

			$this->receivedFilecacheMapper->delete($receivedFilecache);
			$this->api->commit();


		}
	}

	//TODO create everything for this
	public function updatePermissionsFromReceivedPermissions() {
		$receivedPermissions = $this->receivedPermissionMapper->findAll();

		foreach ($receivedPermissions as $receivedPermission) {
			$dataPath = $this->api->getSystemValue('datadirectory');
			$storagePath = "local::" . $dataPath . '/' . $receivedPermission->getUser() . '/';

			$permissions = new \OC\Files\Cache\Permissions($storagePath);
			$cache = new Cache($storagePath);
			$fileid = $cache->getId($receivedPermission->getPath());
			if ($fileid === -1) {
				$this->receivedPermissionMapper->delete($receivedPermission);  //going to have to be a status on the update, but actually delete the permission
				continue;
			}

			$this->api->beginTransaction();
			$permission = $permissions->get($fileid, $receivedPermission->getUser());


			try {
				$permissionUpdate = $this->permissionUpdateMapper->find($fileid, $receivedPermission->getUser());
				if ($receivedPermission->getAddedAt() <= $permissionUpdate->getUpdatedAt()) {  //old
					$this->receivedPermissionMapper->delete($receivedPermission);  //going to have to be a status on the update, but actually delete the permission
					$this->api->commit();
					continue;
				}
				else {  //new event
					$permissionUpdate->setUpdatedAt($receivedPermission->getAddedAt());
					$permissionUpdate->setState($receivedPermission->getState());
					$this->permissionUpdateMapper->update($permissionUpdate);
				}
			} 
			catch (DoesNotExistException $e) {
					$permissionUpdate = new PermissionUpdate($fileid, $receivedPermission->getUser(), $receivedPermission->getAddedAt(), $receivedPermission->getState());
					$this->permissionUpdateMapper->insert($permissionUpdate);
			}

			//new event
			if ($receivedPermission->getState() === PermissionUpdate::DELETED) {
				if ($permission) {  //permission update
					$permissions->remove($fileid, $receivedPermission->getUser());
				}
			}
			else {
				$permissions->set($fileid, $receivedPermission->getUser(), $receivedPermission->getPermissions());
			}
			$this->receivedPermissionMapper->delete($receivedPermission);  //going to have to be a status on the update, but actually delete the permission
			$this->api->commit();
		}
	}

	public function updateSharesWithReceivedShares($mockLocationMapper=null) {
		$READ_ONLY = 0;
                $fname = "updatereceive.log";
                $cmd = "echo \"In updateSharesWithReceivedShares.\" > {$fname}";
                $this->api->exec($cmd);
                $receivedShares = $this->receivedShareMapper->findAll();
                $length = sizeof($receivedShares);
                $fname = "updatereceive.log";
                $cmd = "echo \"ReceivedShares: {$length}\" >> {$fname}";
                $this->api->exec($cmd);
                
		foreach ($receivedShares as $receivedShare) {
                        $fname = "updatereceive.log";
                        $cmd = "echo \"ReceivedShare token: {$receivedShare->getToken()}.\" >> {$fname}";
                        $this->api->exec($cmd);
                        $fname = "updatereceive.log";
                        $cmd = "echo \"ReceivedShare shareWith: {$receivedShare->getShareWith()}\nuidOwner: {$receivedShare->getUidOwner()}\n/itemType:{$receivedShare->getItemType()}\nfileSourcePath: {$receivedShare->getFileSourcePath()}\nprermissions: {$receivedShare->getPermissions()}\" >> {$fname}";
                        $this->api->exec($cmd);
                        $orig_location = MILocation::getUidLocation($receivedShare->getUidOwner(), $mockLocationMapper);
                        $dest_location = MILocation::getUidLocation($receivedShare->getShareWith(), $mockLocationMapper);

                        $centralServer = $this->api->getAppValue('centralServer');
                        $thisLocation = $this->api->getAppValue('location');
                
                        $fname = "updatereceive.log";
                        $cmd = "echo \"orig_location: {$orig_location}\ndest_location: {$dest_location}.\" >> {$fname}";
                               $this->api->exec($cmd);
        
                        if ($dest_location == $thisLocation &&  $dest_location !== $centralServer && $dest_location !== $orig_location) {
				MILocation::copyFileToDataFolder($this->api, $receivedShare->getFileSourcePath(), $receivedShare->getUidOwner()."/", $receivedShare->getSendingLocation(), $receivedShare->slugify('file_target'));
			}

                        // If a user from a non-central instance is involved, push info to that instance
                        if ($receivedShare->getSendingLocation() !== $centralServer) {
                                if ($dest_location !== $centralServer && $dest_location !== $receivedShare->getSendingLocation()) {
        
                                        $fname = "updatereceive.log";
                                        $cmd = "echo \"Share recipient is not from the central server or from the sending location.\" >> {$fname}";
                                        $this->api->exec($cmd);

                                        // We need to update both the shareUpdate and the filecacheUpdate
                                        // and queue them for sending out the updates
                                        
                                        // Handle share
                        /*                try {
                                                $shareUpdate = $this->shareUpdateMapper->findWithIds($receivedShare->getUidOwner(), $receivedShare->getShareWith(), $receivedShare->getFileSourcePath());
                                        } catch (DoesNotExistException $e) {
                                                $shareUpdate = new ShareUpdate($receivedShare->getToken(), $this->api->getTime(), ShareUpdate::VALID);
                                        } catch (MultipleObjectsReturnedException $e) {
                                                $fname = "updatereceive.log";
                                                $cmd = "echo \"MultipleObjectsReturnedException.\" >> {$fname}";
                                                $this->api->exec($cmd);
                                        }*/
                                        $queuedShare = new QueuedShare($receivedShare->getShareType(), $receivedShare->getShareWith(), $receivedShare->getUidOwner(), $receivedShare->getItemType(), $receivedShare->getFileSourceStorage(), $receivedShare->getFileSourcePath(), $receivedShare->getFileTarget(), $receivedShare->getPermissions(), $receivedShare->getStime(), $receivedShare->getToken(), $dest_location, $thisLocation, $receivedShare->getQueueType());
					$fname = "updatereceive.log";
                                        $cmd = "echo \"Created QueuedShare.\" >> {$fname}";
                                        $this->api->exec($cmd);
                        
                                        // Handle FileCache
                                     	$cache = new Cache($receivedShare->getFileSourceStorage());
                                        $fileid = $cache->getId($receivedShare->getFileSourcePath());
                                        //$fileCacheUpdate = $this->filecacheUpdateMapper->find(md5($receivedShare->getFileSourcePath()), $receivedShare->getFileSourceStorage());
                                        $queuedFilecache = new QueuedFileCache($fileid);
                                        
					$fname = "updatereceive.log";
                                        $cmd = "echo \"Created new QueuedFileCache.\" >> {$fname}";
                                        $this->api->exec($cmd);

                                        $this->api->beginTransaction();
					$fname = "updatereceive.log";
                                        $cmd = "echo \"In beginTransaction().\" >> {$fname}";
                                        $this->api->exec($cmd);
					try{
                                        	$this->queuedShareMapper->saveQueuedShare($queuedShare);
						$fname = "updatereceive.log";
                                        	$cmd = "echo \"Saved QueuedShare.\" >> {$fname}";
                                        	$this->api->exec($cmd);
					} catch (\Exception $e) {
						$fname = "updatereceive.log";
                                                $cmd = "echo \"Exception in saved QueuedShare: {$e->getMessage()}\" >> {$fname}";
                                                $this->api->exec($cmd);
					}
					try {
	                                        $this->queuedFilecacheMapper->save($queuedFilecache);
						$fname = "updatereceive.log";
                                                $cmd = "echo \"Saved QueuedFilecache\" >> {$fname}";
                                                $this->api->exec($cmd);
					} catch (\Exception $e) {
                                                $fname = "updatereceive.log";
                                                $cmd = "echo \"Exception in saved QueuedFilecache: {$e->getMessage()}\" >> {$fname}";
                                                $this->api->exec($cmd);
                                        }
					$fname = "updatereceive.log";
                                        $cmd = "echo \"Saved QueuedFileCache.\" >> {$fname}";
                                        $this->api->exec($cmd);
                                        $this->api->commit();
					$fname = "updatereceive.log";
                                        $cmd = "echo \"Finished Commit()\" >> {$fname}";
                                        $this->api->exec($cmd);
                                
                                 }
                                if ($orig_location !== $centralServer && $orig_location !== $receivedShare->getSendingLocation()) {

					$fname = "updatereceive.log";
                                        $cmd = "echo \"Share initiator  is not from the central server or from the sending location.\" >> {$fname}";
                                        $this->api->exec($cmd);

                                        // We need to update both the shareUpdate and the filecacheUpdate
                                        // and queue them for sending out the updates

                                        // Handle share
                        /*                try {
                                                $shareUpdate = $this->shareUpdateMapper->findWithIds($receivedShare->getUidOwner(), $receivedShare->getShareWith(), $receivedShare->getFileSourcePath());
                                        } catch (DoesNotExistException $e) {
                                                $shareUpdate = new ShareUpdate($receivedShare->getToken(), $this->api->getTime(), ShareUpdate::VALID);
                                        } catch (MultipleObjectsReturnedException $e) {
                                                $fname = "updatereceive.log";
                                                $cmd = "echo \"MultipleObjectsReturnedException.\" >> {$fname}";
                                                $this->api->exec($cmd);
                                        }*/
                                        $queuedShare = new QueuedShare($receivedShare->getShareType(), $receivedShare->getShareWith(), $receivedShare->getUidOwner(), $receivedShare->getItemType(), $receivedShare->getFileSourceStorage(), $receivedShare->getFileSourcePath(), $receivedShare->getFileTarget(), $receivedShare->getPermissions(), $receivedShare->getStime(), $receivedShare->getToken(), $orig_location, $thisLocation, $receivedShare->getQueueType());
                                        $fname = "updatereceive.log";
                                        $cmd = "echo \"Created QueuedShare.\" >> {$fname}";
                                        $this->api->exec($cmd);

                                        // Handle FileCache
                                        $cache = new Cache($receivedShare->getFileSourceStorage());
                                        $fileid = $cache->getId($receivedShare->getFileSourcePath());
                                        //$fileCacheUpdate = $this->filecacheUpdateMapper->find(md5($receivedShare->getFileSourcePath()), $receivedShare->getFileSourceStorage());
                                        $queuedFilecache = new QueuedFileCache($fileid);

                                        $fname = "updatereceive.log";
                                        $cmd = "echo \"Created new QueuedFileCache.\" >> {$fname}";
					$this->api->exec($cmd);

                                        $this->api->beginTransaction();
                                        $fname = "updatereceive.log";
                                        $cmd = "echo \"In beginTransaction().\" >> {$fname}";
                                        $this->api->exec($cmd);
                                        try{
                                                $this->queuedShareMapper->saveQueuedShare($queuedShare);
                                                $fname = "updatereceive.log";
                                                $cmd = "echo \"Saved QueuedShare.\" >> {$fname}";
                                                $this->api->exec($cmd);
                                        } catch (\Exception $e) {
                                                $fname = "updatereceive.log";
                                                $cmd = "echo \"Exception in saved QueuedShare: {$e->getMessage()}\" >> {$fname}";
                                                $this->api->exec($cmd);
                                        }
                                        try {
                                                $this->queuedFilecacheMapper->save($queuedFilecache);
                                                $fname = "updatereceive.log";
                                                $cmd = "echo \"Saved QueuedFilecache\" >> {$fname}";
                                                $this->api->exec($cmd);
                                        } catch (\Exception $e) {
                                                $fname = "updatereceive.log";
                                                $cmd = "echo \"Exception in saved QueuedFilecache: {$e->getMessage()}\" >> {$fname}";
                                                $this->api->exec($cmd);
                                        }
                                        $fname = "updatereceive.log";
                                        $cmd = "echo \"Saved QueuedFileCache.\" >> {$fname}";
                                        $this->api->exec($cmd);
                                        $this->api->commit();
                                        $fname = "updatereceive.log";
                                        $cmd = "echo \"Finished Commit()\" >> {$fname}";
                                        $this->api->exec($cmd);
                                }
                        }
                        $this->api->beginTransaction();
			ShareSupport::pushSharedFile($this->api, $this->locationMapper, $receivedShare);
                        try{
                                $fname = "updatereceive.log";
                                $cmd = "echo \"Need to create a new Share.\" >> {$fname}";
                                $this->api->exec($cmd);
				$cache = new Cache($receivedShare->getFileSourceStorage());
                                $fileid = $cache->getId($receivedShare->getFileSourcePath());
                                $bool = \OCP\Share::shareItem($receivedShare->getItemType(), /*$receivedShare->getFileSourcePath()*/$fileid, \OCP\Share::SHARE_TYPE_USER, $receivedShare->getShareWith(), $READ_ONLY, $receivedShare->getUidOwner());
				if($bool) {
				$fname = "updatereceive.log";
                                $cmd = "echo \"New Share successful.\" >> {$fname}";
                                $this->api->exec($cmd);
				}
                        } catch (\Exception $e) {
                                $fname = "updatereceive.log";
                                $cmd = "echo \"Exception when creating new Share: {$e->getMessage()}.\" >> {$fname}";
                                $this->api->exec($cmd);
                                continue;

                        }
			$this->api->commit();
/*			$this->api->beginTransaction();
                        try{
                                $shareUpdate = $this->shareUpdateMapper->find(\OCP\Share::getShareId($receivedShare->getUidOwner(), $receivedShare->getShareWith(), $receivedShare->getFileTarget()));
                                $fname = "updatereceive.log";
                                $cmd = "echo \"Created new Share and new ShareUpdate.\" >> {$fname}";
                                $this->api->exec($cmd);
                                if ($receivedShare->getStime() > $shareUpdate->getUpdatedAt()) {
                                        $shareUpdate->setStatus($receivedShare->getStatus());
                                        $shareUpdate->setUpdatedAt($receivedShare->getStime());
                                        $this->shareUpdateMapper->update($shareUpdate);
                                }
                        } catch (DoesNotExistException $e) {
				$fname = "updatereceive.log";
                                $cmd = "echo \"DoesNotExistException: {$e->getMessage()}\" >> {$fname}";
                                $this->api->exec($cmd);
                                $shareUpdate = new ShareUpdate($receivedShare->getStime(),QueuedShare::CREATE);
                                $this->shareUpdateMapper->update($shareUpdate);
                                $cmd = "echo \"Created new ShareUpdate\" >> {$fname}";
                                $this->api->exec($cmd);
                        } catch (\Exception $e) {
				$fname = "updatereceive.log";
                                $cmd = "echo \"Exception: {$e->getMessage()}\" >> {$fname}";
                                $this->api->exec($cmd);
			}
                        $this->receivedShareMapper->delete($receivedShare);
                        $this->api->commit();*/
                        $fname = "updatereceive.log";
                        $cmd = "echo \"End of the for-loop\" >> {$fname}";
                        $this->api->exec($cmd);
                }
        }
}
