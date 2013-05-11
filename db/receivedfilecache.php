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

namespace OCA\MultiInstance\Db;


use \OCA\AppFramework\Db\Entity;


class ReceivedFileCache extends Entity {

	public $fileid;
	public $storage;
	public $path;
	public $pathVar;  //can be the parentPath (if a new file), or the new file name (if rename)
	public $name;
	public $mimetype;
	public $mimepart;
	public $size;
	public $mtime;
	public $encrypted;
	public $etag;
	public $addedAt;
	public $queueType;
	public $destinationLocation;
	public $sendingLocation;

	public function __construct($row){
		$this->fromRow($row);
	}

}
