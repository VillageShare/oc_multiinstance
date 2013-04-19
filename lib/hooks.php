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

/**
 * This class contains all hooks.
 */
class Hooks{

	//TODO: try catch with rollback
	static public function createUser($parameters) {
		$c = new DIContainer();
		$centralServerName = $c['API']->getAppValue('centralServer');
		if ( $centralServerName !== $c['API']->getAppValue('location')) {
			$uid = $parameters['uid'];
			$displayname = '';
			$password = $parameters['password'];
			
			$date = $c['API']->getTime();
			$queuedUser = new QueuedUser($uid, $displayname, $password, $date, $centralServerName);
			$userUpdate = new UserUpdate($uid, $date, $centralServerName);
			$c['API']->beginTransaction();
			$c['QueuedUserMapper']->save($queuedUser);
			$c['UserUpdateMapper']->save($userUpdate);
			$c['API']->commit();
		}
	}

	static public function updateUser($parameters) {
		$c = new DIContainer();
		$centralServerName = $c['API']->getAppValue('centralServer');
		if ($centralServerName !== $c['API']->getAppValue('location')) {
			$uid = $parameters['uid'];
			$displayname = '';
			$password = $parameters['password'];
			$date = $c['API']->getTime();
			$queuedUser = new QueuedUser($uid, $displayname, $password, $date, $centralServerName);
			$userUpdate = new UserUpdate($uid, $date, $centralServerName);

			$c['API']->beginTransaction();
			$c['QueuedUserMapper']->save($queuedUser);
			$c['UserUpdateMapper']->update($userUpdate);
			$c['API']->commit();
		}	
	}


}
