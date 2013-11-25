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
use \OCA\MultiInstance\Db\LocationMapper;
use \OCA\MultiIntance\Core\MuliInstanceAPI;
use \OCA\MultiInstance\Lib\MILocation;
use \OC\Files\Cache\Cache;
use \OCA\MultiInstance\Db\QueuedFileCache;
use \OCA\MultiInstance\DependencyInjection\DIContainer;

class ShareSupport {

	/**
	 * Determine if shared files need to be pushed to the destination 
	 * server and push them
	 *
	 */
	static public function pushSharedFile($receivedShare) {

		$di = new DIContainer();
                $locationMapper = $di['LocationMapper'];
		$api = $di['API'];

		$fname = "updatereceive.log";
                $cmd = "echo \"In ShareSupport::pushSharedFile.\" >> {$fname}";
                $api->exec($cmd);

		$thisLocation = $api->getAppValue('location');
		$centralServer = $api->getAppValue('centralServer');
		$dest_location = MILocation::getUidLocation($receivedShare->getShareWith(), $mockLocationMapper);
		$orig_location = MILocation::getUidLocation($receivedShare->getUidOwner(), $mockLocationMapper);

                $output = $api->getAppValue('cronErrorLog');

                $dbSyncPath = $api->getAppValue('dbSyncPath');
                $dbSyncRecvPath = $api->getAppValue('dbSyncRecvPath');
		$dataPath = $api->getSystemValue('datadirectory');
		$user = $api->getAppValue('user');
                $rsyncPort = $api->getAppValue('rsyncPort');

		$cmdPrefix = "rsync --verbose --compress --rsh='ssh -p{$rsyncPort}' \
                                      --times --perms --copy-links --recursive --delete \
                                      --group --partial --log-file=/tmp/sharersynclog";

		// Only push files to a share recipient when
		// you are at the central server. The central
		// server has everyone's information and should
		// have a file for everyone. This also ensures that
		// everything passes through the central server and does not circumvent it.	
		if ($thisLocation == $centralServer) {
			// Push files only if they are between two different remote servers
			if (($orig_location !== $dest_location) && ($dest_location !== $centralServer)) {
				try{ 	
					$fname = "updatereceive.log";
                			$cmd = "echo \"In ShareSupport::pushSharedFile: In the correct location to initiate a push.\" >> {$fname}";
                			$api->exec($cmd);
					$filetarget = $receivedShare->slugify('fileTarget'); // Slugify for rsync
					$filename  = trim($receivedShare->getFileTarget(), "/");
					$fname = "updatereceive.log";
                                        $cmd = "echo \"In ShareSupport::pushSharedFile\nfiletarget: {$filetarget}\nfilename: {$filename}.\" >> {$fname}";
                                        $api->exec($cmd);
                                	if(!chdir($dataPath."/".$receivedShare->getUidOwner()."/files/")) {
						$fname = "updatereceive.log";
                                        	$cmd = "echo \"Could not change into this directory: {$dataPath}/{$receivedShare->getUidOwner()}/files/\" >> {$fname}";
                                        	$api->exec($cmd);
					}
					if(!copy($filename, $filetarget)) {
						$fname = "updatereceive.log";
                                                $cmd = "echo \"Could not copy\" >> {$fname}";
                                                $api->exec($cmd);
					}
                                	$cmd =  "{$cmdPrefix} -R {$filetarget} {$user}@{$locationMapper->findIPByLocation($dest_location)}:{$dbSyncRecvPath}/{$thisLocation} >> {$output} 2>&1";

                                	exec($cmd);
				} catch (\BadFunctionCallException $e) {
					// TODO abstract this to refer to a system value or something
                                        chdir("/home/owncloud/public_html/apps/multiinstance");
					$fname = "updatereceive.log";
                			$cmd = "echo \"In ShareSupport::pushSharedFile: Exception {$e->getMessage()}.\" >> {$fname}";
                			$api->exec($cmd);
					return false;
				} catch (\Exception $e) {
					// TODO abstract this to refer to a system value or something
                                        chdir("/home/owncloud/public_html/apps/multiinstance");
					$fname = "updatereceive.log";
                                        $cmd = "echo \"In ShareSupport::pushSharedFile: Exception {$e->getMessage()}.\" >> {$fname}";
                                        $api->exec($cmd);
                                        return false;
				}
				// TODO abstract this to refer to a system value or something
                                chdir("/home/owncloud/public_html/apps/multiinstance");
				return true;
				
			}
		}  
		return false;
	}

}
