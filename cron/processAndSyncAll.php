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

namespace OCA\MultiInstance\Cron;


require_once(__DIR__ . "/../tests/classloader.php");
require_once(__DIR__ . "/../../../owncloud/lib/public/config.php");
require_once(__DIR__ . "/../../../owncloud/lib/config.php");
require_once(__DIR__ . "/../../../owncloud/lib/base.php");

use OCA\MultiInstance\DependencyInjection\DIContainer;
use OCA\MultiInstance\Lib\MILocation;

/**
 * rsync with UCSB server
 */


//TODO: Test this
public function sync($api) {
	$appName = $api->getAppName();

	$thisLocation = $api->getAppValue($appName, 'location');

	$centralServerName = $api->getAppValue($appName, 'centralServer');
	$server = $api->getAppValue($appName, 'centralServerIP');

	$output = $api->getAppValue($appName, 'cronErrorLog');

	$dbSyncRecvPath = $api->getAppValue($appName, 'dbSyncRecvPath');

	$locationList = $dicontainer['LocationMapper']->findAll();

	if ($centralServerName === $thisLocation) {
		foreach ($locationList as $location) {
			$locationName = $location->getLocation();
			if ($locationName === $thisLocation) {
				continue;
			}

			$cmd = "rsync --verbose --compress --rsh ssh \
			      --recursive --times --perms --links --delete \
			      --exclude \"*~\" \
			      db_sync/{$locationName} www-data@{$server}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

			#$safe_cmd = escapeshellcmd($cmd);
			exec($cmd);
		}
	}
	else { //not-central server
		$cmd = "rsync --verbose --compress --rsh ssh \
		      --recursive --times --perms --links --delete \
		      --exclude \"*~\" \
		      db_sync/{$centralServerName} www-data@{$server}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

		#$safe_cmd = escapeshellcmd($cmd);
		exec($cmd);

	}
}

public function updateFromReceived($cronTask) {
	$cronTask->updateUsersWithReceivedUsers();
	$cronTask->updateFriendshipsWithReceivedFriendships();
	$cronTask->updateUserFacebookIdsWithReceivedUserFacebookIds();
}

public function processRequestsOrResponses($cronTask) {
	//Only the central server should process requests
	if ($api->getAppValue('centralServer') === $api->getAppValue('location')) {
		$c['CronTask']->processRequests();
	}
	else { //only the non-central servers should process responses
		$c['CronTask']->processResponses();
	}
}

//Main script

$dicontainer = new DIContainer();
$api = $dicontainer['API'];

//Insert
$dicontainer['CronTask']->insertReceived();

//Process
updateFromReceived($dicontainer['CronTask']);
$dicontainer['CronTask']->readAcksAndResponses(); //This method checks to whether or not it should read responses (only non-central servers should process responses)
processRequestsOrResponses($dicontainer['CronTask']);

//Dump
$c['CronTask']->dumpResponses();
$c['CronTask']->dumpQueued();

//Sync
sync($api);
