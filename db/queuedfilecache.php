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


class QueuedFileCache extends Entity {

	const DELETE = 0;
	const CREATE = 1;
	const RENAME = 2;
	const UPDATE = 3;

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

	public function __construct($fileid, $storage=null, $path=null, $pathVar=null, $name=null, $mimetype=null, $mimepart=null, $size=null, $mtime=null, $encrypted=null, $etag=null, $addedAt=null, $queueType=null, $destinationLocation=null, $sendingLocation=null){
		if ($storage) {
			$this->setFileid($fileid);
			$this->setStorage($storage);
			$this->setPath($path);
			$this->setPathVar($pathVar);
			$this->setName($name);
			$this->setMimetype($mimetype);
			$this->setMimepart($mimepart);
			$this->setSize($size);
			$this->setMtime($mtime);
			$this->setEncrypted($encrypted);
			$this->setEtag($etag);
			$this->setAddedAt($addedAt);
			$this->setQueueType($queueType);
			$this->setDestinationLocation($destinationLocation);
			$this->setSendingLocation($sendingLocation);
		}
		else {
			$this->fromRow($fileid);
		}
	}

}
