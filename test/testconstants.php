<?php
/**
* ownCloud - App Template Example
*
* @author Morgan
* @copyright 2014 Morgan Vigil morgan.a.vigil@@gmail.com
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
	const CRON_DRIVEN = 1;

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
	const DEACTIVATEUSER = 10;
		
	const PREFIX = "multiinstance";
	
	// TODO: arrange table arrays as (multiinstance_queued* => multiinstance_received*)
	public static function getTables($table) {

		$tables = array();
		switch($table) {
			case self::USER:
				$tables = array(self::PREFIX."_queued_users" => self::PREFIX."_received_users");
				break;
			case self::FRIENDSHIP:
				$tables = array(self::PREFIX."_queued_users" => self::PREFIX."_received_users", self::PREFIX."_queued_friendships" => self::PREFIX."_received_friendships");
				break;
			case self::FILECACHE:
				$tables = array(self::PREFIX."_queued_users" => self::PREFIX."_received_users", self::PREFIX."_queued_filecache" => self::PREFIX."_received_filecache", self::PREFIX."_queued_permissions" => self::PREFIX."_received_permissions");
				break;
			case self::SHARE:
				$tables = array(self::PREFIX."_queued_users" => self::PREFIX."_received_users", self::PREFIX."_queued_groups" => self::PREFIX."_received_groups", self::PREFIX."_queued_groupuser" => self::PREFIX."_received_groupuser", self::PREFIX."_queued_filecache" => self::PREFIX."_received_filecache",  self::PREFIX."_queued_permissions" => self::PREFIX."_received_permissions", self::PREFIX."_queued_share" => self::PREFIX."_received_share");
 
				break;
			case self::GROUP:
				$tables = array(self::PREFIX."_queued_groups" => self::PREFIX."_received_groups");
				break;
			case self::GROUPADMIN:
				$tables = array(self::PREFIX."_queued_users" => self::PREFIX."_received_users", self::PREFIX."_queued_groups" => self::PREFIX."_received_groups", self::PREFIX."_queued_groupadmin" => self::PREFIX."_received_groupadmin");
				break;
			case self::GROUPUSER:
				$tables = array(self::PREFIX."_queued_users" => self::PREFIX."_received_users", self::PREFIX."_queued_groups" => self::PREFIX."_received_groups", self::PREFIX."_queued_groupuser" =>  self::PREFIX."_received_groupuser");
                                break;
			case self::DEACTIVATEUSER:
				$tables = array(self::PREFIX."_queued_deactivatedusers" => self::PREFIX."_received_deactivatedusers");
				break;
		}
		return $tables;
	}

}
