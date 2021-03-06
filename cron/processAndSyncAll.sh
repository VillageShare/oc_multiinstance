#!/bin/sh
###
# ownCloud - Multi Instance
#
# @author Sarah Jones
# @copyright 2013 Sarah Jones owncloud.e.p.jones@gmail.com
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
# License as published by the Free Software Foundation; either
# version 3 of the License, or any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU AFFERO GENERAL PUBLIC LICENSE for more details.
#
# You should have received a copy of the GNU Affero General Public
# License along with this library.  If not, see <http://www.gnu.org/licenses/>.
#
###
cd /home/owncloud/public_html/apps/multiinstance

if ps -ef | grep -v grep | grep processAndSyncAll.php ; then
	echo "processAndSyncAll.php is not starting because it is already running." >> /home/owncloud/public_html/apps/multiinstance/cron/error.txt 
        exit 0
else
	php5 /home/owncloud/public_html/apps/multiinstance/cron/processAndSyncAll.php >> /home/owncloud/public_html/apps/multiinstance/cron/error.txt &
        exit 0
fi
