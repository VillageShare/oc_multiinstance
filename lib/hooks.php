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
use OCA\MultiInstance\DependencyInjection\DIContainer;
use OCA\MultiInstance\Lib\MILocation;

/**
 * This class contains all hooks.
 */
class Hooks{

	//TODO: try catch with rollback
	static public function createUser($parameters) {
		$c = new DIContainer();
		$centralServerName = $c['API']->getAppValue('centralServer');
		$thisLocaiton = $c['API']->getAppValue('location');
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


}
