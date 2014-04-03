#!/bin/sh
TYPE=$1
ITER=$2
LOCATION=$3
FILESIZE=$4

if [ "$TYPE" == "0" ]; then
	SCRIPT=test/eventDrivenTransaction.sh
	FILE=event
fi

if [ "$TYPE" == "1" ]; then
        SCRIPT=test/cronDrivenTransaction.sh
	FILE=crondriven
fi

for i in `seq 1 $ITER`; do
	# Clean
	./cleanForTests.sh
	sudo rm /home/owncloud/public_html/owncloud/data/test1@$LOCATION/files/*
	mysql -uroot -powncloud owncloud < test/cleanfiles.mysql

	# Move into users data directory
	cd /home/owncloud/public_html/owncloud/data/test1@$LOCATION/files/
	# Make a file with size FILESIZE
	#mkfile $FILESIZE $FILESIZE_$i
	sudo dd if=/dev/zero of=$FILESIZE_$i.out  bs=1M  count=$FILESIZE	

	# Simulate file upload by calling uploadfile.php
	php5 /home/owncloud/public_html/apps/multiinstance/test/uploadfile.php $FILESIZE_$i.out $LOCATION
	
	# Initial time
	echo $(date -u +"%s") >> /home/owncloud/public_html/apps/multiinstance/test/$FILE.log
	
	# If this is an event driven test, run the script immediately
	# Since cron driven is using cron as a trigger, we do not need
	# to worry about running it
	if [ "$TYPE" == "0" ]; then
		cd /home/owncloud/public_html/apps/multiinstance
		./$SCRIPT $TYPE 5 
	fi
	
	# Important that this is sleep and not wait because it 
	# more realistically portrays users who are not aware 
	# of processes that are already running
	sleep 30
done
