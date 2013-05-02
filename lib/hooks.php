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

use OCA\MultiInstance\Db\QueuedUser;
use OCA\MultiInstance\Db\UserUpdate;
use OCA\MultiInstance\Db\QueuedShare;
use OCA\MultiInstance\DependencyInjection\DIContainer;
use OCA\MultiInstance\Lib\MILocation;

use OC\Files\Cache\Cache;

/**
 * This class contains all hooks.
 */
class Hooks{

	//TODO: try catch with rollback
	static public function createUser($parameters) {
		$c = new DIContainer();
		$centralServerName = $c['API']->getAppValue('centralServer');
		$thisLocation = $c['API']->getAppValue('location');
		$date = $c['API']->getTime();
		$uid = $parameters['uid'];

		//Only push if you are a noncentral server and you created this user
		if ( $centralServerName !== $thisLocation && MILocation::uidContainsThisLocation($uid)) {
			$displayname = '';
			$password = $c['API']->getPassword($uid);  //Queue hashed password
			
			$queuedUser = new QueuedUser($uid, $displayname, $password, $date, $centralServerName);
			$c['QueuedUserMapper']->save($queuedUser);
		}
		$userUpdate = new UserUpdate($uid, $date, $centralServerName);
		$c['UserUpdateMapper']->insert($userUpdate);
	}

	static public function updateUser($parameters) {
		$c = new DIContainer();
		$centralServerName = $c['API']->getAppValue('centralServer');
		$date = $c['API']->getTime();
		$uid = $parameters['uid'];

		if ($centralServerName !== $c['API']->getAppValue('location')) {
			$displayname = '';
			$password = $c['API']->getPassword($uid); //Queue hashed password

			$queuedUser = new QueuedUser($uid, $displayname, $password, $date, $centralServerName);
			$c['QueuedUserMapper']->save($queuedUser);
		}	
		$userUpdate = $c['UserUpdateMapper']->find($uid);
		$userUpdate->setUpdatedAt($date);
		$c['UserUpdateMapper']->update($userUpdate);
	}


	/**
	 * Note: parameters['accepted'] does not exist at this time.  Not sure where accepted is used.
	 */
	static public function queueShareAdd($parameters, $mockAPI=null, $mockQueuedShareMapper=null) {
		if ($mockAPI === null && $mockQueuedShareMapper === null) {
			$di = new DIContainer();
			$api = $di['API'];
			$queuedShareMapper = $di['QueuedShareMapper'];
		}
		else {
			$api = $mockAPI;
			$queuedShareMapper = $mockQueuedShareMapper;
		}
		if ((string)$parameters['fileSource'] !== $parameters['itemSource'] || $parameters['itemSource'] !== substr($parameters['itemTarget'], 1)) {
			$info = print_r($parameters, TRUE);
			$api->log("Cannot share an item where the fileSource, item_source, and item_target are different.  Implementation counts on it.  Share information = {$info}.");	
		}
		list($fileSourceStorage, $fileSourcePath) = Cache::getById($parameters['fileSource']);
		if ($fileSourceStorage == null) {
			$api->log("Could not get storageId from fileSource = {$parameters['fileSource']}.  Did not queue share.");
			return;
		}
		//still needs parent info
		$stime = $api->getShareStime($parameters['id']);	
		$queuedShare = new QueuedShare($parameters['shareType'], $parameters['shareWith'], $parameters['uidOwner'], $parameters['itemType'], $fileSourceStorage, $fileSourcePath, $parameters['fileTarget'], $parameters['permissions'], $stime, $parameters['token'], $api->getAppValue('centralServer'), $api->getAppValue('location'), QueuedShare::CREATE);
		$queuedShareMapper->insert($queuedShare);
		

	}

	/**
	 * Unofficial hooks, don't use the emit mechanism
	 */

	static public function queueShareExpiration($shareId, $expiration, $mockAPI = null, $mockQueuedShareMapper=null) {
		//lookup id
		if ($mockAPI === null && $mockQueuedShareMapper === null) {
			$di = new DIContainer();
			$api = $di['API'];
			$queuedShareMapper = $di['QueuedShareMapper'];
		}
		else {
			$api = $mockAPI;
			$queuedShareMapper = $mockQueuedShareMapper;
		}
		$share = $api->findShare($shareId);
		$shareUpdate = $shareUpdateMapper->find($shareId);
		list($fileSourceStorage, $fileSourcePath) = Cache::getById($share['file_source']);
		if ($fileSourceStorage == null) {
			$api->log("Could not get storageId from fileSource = {$parameters['fileSource']}.  Did not queue share.");
			return;
		}

		$updatedAt = $api->getTime();
		$shareUpdate->setUpdatedAt($updatedAt);

		$queuedShare = new QueuedShare();
		$queuedShare->setShareWith($share['share_with']);  //key
		$queuedShare->setUidOwner($share['uid_owner']);
		$queuedShare->setFileTarget($share['file_target']);
		$queuedShare->setFileSourceStorage($fileSourceStorage);
		$queuedShare->setFileSourcePath($fileSourcePath); //end key
		$queuedShare->setUpdatedAt($updatedAt);
		$queuedShare->setDestinationLocation($api->getAppValue('centralServer'));
		$queuedShare->setSendingLocation($api->getAppValue('location'));
		$queuedShare->setExpiration($expiration);
		$queuedShare->setQueueType(QueuedShare::EXPIRATION);

		$api->beginTransaction();
		$queuedShareMapper->insert($queuedShare);
		$shareUpdateMapper->update($shareUpdate);
		$api->commit();
		
	}

	static public function queueShareDelete($shareIds) {
		
		foreach ($shareIds as $shareId) {
			$share = $api->findShare($shareId);
			$shareUpdate = $shareUpdateMapper->find($shareId);
			list($fileSourceStorage, $fileSourcePath) = Cache::getById($share['file_source']);
			if ($fileSourceStorage == null) {
				$api->log("Could not get storageId from fileSource = {$parameters['fileSource']}.  Did not queue share.");
				return;
			}

			$updatedAt = $api->getTime();
			$shareUpdate->setUpdatedAt($updatedAt);
			$shareUpdate->setState(ShareUpdate::DELETED);

			$queuedShare = new QueuedShare();
			$queuedShare->setShareWith($share['share_with']);  //key
			$queuedShare->setUidOwner($share['uid_owner']);
			$queuedShare->setFileTarget($share['file_target']);
			$queuedShare->setFileSourceStorage($fileSourceStorage);
			$queuedShare->setFileSourcePath($fileSourcePath); //end key
			$queuedShare->setDestinationLocation($api->getAppValue('centralServer'));
			$queuedShare->setSendingLocation($api->getAppValue('location'));
			$queuedShare->setUpdatedAt($updatedAt);
			$queuedShare->setQueueType(QueuedShare::DELETE);
			$queueShareMapper->insert($queuedShare);
		}
	}


}
