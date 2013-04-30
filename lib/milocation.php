<?php
/**
 * ownCloud - Multi Instance
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

namespace OCA\MultiInstance\Lib;

use OCA\AppFramework\Db\DoesNotExistException;

use OCA\MultiInstance\Core\MultiInstanceAPI;
use OCA\MultiInstance\Db\LocationMapper;
use OCA\MultiInstance\DependencyInjection\DIContainer;
use OCA\MultiInstance\Db\QueuedFriendship;
use OCA\MultiInstance\Db\QueuedUserFacebookId;
use OCA\MultiInstance\Db\QueuedRequest;
use OCA\MultiInstance\Db\Request;
use OCA\MultiInstance\Db\QueuedFileCache;
use OCA\MultiInstance\Db\QueuedUser;
use OCA\MultiInstance\Db\UserUpdate;
use OCA\MultiInstance\Db\QueuedPermission;
use OCA\MultiInstance\Db\PermissionUpdate;
/**
 * This is a static library methods for MultiInstance app.
 */
class MILocation{

	static public function getLocations() {
		$api = new MultiInstanceAPI('multiinstance');
		$locationMapper = new LocationMapper($api);
		return $locationMapper->findAll();
	}


	static public function uidContainsLocation($uid, $locationMapper=null) {
		if (strpos($uid,'@')) {
			$pattern = '/@(?P<location>[^@]+)$/';
			$matches = array();
			if (preg_match($pattern, $uid, $matches) === 1) { //must use === for this function (according to documentation)
				if ($locationMapper !== null) { //For testability 
					$lm = $locationMapper;
				} 
				else {  
					$di = new DIContainer();
					$lm = $di['LocationMapper']; 
				}
				return $lm->existsByLocation($matches['location']); 
			}
		}
		return false;
	}

	static public function uidContainsThisLocation($uid, $apiForTest=null) {
		if ($apiForTest === null) {
			$api = new MultiInstanceAPI('multiinstance');
		}
		else {
			$api = $apiForTest;
		}
		$location = $api->getAppValue('location');
		if (strpos($uid, '@')) {
			$pattern = '/@' . $location . '$/';
			if (preg_match($pattern, $uid) === 1) { //must use === for this function (according to documentation)
				return true;
			}
		}
		return false;
	}

	static public function getUidLocation($uid, $mockLocationMapper=null) {
		if (strpos($uid,'@')) {
			$pattern = '/@(?P<location>[^@]+)$/';
			$matches = array();
			if (preg_match($pattern, $uid, $matches) === 1) { //must use === for this function (according to documentation)
				if ($mockLocationMapper) {
					$locationMapper = $mockLocationMapper;
				}
				else {
					$di = new DIContainer();
					$locationMapper = $di['LocationMapper'];
				}
				
				if ($locationMapper->existsByLocation($matches['location'])) {
					return $matches['location'];
				}
			}
		}
		return null;
	}

	static public function userExistsAtCentralServer($uid, $mockQueuedRequestMapper=null, $mockApi=null) {
		self::pullUserFromCentralServer($uid, Request::USER_EXISTS, $mockQueuedRequestMapper, $mockApi);	
	}

	static public function fetchUserFromCentralServer($uid, $mockQueuedRequestMapper=null, $mockApi=null) {
		self::pullUserFromCentralServer($uid, Request::FETCH_USER, $mockQueuedRequestMapper, $mockApi);	
	}

	static protected function pullUserFromCentralServer($uid, $type, $mockQueuedRequestMapper=null, $mockApi=null) {
		if ($mockQueuedRequestMapper !== null && $mockApi !== null) {
			$qrm = $mockQueuedRequestMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$qrm = $di['QueuedRequestMapper'];
			$api = $di['API'];
		}
		$instanceName = $api->getAppValue('location');		
		$centralServerName = $api->getAppValue('centralServer');
		if ($centralServerName !== $instanceName) {
			$request = new QueuedRequest($type, $instanceName, $api->getTime(), $centralServerName,  $uid);
			$qrm->save($request);
		}
	}

	static public function createQueuedFriendship($friend_uid1, $friend_uid2, $updated_at, $status, $queuedFriendshipMapper=null, $mockApi=null, $mockUserUpdateMapper=null, $mockQueuedUserMapper=null) {
		if ($queuedFriendshipMapper !== null && $mockApi !== null && $mockUserUpdateMapper !== null && $mockQueueduserMapper !== null) {
			$qfm = $queuedFriendshipMapper;
			$api = $mockApi;
			$userUpdateMapper = $mockUserUpdateMapper;
			$queuedUserMapper = $mockQueuedUserMapper;
		}
		else {
			$di = new DIContainer();
			$qfm = $di['QueuedFriendshipMapper'];
			$api = $di['API'];
			$userUpdateMapper = $di['UserUpdateMapper'];
			$queuedUserMapper = $di['QueuedUserMapper'];
		}
		$centralServerName = $api->getAppValue('centralServer');
		$location = $api->getAppValue('location');
		if ($centralServerName !== $location) { //Non central server always pushes to central server
			$queuedFriendship = new QueuedFriendship($friend_uid1, $friend_uid2, $updated_at, $status, $centralServerName, $location);
			$qfm->save($queuedFriendship);
		}
		else if (!MILocation::uidContainsThisLocation($friend_uid1)){ //At central server, push to non-central server
			$location1 = MILocation::getUidLocation($friend_uid1);
			$userUpdate2 = $userUpdateMapper->find($friend_uid2);
			$queuedUser = new QueuedUser($friend_uid2, $api->getDisplayName($friend_uid2), $api->getPassword($friend_uid2), $userUpdate2->getUpdatedAt(), $location1);
			$queuedFriendship = new QueuedFriendship($friend_uid1, $friend_uid2, $updated_at, $status, $location1, $location);
			$api->beginTransaction();
			$queuedUserMapper->save($queuedUser);
			$qfm->save($queuedFriendship);
			$api->commit();
			
		}
		else if (!MILocation::uidContainsThisLocation($friend_uid2)){ //At central server, push to non-central server
			$location2 = MILocation::getUidLocation($friend_uid2);
			$userUpdate1 = $userUpdateMapper->find($friend_uid1);
			$queuedUser = new QueuedUser($friend_uid1, $api->getDisplayName($friend_uid1), $api->getPassword($friend_uid1), $userUpdate1->getUpdatedAt(), $location2);
			$queuedFriendship = new QueuedFriendship($friend_uid1, $friend_uid2, $updated_at, $status, $location2, $location);
			$api->beginTransaction();
			$queuedUserMapper->save($queuedUser);
			$qfm->save($queuedFriendship);
			$api->commit();
		}
	}

	static public function createQueuedUserFacebookId($uid, $facebookId, $facebookName, $syncedAt, $queuedUserFacebookIdMapper=null, $mockApi=null) {
		if ($queuedUserFacebookIdMapper !== null && $mockApi !==null) {
			$qm = $queuedUserFacebookIdMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$qm = $di['QueuedUserFacebookIdMapper'];
			$api = $di['API'];
		}
		$centralServerName = $api->getAppValue('centralServer');
		if ($centralServerName !== $api->getAppValue('location')) {
			$queuedUserFacebookId = new QueuedUserFacebookId($uid, $facebookId, $facebookName, $syncedAt);
			$qm->save($queuedUserFacebookId);
		}
		
	}


	static public function queueFile($parameters, $storage, $mimetype, $permissions, $parentStorage, $parentPath, $mockQueuedFilecacheMapper=null, $mockApi=null) {
		if ($mockQueuedFilecacheMapper !== null && $mockApi !==null) {
			$queuedFilecacheMapper = $mockQueuedFilecacheMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$queuedFilecacheMapper = $di['QueuedFileCacheMapper'];
			$api = $di['API'];
		}

		$centralServerName = $api->getAppValue('centralServer');
		if ($centralServerName !== $api->getAppValue('location')) {
			$newStorage = MILocation::removePathFromStorage($storage);
			$newParentStorage = MILocation::removePathFromStorage($parentStorage);
			if ($newStorage && $newParentStorage) {
				$queuedFileCache = new QueuedFileCache($newStorage, $parameters[6], $parameters[5], $newParentStorage, $parentPath, $parameters[8], $mimetype, $parameters[0], $parameters[3], $parameters[2], $parameters[9], $parameters[4], $permissions, $api->getTime(),  $centralServerName);
				$queuedFilecacheMapper->save($queuedFileCache);
			}
			else {
				$api->log("Unable to send file with path {$parameters[6]} and storage {$storage}  and parent with path {$parentPath} and storage {$parentStorage} to central server due to bad storage format");
			}
		}
	}

	static public function queuePermissionUpdate($fileid, $user, $permissions, $mockApi=null, $mockQueuedPermissionMapper=null, $mockPermissionUpdateMapper=null) {
		MILocation::queuePermission($fileid, $user, $permissions, PermissionUpdate::VALID);
	}

	static public function queuePermissionDelete($fileid, $user, $mockApi=null, $mockQueuedPermissionMapper=null) {
		MILocation::queuePermission($fileid, $user, $permissions, PermissionUpdate::DELETED);
	}

	static public function queuePermission($fileid, $user, $permissions, $state, $mockApi=null, $mockQueuedPermissionMapper=null, $mockPermissionUpdateMapper=null) {
		if ($mockQueuedPermissionMapper !== null && $mockApi !== null) {
			$queuedPermissionMapper = $mockQueuedPermissionMapper;
			$permissionUpdateMapper = $mockPermissionUpdateMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$queuedPermissionMapper = $di['QueuedPermissionMapper'];
			$permissionUpdateMapper = $di['PermissionUpdateMapper'];
			$api = $di['API'];
		}
		
		$centralServerName = $api->getAppValue('centralServer');
		if ($centralServerName !== $api->getAppValue('location')) {
			$time =  $api->getTime();
			$queuedPermission = new QueuedPermission($fileid, $user, $permissions, $time, $state, $centralServerName);
			try {
				$permissionUpdate = $permissionUpdateMapper->find($fileid, $user);
				$permissionUpdate->setUpdatedAt($time);
				$permissionUpdate->setStatus(PermissionUpdate::VALID);
				$permissionUpdateMapper->update($permissionUpdate);
			}
			catch (DoesNotExistException $e) {
				$permissionUpdate = new PermissionUpdate($fileid, $user, $time, $state);
				$permissionUpdateMapper->insert($permissionUpdate);
			}
			$queuedPermissionMapper->save($queuedPermission);
		}
		
	}


	/**
	 * Helper function
	 */
	static public function moveFileForSyncing($api) {

		$fullLocalPath = $api->getSystemValue('datadirectory').$parameters[6];
		$rsyncPath = $api->getAppValue('dbSyncPath') . $centralServerName . '/';
		//cp datapath + path  db_sync/$centralServerName/
	}

	/**
	 * @brief Helper function for queueFile
	 * @param $storage string
	 */
	static public function removePathFromStorage($storage, $mockAPI=null) {
		$result = strrpos($storage, '/data/');
		if ($result) {
			return substr($storage, $result + 1);
		}
		else {
			if ($mockAPI) {
				$api = $mockAPI;
			}
			else {
				$di = new DIContainer();
				$api = $di['API'];
			}
			$api->log("Storage without '/data/' in it.  Implementation depends on that.  storage = {$storage}");
			return false;
		}
	}
}
