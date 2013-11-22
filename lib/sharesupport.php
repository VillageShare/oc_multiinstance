<?php

/**
 * ownCloud - Multi Instance
 *
 * @author Morgan Vigil
 * @copyright 2013 Morgan Vigil morgan.a.vigil@gmail.com
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

use \OCA\AppFramework\Db\Entity;
use \OCA\MultiInstance\Db\QueuedShare;
use \OCA\MultiInstance\Db\ShareUpdate;

use \OCA\MultiInstance\Lib\MILocation;
use \OC\Files\Cache\Cache;
use \OCA\MultiInstance\Db\QueuedFileCache;

class ShareSupport {

	/**
	 * Determine if shared files need to be pushed to the destination 
	 * server and push them
	 *
	 */
	static public function pushSharedFile($api, $locationMapper, $receivedShare) {

		$thisLocation = $api->getAppValue('location');
		$centralServer = $api->getAppValue('centralServer');
		$dest_location = MILocation::getUidLocation($receivedShare->getShareWith(), $mockLocationMapper);
		$orig_location = MILocation::getUidLocation($receivedShare->getUidOwner(), $mockLocationMapper);

                $output = $api->getAppValue('cronErrorLog');

                $dbSyncPath = $api->getAppValue('dbSyncPath');
                $dbSyncRecvPath = $api->getAppValue('dbSyncRecvPath');
		$user = $api->getAppValue('user');
                $rsyncPort = $api->getAppValue('rsyncPort');

		$cmdPrefix = "rsync --verbose --compress --rsh='ssh -p{$rsyncPort}' \
                                      --recursive --times --perms --copy-links --delete \
                                      --group --partial --log-file=/tmp/rsynclog \
                                      --exclude \"last_read.txt\"";

		// Only push files to a share recipient when
		// you are at the central server. The central
		// server has everyone's information and should
		// have a file for everyone. This also ensures that
		// everything passes through the central server and does not circumvent it.	
		if ($thisLocation == $centralServer) {
			// Push files only if they are between two different remote servers
			if (($orig_location !== $dest_location) && ($dest_location !== $centralServer)) {
				try{ 	
					$filetarget = $receivedShare->slugify('file_target'); // Slugify for rsync
					$filename = str_replace($dbSyncPath."/".$dest_location."/","",$fileTarget);
                                	chdir($dbSyncPath."/".$dest_location);
                                	$cmd =  "{$cmdPrefix} -R \ {$filename} {$user}@{$locationMapper->findIPByLocation($dest_location)}:{$dbSyncRecvPath}/{$thisLocation} >>{$output} 2>&1";

                                	exec($cmd);
				} catch (\BadFunctionCallException $e) {
					return false;
				}
				return true;
				
			}
		}  
		return false;
	}

}
