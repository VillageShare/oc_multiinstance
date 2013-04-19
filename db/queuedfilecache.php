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


class QueuedFileCache {

	private $storage;
	private $path;
	private $pathHash;
	private $addedAt;
	private $destinationLocation;

	public function __construct($storage, $path, $pathHash, $parent, $name, $mimetype, $mimepart, $size, $mtime, $encrypted, $etag, $added_at, $destination_location){
		$this->storage = $storage;
		$this->path = $path;
		$this->pathHash = $pathHash;
		$this->parent = $parent;
		$this->name = $name;
		if (is_int($mimetype)) {
			$cache = new \OC\Files\Cache\Cache($storage);
			$this->mimetype = $cache->getMimetype($mimetype);
		}
		else {
			$this->mimetype = $mimetype;
		}
		$this->mimepart = $mimepart;
		$this->size = $size;
		$this->mtime = $mtime;
		$this->encrypted = $encrypted;
		$this->etag = $etag;
		$this->addedAt = $addedAt;
		$this->destinationLocation = $destinationLocation;

	}

	public function getStorage(){
		return $this->storage;
	}

	public function getDisplayname(){
		return $this->path;
	}

	public function getPassword(){
		return $this->pathHash;
	}

	public function getAddedAt(){
		return $this->addedAt;
	}

	public function getDestinationLocation() {
		return $this->destinationLocation;
	}
}
