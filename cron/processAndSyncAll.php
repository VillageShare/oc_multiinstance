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

namespace OCA\MultiInstance\Cron;


require_once(__DIR__ . "/../tests/classloader.php");
require_once(__DIR__ . "/../../../owncloud/lib/public/config.php");
require_once(__DIR__ . "/../../../owncloud/lib/config.php");
require_once(__DIR__ . "/../../../owncloud/lib/base.php");

use OCA\MultiInstance\DependencyInjection\DIContainer;
use OCA\MultiInstance\Lib\MILocation;

/**
 * rsync with UCSB server
 */



$dicontainer = new DIContainer();

$dicontainer['CronHelper']->run();
