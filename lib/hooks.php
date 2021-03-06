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
use OCA\MultiInstance\Db\QueuedUser;
use OCA\MultiInstance\Db\DeactivatedUser;
use OCA\MultiInstance\Db\QueuedDeactivatedUser;
use OCA\MultiInstance\Db\QueuedGroup;
use OCA\MultiInstance\Db\GroupUpdate;
use OCA\MultiInstance\Db\QueuedGroupAdmin;
use OCA\MultiInstance\Db\QueuedGroupUser;
use OCA\MultiInstance\Db\QueuedFriendship;
use OCA\MultiInstance\Db\UserUpdate;
use OCA\MultiInstance\Db\QueuedShare;
use OCA\MultiInstance\DependencyInjection\DIContainer;
use OCA\MultiInstance\Lib\MILocation;
use OCA\MultiInstance\Db\QueuedFileCache;
use OCA\MultiInstance\Db\FilecacheUpdate;
use OCA\MultiInstance\Db\QueuedPermission;
use OCA\MultiInstance\Db\PermissionUpdate;
use \OC_Util;
use OC\Files\Cache\Cache;

/**
 * This class contains all hooks.
 */
class Hooks{

	static public function createGroup($parameters) {
		shell_exec("echo \"In createGroup() hook\" >> /home/public_html/apps/multiinstance/debug.log");
		$c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $thisLocation = $c['API']->getAppValue('location');
                $date = $c['API']->getTime();
                $gid = $parameters['gid'];

                if ($centralServerName == $thisLocation) {
                        foreach(MILocations::getLocations() as $loc) {
                                if (!$c['QueuedGroupMapper']->exists($gid, $date, $loc->getLocation(), QueuedGroup::CREATED)) {
					$queuedGroup = new QueuedGroup($gid, $date, $loc->getLocation(), QueuedGroup::CREATED);
                                        $c['QueuedGroupMapper']->save($queuedGroup);
                                }
                        }
                } else {
                        if (!$c['QueuedGroupMapper']->exists($gid, $date, $centralServerName, QueuedGroup::CREATED)) {
                                $queuedGroup = new QueuedGroup($gid, $date, $centralServerName, QueuedGroup::CREATED);
                                $c['QueuedGroupMapper']->save($queuedGroup);
                        }
                }

		$groupUpdate = new GroupUpdate($gid, $date, $centralServerName);
                $c['GroupUpdateMapper']->insert($groupUpdate);
		
	}

	static public function deleteGroup($parameters) {
		$c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $thisLocation = $c['API']->getAppValue('location');
                $date = $c['API']->getTime();
                $gid = $parameters['gid'];

		if ($centralServerName == $thisLocation) {
                        foreach(MILocations::getLocations() as $loc) {
                                if (!$c['QueuedGroupMapper']->exists($gid, $date, $loc->getLocation(), QueuedGroup::DELETED)) {
                                        $queuedGroup = new QueuedGroup($gid, $date, $loc->getLocation(), QueuedGroup::DELETED);
                                        $c['QueuedGroupMapper']->save($queuedGroup);
                                }
                        }
                } else {
                        if (!$c['QueuedGroupMapper']->exists($gid, $date, $centralServerNae, QueuedGroup::DELETED)) {
				$queuedGroup = new QueuedGroup($gid, $date, $centralServerNae, QueuedGroup::DELETED);
                                $c['QueuedGroupMapper']->save($queuedGroup);
                        }
                }

                $groupUpdate = new GroupUpdate($gid, $date, $centralServerName);
                $c['GroupUpdateMapper']->insert($groupUpdate);
	}

	static public function addToGroup($parameters) {
		$c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $thisLocation = $c['API']->getAppValue('location');
                $date = $c['API']->getTime();
		$gid = $parameters['gid'];
                $uid = $parameters['uid'];

		if ($centralServerName == $thisLocation) {
			foreach(MILocations::getLocations() as $loc) {
                                if (!$c['QueuedGroupUserMapper']->exists($gid, $uid, $date, $loc->getLocation(), QueuedGroupUser::CREATED)) {
                                        $queuedGroupUser = new QueuedGroupUser($gid, $uid, $date, $loc->getLocation(), QueuedGroupUser::CREATED);
                                        $c['QueuedGroupUserMapper']->save($queuedGroupUser);
                                }
                        }
		} else {
			 if (!$c['QueuedGroupUserMapper']->exists($gid, $date, $centralServerName, QueuedGroupUser::CREATED)) {
                                $queuedGroupUser = new QueuedGroupUser($gid, $uid, $date, $centralServerName, QueuedGroupUser::CREATED);
                                $c['QueuedGroupUserMapper']->save($queuedGroupUser);
                        }
		}	
		$groupUpdate = new GroupUpdate($gid, $date, $centralServerName);
                $c['GroupUpdateMapper']->insert($groupUpdate);
	}

	static public function removeFromGroup($parameters) {
		$c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $thisLocation = $c['API']->getAppValue('location');
                $date = $c['API']->getTime();
                $gid = $parameters['gid'];
                $uid = $parameters['uid'];

		if ($centralServerName == $thisLocation) {
                        foreach(MILocations::getLocations() as $loc) {
                                if (!$c['QueuedGroupUserMapper']->exists($gid, $uid, $date, $loc->getLocation(), QueuedGroupUser::DELETED)) {
                                        $queuedGroupUser = new QueuedGroupUser($gid, $uid, $date, $loc->getLocation(), QueuedGroupUser::DELETED);
                                        $c['QueuedGroupUserMapper']->save($queuedGroupUser);
                                }
                        }
                } else {
                         if (!$c['QueuedGroupUserMapper']->exists($gid, $uid, $date, $centralServerName, QueuedGroupUser::DELETED)) {
                                $queuedGroupUser = new QueuedGroupUser($gid, $uid, $date, $centralServerName, QueuedGroupUser::DELETED);
                                $c['QueuedGroupUserMapper']->save($queuedGroupUser);
                        }
                }

		$groupUpdate = new GroupUpdate($gid, $date, $centralServerName);
                $c['GroupUpdateMapper']->insert($groupUpdate);
	}

	// Group Admin
	static public function addAdminToGroup($parameters) {
                $c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $thisLocation = $c['API']->getAppValue('location');
                $date = $c['API']->getTime();
                $gid = $parameters['gid'];
                $uid = $parameters['uid'];

                if ($centralServerName == $thisLocation) {
                        foreach(MILocations::getLocations() as $loc) {
                                if (!$c['QueuedGroupAdminMapper']->exists($gid, $uid, $date, $loc->getLocation(), QueuedGroupAdmin::CREATED)) {
                                        $queuedGroupAdmin = new QueuedGroupAdmin($gid, $uid, $date, $loc->getLocation(), QueuedGroupAdmin::CREATED);
                                        $c['QueuedGroupAdminMapper']->save($queuedGroupAdmin);
                                }
                        }
                } else {
                         if (!$c['QueuedGroupAdminMapper']->exists($gid, $date, $centralServerName, QueuedGroupAdmin::CREATED)) {
                                $queuedGroupAdmin = new QueuedGroupAdmin($gid, $uid, $date, $centralServerName, QueuedGroupAdmin::CREATED);
                                $c['QueuedGroupAdminMapper']->save($queuedGroupAdmin);
                        }
                }
                $groupUpdate = new GroupUpdate($gid, $date, $centralServerName);
                $c['GroupUpdateMapper']->insert($groupUpdate);
        }

	static public function removeAdminFromGroup($parameters) {
                $c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $thisLocation = $c['API']->getAppValue('location');
                $date = $c['API']->getTime();
                $gid = $parameters['gid'];
                $uid = $parameters['uid'];

                if ($centralServerName == $thisLocation) {
                        foreach(MILocations::getLocations() as $loc) {
                                if (!$c['QueuedGroupAdminMapper']->exists($gid, $uid, $date, $loc->getLocation(), QueuedGroupAdmin::DELETED)) {
                                        $queuedGroupAdmin = new QueuedGroupAdmin($gid, $uid, $date, $loc->getLocation(), QueuedGroupAdmin::DELETED);
                                        $c['QueuedGroupAdminMapper']->save($queuedGroupAdmin);
                                }
                        }
                } else {
                         if (!$c['QueuedGroupAdminMapper']->exists($gid, $date, $centralServerName, QueuedGroupAdmin::DELETED)) {
                                $queuedGroupAdmin = new QueuedGroupAdmin($gid, $uid, $date, $centralServerName, QueuedGroupAdmin::DELETED);
                                $c['QueuedGroupAdminMapper']->save($queuedGroupAdmin);
                        }
                }
                $groupUpdate = new GroupUpdate($gid, $date, $centralServerName);
                $c['GroupUpdateMapper']->insert($groupUpdate);
        }

	//TODO: try catch with rollback
	static public function createUser($parameters) {
		$c = new DIContainer();
		$centralServerName = $c['API']->getAppValue('centralServer');
		$thisLocation = $c['API']->getAppValue('location');
		$date = $c['API']->getTime();
		$uid = $parameters['uid'];

		OC_Util::setupFS($uid);

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

	static public function deactivateUser($parameters) {
		$c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $date = $c['API']->getTime();
                $uid = $parameters['uid'];
		$location = $c['API']->getAppValue('location');
		
		if ($centralServerName == $location) {
			foreach(MILocations::getLocations() as $loc) {
                        	if (!$c['QueuedDeactivatedUserMapper']->exists($uid, $loc, QueuedDeactivatedUser::DEACTIVATE)) {
                               		$queuedDeactivatedUser = new QueuedDeactivatedUser($uid, $date, $loc, QueuedDeactivatedUser::DEACTIVATE);
					$c['QueuedDeactivatedUserMapper']->save($queuedDeactivatedUser);
                       		}
                	}
			if (!$c['DeactivatedUserMapper']->exists($uid)) {
                        	$deactivatedUser = new DeactivatedUser($uid, $date);
                       		$c['DeactivatedUserMapper']->save($deactivatedUser);
			}
                } else {
		 	if (!$c['QueuedDeactivatedUserMapper']->exists($uid, $centralServerName, QueuedDeactivatedUser::DEACTIVATE)) {
				 $queuedDeactivatedUser = new QueuedDeactivatedUser($uid, $date, $centralServerName, QueuedDeactivatedUser::DEACTIVATE);
                                 $c['QueuedDeactivatedUserMapper']->save($queuedDeactivatedUser);
			}
			if (!$c['DeactivatedUserMapper']->exists($uid)) {
				$deactivatedUser = new DeactivatedUser($uid, $date);
                                $c['DeactivatedUserMapper']->save($deactivatedUser);
			}
		} 
	}

	static public function reactivateUser($parameters) {
                $c = new DIContainer();
                $centralServerName = $c['API']->getAppValue('centralServer');
                $date = $c['API']->getTime();
                $uid = $parameters['uid'];
                $location = $c['API']->getAppValue('location');

		if (!$c['DeactivatedUserMapper']->exists($uid)) {
			shell_exec("echo \"User does not exist\" >> /home/owncloud/public_html/apps/multiinstance/reactivate.log");
			return;	
		} else {
			$c['DeactivatedUserMapper']->delete($uid);
		}

                if ($centralServerName == $location) {
                        foreach(MILocations::getLocations() as $loc) {
                                if (!$c['QueuedDeactivatedUserMapper']->exists($uid, $loc, QueuedDeactivatedUser::REACTIVATE)) {
                                        $queuedDeactivatedUser = new QueuedDeactivatedUser($uid, $date, $loc, QueuedDeactivatedUser::REACTIVATE);
                                        $c['QueuedDeactivatedUserMapper']->save($queuedDeactivatedUser);
                                }
                        }
                } else {
                        if (!$c['QueuedDeactivatedUserMapper']->exists($uid, $centralServerName, QueuedDeactivatedUser::REACTIVATE)) {
                                 $queuedDeactivatedUser = new QueuedDeactivatedUser($uid, $date, $centralServerName, QueuedDeactivatedUser::REACTIVATE);
                                 $c['QueuedDeactivatedUserMapper']->save($queuedDeactivatedUser);
                        }
                }
        }

	static public function updateFriendship($parameters, $mockAPI=null) { 
		if ($mockAPI) {
			$api = $mockAPI;
		}
		else {
			$di = new DIContainer();
			$api = $di['API'];
		}

		$friendship = $parameters['friendship'];
		Hooks::createQueuedFriendship($friendship->getFriendUid1(), $friendship->getFriendUid2(), $friendship->getUpdatedAt(), $friendship->getStatus());	

		if (!$api->userExists($friendship->getFriendUid1())) {
			MILocation::userExistsAtCentralServer($friendship->getFriendUid1());
		}
		if (!$api->userExists($friendship->getFriendUid2())) {
			MILocation::userExistsAtCentralServer($friendship->getFriendUid2());
		}
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

	static public function queueFile($parameters, $mockQueuedFilecacheMapper=null, $mockApi=null, $mockFilecacheUpdateMapper=null) {
		if ($mockQueuedFilecacheMapper !== null && $mockApi !==null) {
			$queuedFilecacheMapper = $mockQueuedFilecacheMapper;
			$filecacheUpdateMapper = $mockFilecacheUpdateMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$queuedFilecacheMapper = $di['QueuedFileCacheMapper'];
			$filecacheUpdateMapper = $di['FilecacheUpdateMapper'];
			$api = $di['API'];
		}

		$centralServerName = $api->getAppValue('centralServer');
		$thisLocation = $api->getAppValue('location');

		$matches = array();

		if ($centralServerName !== $thisLocation) {
			$newStorage = MILocation::getStoragePathFromId($parameters['storage']); #MILocation::removePathFromStorage($parameters['fullStorage']);
			if ($newStorage) {
				$istrash = preg_match($newStorage, 'files_trashbin', $matches);
				if ($istrash !== 0) {
					$api->log("Unable to send file with path {$parameters['path']} and storage {$parameters['storage']} to central server because we do not support files in files_trashbin");
					return;
				}
				$date = $api->getTime();
				$queuedFileCache = new QueuedFileCache($parameters['fileid'], $newStorage, $parameters['path'], null, $parameters['name'],
									$parameters['mimetype'], $parameters['mimepart'], $parameters['size'], $parameters['mtime'], $parameters['encrypted'], $parameters['etag'], $date, QueuedFileCache::CREATE, $centralServerName, $thisLocation);
				try {
					$filecacheUpdate = $filecacheUpdateMapper->find(md5($parameters['path']), $newStorage);
					$filecacheUpdate->setUpdatedAt($date);
					$filecacheUpdate->setState(FilecacheUpdate::VALID);
					$filecacheUpdateMapper->update($filecacheUpdate);
				}
				catch (DoesNotExistException $e) {
					$filecacheUpdate = new FilecacheUpdate(md5($parameters['path']), $newStorage, $date, FilecacheUpdate::VALID);
					$filecacheUpdateMapper->insert($filecacheUpdate);
				}
				$queuedFilecacheMapper->save($queuedFileCache);
			}
			else {
				$api->log("Unable to send file with path {$parameters['path']} and storage {$parameters['storage']} to central server due to bad storage format");
			}
		}
	}

	static public function queueFileUpdate($parameters, $mockQueuedFilecacheMapper=null, $mockApi=null, $mockFilecacheUpdateMapper=null) {
		if ($mockQueuedFilecacheMapper !== null && $mockApi !==null) {
			$queuedFilecacheMapper = $mockQueuedFilecacheMapper;
			$filecacheUpdateMapper = $mockFilecacheUpdateMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$queuedFilecacheMapper = $di['QueuedFileCacheMapper'];
			$filecacheUpdateMapper = $di['FilecacheUpdateMapper'];
			$api = $di['API'];
		}

		$centralServerName = $api->getAppValue('centralServer');
		$thisLocation = $api->getAppValue('location');
		if ($centralServerName !== $thisLocation) {
			$newStorage = MILocation::removePathFromStorage($parameters['fullStorage']);
			if ($newStorage) {
				$date = $api->getTime();
				$queuedFileCache = new QueuedFileCache($parameters['fileid'], $newStorage, $parameters['path'], null, null,
									$parameters['mimetype'], null, $parameters['size'], $parameters['mtime'],
									$parameters['encrypted'], $parameters['etag'], $date, QueuedFileCache::UPDATE, 
									$centralServerName, $thisLocation);
				
				try {
					$filecacheUpdate = $filecacheUpdateMapper->find(md5($parameters['path']), $newStorage);
					$filecacheUpdate->setUpdatedAt($date);
					$filecacheUpdate->setState(FilecacheUpdate::VALID);
					$filecacheUpdateMapper->update($filecacheUpdate);
				}
				catch (DoesNotExistException $e) {
					$filecacheUpdate = new FilecacheUpdate(md5($parameters['path']), $newStorage, $date, FilecacheUpdate::VALID);
					$filecacheUpdateMapper->insert($filecacheUpdate);
				}
				$queuedFilecacheMapper->save($queuedFileCache);
			}
			else {
				$api->log("Unable to send file with path {$parameters['path']} and storage {$parameters['fullStorage']} to central server due to bad storage format");
			}
		}
	}

	static public function queueFileDelete($parameters, $mockQueuedFilecacheMapper=null, $mockApi=null, $mockFilecacheUpdateMapper=null) {
		if ($mockQueuedFilecacheMapper !== null && $mockApi !==null) {
			$queuedFilecacheMapper = $mockQueuedFilecacheMapper;
			$filecacheUpdateMapper = $mockFilecacheUpdateMapper;
			$api = $mockApi;
		}
		else {
			$di = new DIContainer();
			$queuedFilecacheMapper = $di['QueuedFileCacheMapper'];
			$filecacheUpdateMapper = $di['FilecacheUpdateMapper'];
			$api = $di['API'];
		}

		$centralServerName = $api->getAppValue('centralServer');
		$thisLocation = $api->getAppValue('location');
		if ($centralServerName !== $thisLocation) {
			$newStorage = MILocation::getStoragePathFromId($parameters['storage']); # MILocation::removePathFromStorage($parameters['fullStorage']);
			if ($newStorage) {
				$date = $api->getTime();
				$queuedFileCache = new QueuedFileCache(null, $newStorage, $parameters['path'], null, null,
									null, null, null, null,
									null, null, $date, QueuedFileCache::DELETE, 
									$centralServerName, $thisLocation);
				
				try {
					$filecacheUpdate = $filecacheUpdateMapper->find(md5($parameters['path']), $newStorage);
					$filecacheUpdate->setUpdatedAt($date);
					$filecacheUpdate->setState(FilecacheUpdate::DELETED);
					$filecacheUpdateMapper->update($filecacheUpdate);
				}
				catch (DoesNotExistException $e) { 
					$filecacheUpdate = new FilecacheUpdate(md5($parameters['path']), $newStorage, $date, FilecacheUpdate::DELETED);
					$filecacheUpdateMapper->insert($filecacheUpdate);
				}
				$queuedFilecacheMapper->save($queuedFileCache);
			}
			else {
				$api->log("Unable to send file with path {$parameters['path']} and storage {$parameters['fullStorage']} to central server due to bad storage format");
			}
		}
	}

	static public function queuePermissionUpdate($parameters, $mockApi=null, $mockQueuedPermissionMapper=null, $mockPermissionUpdateMapper=null) {
		Hooks::queuePermission($parameters['fileid'], $parameters['user'], $parameters['permissions'], PermissionUpdate::VALID);
	}

	static public function queuePermissionDelete($parameters, $mockApi=null, $mockQueuedPermissionMapper=null) {
		Hooks::queuePermission($parameters['fileid'], $parameters['user'], null, PermissionUpdate::DELETED);
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
			try {
				$permissionUpdate = $permissionUpdateMapper->find($fileid, $user);
				$permissionUpdate->setUpdatedAt($time);
				$permissionUpdate->setStatus($state);
				$permissionUpdateMapper->update($permissionUpdate);
			}
			catch (DoesNotExistException $e) {
				$permissionUpdate = new PermissionUpdate($fileid, $user, $time, $state);
				$permissionUpdateMapper->insert($permissionUpdate);
			}
			list($storage, $path) = \OC\Files\Cache\Cache::getById($fileid);
			if (!$path) {
				$api->log("Cannot get storage and path for fileid ] {$fileid}.  Not queuing file permissions.");
				return;
			}
			$queuedPermission = new QueuedPermission($path, $user, $permissions, $time, $state, $centralServerName);
			$queuedPermissionMapper->save($queuedPermission);
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

}
