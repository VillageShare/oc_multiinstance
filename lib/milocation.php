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
 * This also contains methods that would be hooks if hooks existed for the necessary events
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

	/**
	 * @brief Creates a request for central server to push a user to this server.  After receiving the response
	 * 	additional processing will occur.
 	 */
	static public function userExistsAtCentralServer($uid, $mockQueuedRequestMapper=null, $mockApi=null) {
		self::pullUserFromCentralServer($uid, Request::USER_EXISTS, $mockQueuedRequestMapper, $mockApi);	
	}

	/**
	 * @brief: creates a request for the central server to push a user to this server
	 */
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

	/**
	 * Helper function
	 * @brief Copy a file from its path to db_sync
	 */
	static public function linkFileForSyncing($api, $path, $subStorage, $serverName, $fileid) {
		

		$fullLocalPath = escapeshellarg($api->getSystemValue('datadirectory').$subStorage.$path);
		$rsyncPath = escapeshellarg($api->getAppValue('dbSyncPath') . $serverName . '/' .(string)$fileid);
		$cmd = "ln -s {$fullLocalPath} {$rsyncPath}";
		$api->exec(escapeshellcmd($cmd));
	}

	static public function removeLinks($api) {
		$dbSync = $api->getAppValue('dbSyncPath');
		$cmd = "find {$dbSync} -maxdepth 2 -type l -exec rm -f {} \\;";
		$api->exec($cmd);
	}

	/**
	 * Helper function
	 * @brief Copy a file from db_sync to its appropriate path
	 */
	static public function copyFileToDataFolder($api, $path, $subStorage, $serverName, $fileid) {
		$rsyncPath = escapeshellarg($api->getAppValue('dbSyncRecvPath') . $serverName . '/' . (string)$fileid);
		$fullLocalPath = escapeshellarg($api->getSystemValue('datadirectory').$subStorage.$path);

		$cmd = "cp --preserve {$rsyncPath} {$fullLocalPath}";
		$api->exec(escapeshellcmd($cmd));
	} 

	/**
	 * @brief Helper function for queueFile
	 * @param $storage string
	 */
	static public function removePathFromStorage($storage, $mockAPI=null) {
		$result = strrpos($storage, '/data/');
		if ($result) {
			return substr($storage, $result + 5);
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
