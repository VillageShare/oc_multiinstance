#!/bin/sh
TYPE=$1
ITER=$2
CASE=$3

if [ "$TYPE" == "0" ]; then
	SCRIPT=test/eventDrivenTransaction.sh
	FILE=event
fi

if [ "$TYPE" == "1" ]; then
        SCRIPT=test/cronDrivenTransaction.sh
	FILE=crondriven
fi

for i in `seq 1 $ITER`; do
	cd /home/owncloud/public_html/apps/multiinstance
	./cleanForTests.sh
	echo $(date -u +"%s") >> /home/owncloud/public_html/apps/multiinstance/test/$FILE.log
	./$SCRIPT $CASE
done
