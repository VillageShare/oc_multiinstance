#!/bin/sh
###
# ownCloud - Multi Instance
#
# @author Sarah Jones
# @copyright 2013 Sarah Jones sarah.e.p.jones@gmail.com
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


if ps -ef | grep -v grep | grep processAndSyncAll.php ; then
	echo "processAndSyncAll.php is not starting because it is already running." >> /home/sarah/public_html/apps/multiinstance/cron/error.txt 
        exit 0
else
	#Change this path to be the path to multiinstance/cron/processAndSyncAll.php
	php5 /home/sarah/public_html/apps/multiinstance/cron/processAndSyncAll.php >> /home/sarah/public_html/apps/multiinstance/cron/error.txt &
        exit 0
fi
