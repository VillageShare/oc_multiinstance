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

namespace OCA\MultiInstance\DependencyInjection;

use OCA\AppFramework\DependencyInjection\DIContainer as BaseContainer;

use OCA\MultiInstance\Controller\CronController;
use OCA\MultiInstance\Controller\SettingsController;
use OCA\MultiInstance\Db\QueuedUserMapper;
use OCA\MultiInstance\Db\ReceivedUserMapper;
use OCA\MultiInstance\Db\UserUpdateMapper;

use OCA\Friends\Db\FriendshipMapper;

use OCA\MultiInstance\Db\QueuedFriendshipMapper;
use OCA\MultiInstance\Db\ReceivedFriendshipMapper;
use OCA\Friends\Db\UserFacebookIdMapper;
use OCA\MultiInstance\Db\QueuedUserFacebookIdMapper;
use OCA\MultiInstance\Db\ReceivedUserFacebookIdMapper;

use OCA\MultiInstance\Db\QueuedRequestMapper;
use OCA\MultiInstance\Db\ReceivedRequestMapper;
use OCA\MultiInstance\Db\QueuedResponseMapper;
use OCA\MultiInstance\Db\ReceivedResponseMapper;

use OCA\MultiInstance\Db\QueuedFileCacheMapper;
use OCA\MultiInstance\Db\ReceivedFileCacheMapper;
use OCA\MultiInstance\Db\FilecacheUpdateMapper;
use OCA\MultiInstance\Db\QueuedPermissionMapper;
use OCA\MultiInstance\Db\ReceivedPermissionMapper;
use OCA\MultiInstance\Db\PermissionUpdateMapper;
use OCA\MultiInstance\Db\QueuedShareMapper;

use OCA\MultiInstance\Db\LocationMapper;

use OCA\MultiInstance\Core\MultiInstanceAPI;
use OCA\MultiInstance\Core\CronTask;
use OCA\MultiInstance\Core\CronHelper;
use OCA\MultiInstance\Core\UpdateReceived;
use OCA\MultiInstance\Core\RequestResponse;

use OCA\MultiInstance\Lib\Hooks;
use OCA\MultiInstance\Lib\Location;

class DIContainer extends BaseContainer {


	/**
	 * Define your dependencies in here
	 */
	public function __construct(){
		// tell parent container about the app name
		parent::__construct('multiinstance');

		$this['API'] = $this->share(function($c){
			return new MultiInstanceAPI($c["AppName"]);
		});

		/**
		 * Delete the following twig config to use ownClouds default templates
		 */
		// use this to specify the template directory
		$this['TwigTemplateDirectory'] = __DIR__ . '/../templates';

		// if you want to cache the template directory in yourapp/cache
		// uncomment this line. Remember to give your webserver access rights
		// to the cache folder 
		// $this['TwigTemplateCacheDirectory'] = __DIR__ . '/../cache';		

		/** 
		 * CONTROLLERS
		 */

		$this['SettingsController'] = $this->share(function($c){
			return new SettingsController($c['API'], $c['Request']);
		});


		/**
		 * MAPPERS
		 */
		$this['LocationMapper'] = $this->share(function($c){
			return new LocationMapper($c['API']);
			
		});

		$this['QueuedUserMapper'] = $this->share(function($c){
			return new QueuedUserMapper($c['API']);
		});

		$this['ReceivedUserMapper'] = $this->share(function($c){
			return new ReceivedUserMapper($c['API']);
		});

		$this['UserUpdateMapper'] = $this->share(function($c){
			return new UserUpdateMapper($c['API']);
			
		});
		$this['FriendshipMapper'] = $this->share(function($c){
			return new FriendshipMapper($c['API']);
		});

		$this['QueuedFriendshipMapper'] = $this->share(function($c){
			return new QueuedFriendshipMapper($c['API']);
			
		});
		$this['ReceivedFriendshipMapper'] = $this->share(function($c){
			return new ReceivedFriendshipMapper($c['API']);
		});
		$this['UserFacebookIdMapper'] = $this->share(function($c){
			return new UserFacebookIdMapper($c['API']);
		});
		$this['QueuedUserFacebookIdMapper'] = $this->share(Function($c){
			return new QueuedUserFacebookIdMapper($c['API']);
		});
		$this['ReceivedUserFacebookIdMapper'] = $this->share(Function($c){
			return new ReceivedUserFacebookIdMapper($c['API']);
		});
		
		$this['QueuedRequestMapper'] = $this->share(function($c){
			return new QueuedRequestMapper($c['API']);
		});
		$this['ReceivedRequestMapper'] = $this->share(function($c){
			return new ReceivedRequestMapper($c['API']);
		});
		$this['QueuedResponseMapper'] = $this->share(function($c){
			return new QueuedResponseMapper($c['API']);
		});
		$this['ReceivedResponseMapper'] = $this->share(function($c){
			return new ReceivedResponseMapper($c['API']);
		});
				
		$this['QueuedFileCacheMapper'] = $this->share(function($c){
			return new QueuedFileCacheMapper($c['API']);
		});
		$this['ReceivedFileCacheMapper'] = $this->share(function($c){
			return new ReceivedFileCacheMapper($c['API']);
		});
		$this['FilecacheUpdateMapper'] = $this->share(function($c){
			return new FilecacheUpdateMapper($c['API']);
		});
		$this['QueuedPermissionMapper'] = $this->share(function($c){
			return new QueuedPermissionMapper($c['API']);
		});
		$this['ReceivedPermissionMapper'] = $this->share(function($c){
			return new ReceivedPermissionMapper($c['API']);
		});
		$this['PermissionUpdateMapper'] = $this->share(function($c){
			return new PermissionUpdateMapper($c['API']);
		});
		$this['QueuedShareMapper'] = $this->share(function($c){
			return new QueuedShareMapper($c['API']);
		});

		/**
		 * Core
		 */
		$this['CronHelper'] = $this->share(function($c){
			return new CronHelper($c['API'], $c['LocationMapper'], $c['CronTask'], $c['UpdateReceived'], $c['RequestResponse']);
			
		});
		$this['CronTask'] = $this->share(function($c){
			return new CronTask($c['API'], $c['LocationMapper'], $c['QueuedResponseMapper'], $c['QueuedFileCacheMapper']);
			
		});
		$this['UpdateReceived'] = $this->share(function($c){
			return new UpdateReceived($c['API'], $c['ReceivedUserMapper'], $c['UserUpdateMapper'], $c['ReceivedFriendshipMapper'], $c['UserFacebookIdMapper'], $c['ReceivedUserFacebookIdMapper'], $c['FriendshipMapper'], $c['QueuedFriendshipMapper'], $c['QueuedUserMapper'], $c['LocationMapper'], $c['ReceivedFileCacheMapper'], $c['FilecacheUpdateMapper']);
			
		});
		$this['RequestResponse'] = $this->share(function($c){
			return new RequestResponse($c['API'], $c['UserUpdateMapper'], $c['ReceivedResponseMapper'], $c['ReceivedRequestMapper'], $c['QueuedResponseMapper'], $c['QueuedRequestMapper'], $c['QueuedUserMapper'], $c['FriendshipMapper']);
			
		});
	}
}

