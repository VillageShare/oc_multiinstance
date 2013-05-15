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
	private $requestResponse;

	/**
	 * @param API $this->api: an api wrapper instance
	 */
	public function __construct($api, $locationMapper, $cronTask, $updateReceived, $requestResponse){
		$this->api = $api;
		$this->locationMapper = $locationMapper;
		$this->cronTask = $cronTask;
		$this->updateReceived = $updateReceived;
		$this->requestResponse = $requestResponse;
	}

	public function sync() {
		$thisLocation = $this->api->getAppValue('location');
		$centralServerName = $this->api->getAppValue('centralServer');
		$server = $this->api->getAppValue('centralServerIP');

		$output = $this->api->getAppValue('cronErrorLog');

		$dbSyncPath = $this->api->getAppValue('dbSyncPath');
		$dbSyncRecvPath = $this->api->getAppValue('dbSyncRecvPath');
		$user = $this->api->getAppValue('user');
		$rsyncPort = $this->api->getAppValue('rsyncPort');

		$locationList = $this->locationMapper->findAll();

		$cmdPrefix = "rsync --verbose --compress --rsh='ssh -p{$rsyncPort}' \
				      --recursive --times --perms --copy-links --delete \
				      --group \
				      --exclude \"last_read.txt\"";

		if ($centralServerName === $thisLocation) {
			foreach ($locationList as $location) {
				$locationName = $location->getLocation();
				if ($locationName === $thisLocation) {
					continue;
				}
				$microTime = $this->api->microTime();
				$cmd = "echo {$microTime} > {$dbSyncPath}{$locationName}/last_updated.txt";
				$this->api->exec($cmd);

				$cmd =  "{$cmdPrefix} \
				      db_sync/{$locationName}/ {$user}@{$location->getIP()}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

				#$safe_cmd = escapeshellcmd($cmd);
				exec($cmd);
			}
		}
		else { //not-central server
			$microTime = $this->api->microTime();
			$cmd = "echo {$microTime} > {$dbSyncPath}{$centralServerName}/last_updated.txt";
			$this->api->exec($cmd);

			$cmd = "{$cmdPrefix} \
			      db_sync/{$centralServerName}/ {$user}@{$server}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

			#$safe_cmd = escapeshellcmd($cmd);
			exec($cmd);

		}
	}

	public function requestsAndResponses() {
		$this->cronTask->readAcksAndResponses(); //This method checks to whether or not it should read responses (only non-central servers should process responses)

		//Only the central server should process requests
		if ($this->api->getAppValue('centralServer') === $this->api->getAppValue('location')) {
			$this->requestResponse->processRequests();
		}
		else { //only the non-central servers should process responses
			$this->requestResponse->processResponses();
		}
	}

	public function run() {
		$this->cronTask->insertReceived();

		//Process
		$this->updateReceived->updateUsersWithReceivedUsers();
		$this->updateReceived->updateFriendshipsWithReceivedFriendships();
		$this->updateReceived->updateUserFacebookIdsWithReceivedUserFacebookIds();
		$this->updateReceived->updateFilecacheFromReceivedFilecaches();
		$this->cronTask->readAcksAndResponses(); //This method checks to whether or not it should read responses (only non-central servers should process responses)

		$this->requestsAndResponses();

		//Dump
		$this->cronTask->dumpResponses();
		$this->cronTask->dumpQueued();
		$this->cronTask->linkFiles();

		//Sync
		$this->sync();

		$this->cronTask->unlinkFiles();

	}
}
