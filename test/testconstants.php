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

namespace OCA\MultiInstance\Test;

use OCA\AppFramework\Db\Entity;

class TestConstants extends Entity{

	/**
	 * Constants for transaction type
	 *
	 */
	const EVENT_DRIVEN = 0;
	const TIME_DRIVEN = 1;

	/**
	 * Constants for param type
	 * Which table  needs
	 * to be synchronized
	 */
	const USER = 3;
	const FRIENDSHIP = 4;
	const FILECACHE = 5;
	const SHARE = 6;
	const GROUP = 7;
	const GROUPADMIN = 8;
	const GROUPUSER = 9;
	
	$db_prefix = "oc_multiinstance";

	public function getTables($table) {
		$tables = array();
		switch($table) {
			case USER:
				$tables = array($db_prefix."_queued_users");
				break;
			case FRIENDSHIP:
				$tables = array($db_prefix."_queued_users", $db_prefix."_queued_friendships");
				break;
			case FILECACHE:
				$tables = array($db_prefix."_queued_users", $db_prefix."_queued_filecache");
				break;
			case SHARE:
				$tables = array($db_prefix."_queued_users", $db_prefix."_queued_groups", $db_prefix."_queued_groupuser", $db_prefix."_queued_filecache", $db_prefix."_queued_share");
				break;
			case GROUP:
				$tables = array($db_prefix."_queued_groups");
				break;
			case GROUPADMIN:
				$tables = array($db_prefix."_queued_users", $db_prefix."_queued_groups", $db_prefix."_queued_groupadmin");
				break;
			case GROUPUSER:
				$tables = array($db_prefix."_queued_users", $db_prefix."_queued_groups", $db_prefix."_queued_groupuser");
                                break;
		}
		return $tables;
	}

}
