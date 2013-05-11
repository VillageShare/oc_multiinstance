<?php
/**
* ownCloud - Friends app
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


namespace OCA\MultiInstance\Core;

use  OCA\AppFramework\Core\API as API;

class MultiInstanceAPI extends API {
	public function getTime() {
		$date = new \DateTime("now");
                $date = date('Y-m-d H:i:s', $date->format('U') - $date->getOffset());
		return (string)$date;
	}

	public function beginTransaction() {
		\OCP\DB::beginTransaction();
	}

	public function commit() {
		\OCP\DB::commit();
	}

        public function userExists($uid) {
                return \OC_User::userExists($uid);
        }

	public function createUser($uid, $password) {
		return \OC_User::createUser($uid, $password);

	}

	public function getDisplayName($uid) {
		return \OC_User::getDisplayName($uid);
	}

	public function getPassword($uid) {
		$sql = "SELECT `password` FROM `*PREFIX*users` WHERE `uid` = ?";
		$params = array($uid);
		$query = $this->prepareQuery($sql);
                $result = $query->execute($params);
                $row = $result->fetchRow();
		return $row['password'];
	}

	public function setPassword($uid, $passwordHash) {
		$sql = "UPDATE `*PREFIX*users` SET `password` = ? WHERE `uid` = ?";
		$params = array($passwordHash, $uid);
		$query = $this->prepareQuery($sql);
                return $query->execute($params);
	}

	public function getShareStime($id) {
		$sql = "SELECT `stime` FROM `*PREFIX*share` WHERE `id` = ?";
		$params = array($id);
		$query = $this->prepareQuery($sql);
		$result = $query->execute($params);
		$row = $result->fetchRow();;
		return $row['stime'];
	}

	public function findShare($shareId) {
		$sql = "SELECT * FROM `*PREFIX*share` WHERE `id` = ?";
		$params = array($id);
		$query = $this->prepareQuery($sql);
		$result = $query->execute($params);
		return $result->fetchRow();;
	}

        public function fileGetContents($url) {
                return file_get_contents($url);
        }

	public function filePutContents($filename, $string) {
		return file_put_contents($filename, $string);
	}

	public function exec($cmd) {
		exec($cmd);
	}

	public function baseName($path) {
		return basename($path);
	}

	public function fileExists($path) {
		return file_exists($path);
	}

	public function microTime() {
		return microtime(true);
	}

	public function glob($pathAndPattern, $directoryOnly=null) {
		if ($directoryOnly) {
			return glob($pathAndPattern, GLOB_ONLYDIR);
		}
		return glob($pathAndPattern);
	}

	public function mkdir($pathname) {
		mkdir($pathname, 0775, true); 
	}
}	
