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

use OCA\MultiInstance\Test\TestConstants;
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

	private function getFileList($dir, $recurse=false) {
                // array to hold return value
                $retval = array();

                // add trailing slash if missing
                if(substr($dir, -1) != "/") $dir .= "/";

                // open pointer to directory and read list of files
                $d = @dir($dir) or die("getFileList: Failed opening directory $dir for reading");
                while(false !== ($entry = $d->read())) {
                        // skip hidden files
                        if($entry[0] == ".") continue;
                        if(is_dir("$dir$entry")) {
                                $retval[] = array(
                                        "name" => "$dir$entry/",
                                        "type" => filetype("$dir$entry"),
                                        "size" => 0,
                                        "lastmod" => filemtime("$dir$entry")
                                );
                        if($recurse && is_readable("$dir$entry/")) {
                                $retval = array_merge($retval, $this->getFileList("$dir$entry/", true));
                        }
                        } elseif(is_readable("$dir$entry")) {
                                $retval[] = array(
                                        "name" => "$dir$entry",
                                        "type" => mime_content_type("$dir$entry"),
                                        "size" => filesize("$dir$entry"),
                                        "lastmod" => filemtime("$dir$entry")
                                );
                        }
                }
                $d->close();

                return $retval;
        }



	public function sync($transactionType=null, $param=null) {
		$thisLocation = $this->api->getAppValue('location');
		$centralServerName = $this->api->getAppValue('centralServer');
		$server = $this->api->getAppValue('centralServerIP');

		$output = $this->api->getAppValue('cronErrorLog');

		$dbSyncPath = $this->api->getAppValue('dbSyncPath');
		$dbSyncRecvPath = $this->api->getAppValue('dbSyncRecvPath');
		$user = $this->api->getAppValue('user');
		$rsyncPort = $this->api->getAppValue('rsyncPort');

		$locationList = $this->locationMapper->findAll();
		date_default_timezone_set($this->api->getAppValue('timezone'));
		$hour = date('H');
		//Note: we cannot use option --inplace.  We do not want incomplete files to be processed!
		//We can consider using the bandwidth limit option (--bwlimit) instead of tc
		$cmdPrefix = "rsync --verbose --compress --rsh='ssh -p{$rsyncPort}' \
				      --recursive --times --perms --copy-links --delete \
				      --group --partial --log-file=/tmp/rsynclog \
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

				//Run a full backup if after hours
	                        if ($hour === $this->api->getAppValue('backuphour')) {

                        	        $cmd = "{$cmdPrefix} \
					     db_sync/{$locationName}/ {$user}@{$location->getIP()}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";
                                	     #db_sync/{$centralServerName}/ {$user}@{$server}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

                                	exec($cmd);
        	                }
                	        else {

      	        	                $dirlist = $this->getFileList($dbSyncPath."/".$locationName, true);
        	                        foreach ($dirlist as $file) {
                                        	#if ($file['size'] < $this->api->getAppValue('filesizecutoff')) {
                                                	$filename = str_replace($dbSyncPath."/".$locationName."/","",$file['name']);
                                                	chdir($dbSyncPath."/".$locationName);
                                                	$cmd =  "{$cmdPrefix} -R \
                                                        	{$filename} {$user}@{$location->getIP()}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

                                                	exec($cmd);
                                        	#}

                                	}

                        	}




#				$cmd =  "{$cmdPrefix} \
#				      db_sync/{$locationName}/ {$user}@{$location->getIP()}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";
#
#				#$safe_cmd = escapeshellcmd($cmd);
#				exec($cmd);
			}
		}
		else { //not-central server
			$locationName = $centralServerName;
			$microTime = $this->api->microTime();
			$cmd = "echo {$microTime} > {$dbSyncPath}{$centralServerName}/last_updated.txt";
			$this->api->exec($cmd);


			//Run a full backup if after hours
			if ($hour === $this->api->getAppValue('backuphour')) {

                        	$cmd = "{$cmdPrefix} \
 	                             db_sync/{$centralServerName}/ {$user}@{$server}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

                                exec($cmd);
                      	}
                        else {

                        	$dirlist = $this->getFileList($dbSyncPath."/".$locationName, true);
                                foreach ($dirlist as $file) {
                                	#if ($file['size'] < $this->api->getAppValue('filesizecutoff')) {
                                        	$filename = str_replace($dbSyncPath."/".$centralServerName."/","",$file['name']);
                                                chdir($dbSyncPath."/".$centralServerName);
                                                $cmd =  "{$cmdPrefix} -R \
                                                	{$filename} {$user}@{$server}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

                                              	exec($cmd);
                                        #} 
					
                               	}

                    	}

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

	public function run($transactionType=null, $param=null) {

                if (is_null($transactionType)) {

                        $this->cronTask->insertReceived();

                        //Process
                        $this->updateReceived->updateUsersWithReceivedUsers();
			$this->updateReceived->updateGroupsWithReceivedGroups();
			$this->updateReceived->updateGroupUsersWithReceivedGroups();
                        $this->updateReceived->updateFriendshipsWithReceivedFriendships();
                        $this->updateReceived->updateUserFacebookIdsWithReceivedUserFacebookIds();
                        $this->updateReceived->updateFilecacheFromReceivedFilecaches();
                        $this->updateReceived->updatePermissionsFromReceivedPermissions();
                        $this->updateReceived->updateSharesWithReceivedShares();
                        $this->cronTask->readAcksAndResponses(); //This method checks to whether or not it should read responses (only non-central servers should process responses)

                        $this->requestsAndResponses();
                }

                if ($transactionType == TestConstants::EVENT_DRIVEN || $transactionType == TestConstants::CRON_DRIVEN) {
			$this->cronTask->insertReceived();
                        $this->cronTask->dumpQueued($transactionType, $param);
                        $this->cronTask->linkFiles($transactionType, $param);
                } else {
                        //Dump
                        $this->cronTask->dumpResponses();
                        $this->cronTask->dumpQueued();
                        $this->cronTask->linkFiles();
                }
                //Sync
                $this->sync($transactionType, $param);

                $this->cronTask->unlinkFiles($transactionType, $param);
		
		$time = microtime(true);
		if ($transactionType == TestConstants::EVENT_DRIVEN) {
			$cmd = "echo  {$time} >> /home/owncloud/public_html/apps/multiinstance/test/event.log";
		} else if ($transactionType == TestConstants::CRON_DRIVEN) {
			$cmd = "echo  {$time} >> /home/owncloud/public_html/apps/multiinstance/test/crondriven.log";
		}	
		shell_exec($cmd);
        }

}
