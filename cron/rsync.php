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

$dicontainer = new DIContainer();
$api = $dicontainer['API'];

$thisLocation = $api->getAppValue('location');

$centralServerName = $api->getAppValue('centralServer');
$server = $api->getAppValue('centralServerIP');

$output = $api->getAppValue('cronErrorLog');

$dbSyncPath = $api->getAppValue('dbSyncPath');
$dbSyncRecvPath = $api->getAppValue('dbSyncRecvPath');
$user = $api->getAppValue('user');

$locationList = $dicontainer['LocationMapper']->findAll();

if ($centralServerName === $thisLocation) {
	foreach ($locationList as $location) {
		$locationName = $location->getLocation();
		if ($locationName === $thisLocation) {
			continue;
		}
		$microTime = $this->api->microTime();
		$cmd = "echo {$microTime} > {$dbSyncPath}{$locationName}/last_update.txt";
		$this->api->exec(escapeshellcmd($cmd));

		$cmd = "rsync --verbose --compress --rsh ssh \
		      --recursive --times --perms --links --delete \
		      --exclude \"*~\" \
		      db_sync/{$locationName}/ {$user}@{$location->getIP()}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

		#$safe_cmd = escapeshellcmd($cmd);
		exec($cmd);
	}
}
else { //not-central server
	$cmd = "rsync --verbose --compress --rsh ssh \
	      --recursive --times --perms --links --delete \
	      --exclude \"*~\" \
	      db_sync/{$centralServerName}/ {$user}@{$server}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

	#$safe_cmd = escapeshellcmd($cmd);
	exec($cmd);

}
