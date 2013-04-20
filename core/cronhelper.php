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


use OCA\MultiInstance\Lib\MILocation;

class CronHelper {

	private $api;
	private $locationMapper;
	private $cronTask;
	private $updateReceived;

	/**
	 * @param API $this->api: an api wrapper instance
	 */
	public function __construct($api, $locationMapper, $cronTask, $updateReceived){
		$this->api = $api;
		$this->locationMapper = $locationMapper;
		$this->cronTask = $cronTask;
		$this->updateReceived = $updateReceived;
	}

	public function sync() {
		$thisLocation = $this->api->getAppValue('location');
		$centralServerName = $this->api->getAppValue('centralServer');
		$server = $this->api->getAppValue('centralServerIP');

		$output = $this->api->getAppValue('cronErrorLog');

		$dbSyncPath = $this->api->getAppValue('dbSyncPath');
		$dbSyncRecvPath = $this->api->getAppValue('dbSyncRecvPath');
		$user = $this->api->getAppValue('user');

		$locationList = $this->locationMapper->findAll();

		if ($centralServerName === $thisLocation) {
			foreach ($locationList as $location) {
				$locationName = $location->getLocation();
				if ($locationName === $thisLocation) {
					continue;
				}
				$microTime = $this->api->microTime();
				$cmd = "echo {$microTime} > {$dbSyncPath}{$locationName}/last_updated.txt";
				$this->api->exec($cmd);

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
	}

	public function requestsAndResponses() {
		$this->cronTask->readAcksAndResponses(); //This method checks to whether or not it should read responses (only non-central servers should process responses)

		//Only the central server should process requests
		if ($this->api->getAppValue('centralServer') === $this->api->getAppValue('location')) {
			$this->cronTask->processRequests();
		}
		else { //only the non-central servers should process responses
			$this->cronTask->processResponses();
		}
	}

	public function run() {
		$this->cronTask->insertReceived();

		//Process
		$this->updateReceived->updateUsersWithReceivedUsers();
		$this->updateReceived->updateFriendshipsWithReceivedFriendships();
		$this->updateReceived->updateUserFacebookIdsWithReceivedUserFacebookIds();
		$this->cronTask->readAcksAndResponses(); //This method checks to whether or not it should read responses (only non-central servers should process responses)

		$this->requestsAndResponses();

		//Dump
		$this->cronTask->dumpResponses();
		$this->cronTask->dumpQueued();

		//Sync
		$this->sync();

	}
}
