<?php

/**
* ownCloud - App Template plugin
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

namespace OCA\MultiInstance;


\OCP\App::registerAdmin('multiinstance', 'admin/settings');

//No Nav Entry because this app does not have an UI

//This instance's location settings
//location name should be in Linux file format (no spaces, etc), as it will be used as a folder name
\OCP\Config::setAppValue('multiinstance', 'location', 'Macha');
//IP address
\OCP\Config::setAppValue('multiinstance', 'ip', '192.168.56.102');

//ip or domain name of UCSB server (or whatever the central server is)
\OCP\Config::setAppValue('multiinstance', 'centralServerIP', '192.168.56.101');
\OCP\Config::setAppValue('multiinstance', 'centralServer', 'UCSB');


//path to apps/multiinstance/cron/error.txt
$errorLog =  "/home/sarah/public_html/apps/multiinstance/cron/error.txt";
\OCP\Config::setAppValue('multiinstance', 'cronErrorLog', $errorLog);
//path to apps/multiinstance/db_sync_recv/ PATH MUST HAVE ENDING SLASH
$dbSyncRecvPath = "/home/sarah/public_html/apps/multiinstance/db_sync_recv/";
\OCP\Config::setAppValue('multiinstance', 'dbSyncRecvPath', $dbSyncRecvPath);
$dbSyncFolder = "/home/sarah/public_html/apps/multiinstance/db_sync/";
\OCP\Config::setAppValue('multiinstance', 'dbSyncPath', $dbSyncFolder);
$rsyncPort = 10001;
\OCP\Config::setAppValue('multiinstance', 'rsyncPort', $rsyncPort);


//This user should be the same for all instances in the network (this user contains the code and will be used for rsync)
\OCP\Config::setAppValue('multiinstance', 'user', 'owncloud');



\OCP\Util::connectHook('OC_User', 'post_createUser', 'OCA\MultiInstance\Lib\Hooks', 'createUser');
\OCP\Util::connectHook('OC_User', 'post_setPassword', 'OCA\MultiInstance\Lib\Hooks', 'updateUser');
\OCP\Util::connectHook('OCP\Share', 'post_shared', 'OCA\MultiInstance\Lib\Hooks', 'queueShareAdd');
\OCP\Util::connectHook('FriendshipMapper', 'post_request', 'OCA\MultiInstance\Lib\Hooks', 'updateFriendship');
\OCP\Util::connectHook('FriendshipMapper', 'post_accept', 'OCA\MultiInstance\Lib\Hooks', 'updateFriendship');
\OCP\Util::connectHook('FriendshipMapper', 'post_create', 'OCA\MultiInstance\Lib\Hooks', 'updateFriendship');
\OCP\Util::connectHook('FriendshipMapper', 'post_delete', 'OCA\MultiInstance\Lib\Hooks', 'updateFriendship');
\OCP\Util::connectHook('Cache', 'post_put', 'OCA\MultiInstance\Lib\Hooks', 'queueFile');
\OCP\Util::connectHook('Cache', 'post_update', 'OCA\MultiInstance\Lib\Hooks', 'queueFileUpdate');
\OCP\Util::connectHook('Cache', 'post_delete', 'OCA\MultiInstance\Lib\Hooks', 'queueFileDelete');
\OCP\Util::connectHook('Permissions', 'post_set', 'OCA\MultiInstance\Lib\Hooks', 'queuePermissionUpdate');
\OCP\Util::connectHook('Permissions', 'post_remove', 'OCA\MultiInstance\Lib\Hooks', 'queuePermissionDelete');

