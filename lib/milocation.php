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

use OCA\MultiInstance\Core\MultiInstanceAPI;
use OCA\MultiInstance\Db\LocationMapper;
use OCA\MultiInstance\DependencyInjection\DIContainer;
use OCA\MultiInstance\Db\QueuedFriendship;
use OCA\MultiInstance\Db\QueuedUserFacebookId;
use OCA\MultiInstance\Db\QueuedRequest;
use OCA\MultiInstance\Db\Request;
use OCA\MultiInstance\Db\QueuedFileCache;

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


	static public function userExistsAtCentralServer($uid, $mockQueuedRequestMapper=null, $mockApi=null) {
		self::pullUserFromCentralServer($uid, Request::USER_EXISTS, $mockQueuedRequestUserMapper, $mockApi);	
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

	static public function createQueuedFriendship($friend_uid1, $friend_uid2, $updated_at, $status, $queuedFriendshipMapper=null, $mockApi=null) {
		if ($queuedFriendshipMapper !== null && $mockApi !== null) {
			$qfm = $queuedFriendshipMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$qfm = $di['QueuedFriendshipMapper'];
			$api = $di['API'];
		}
		$centralServerName = $api->getAppValue('centralServer');
		$location = $api->getAppValue('location');
		if ($centralServerName !== $location) {
			$queuedFriendship = new QueuedFriendship($friend_uid1, $friend_uid2, $updated_at, $status, $centralServerName, $location);
			$qfm->save($queuedFriendship);
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


	static public function queueFile($parameters, $storage, $mimetype, $permissions, $parentStorage, $parentPath, $queuedFileCacheMapper=null, $mockApi=null) {
		if ($queuedFileCacheMapper !== null && $mockApi !==null) {
			$qm = $queuedFileCacheMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$qm = $di['QueuedFileCacheMapper'];
			$api = $di['API'];
		}

		$centralServerName = $api->getAppValue('centralServer');
		if ($centralServerName !== $api->getAppValue('location')) {
			$newStorage = MILocation::removePathFromStorage($storage);
			$newParentStorage = MILocation::removePathFromStorage($parentStorage);
			if ($newStorage && $newParentStorage) {
				$queuedFileCache = new QueuedFileCache($newStorage, $parameters[6], $parameters[5], $newParentStorage, $parentPath, $parameters[8], $mimetype, $parameters[0], $parameters[3], $parameters[2], $parameters[9], $parameters[4], $permissions, $api->getTime(),  $centralServerName);
				$qm->save($queuedFileCache);
			}
			else {
				$api->log("Unable to send file with path {$parameters[6]} and storage {$storage}  and parent with path {$parentPath} and storage {$parentStorage} to central server due to bad storage format");
			}
		}
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
