#!/bin/sh
#################################################################
# Author David Johnson
# Modified by Sarah Jones
# We want to police ingress in the following way: all traffic is
# moved to imqX that has two subclasses a HP one and a LP one.
# All the traffic from $IP_LP is classified into the LP class,
# and the rest of the traffic is classified into the HP class.
# The two classes can borrow bandwidth from each other in case
# one class does not have enough traffic.
#################################################################
 
INTERNET="eth1"	#VirtualBox uses eth1
IMQ="imq1"
SYNC_PORT=10001	#Rsync Port (alternate ssh listening port, also set in appinfo/app.php)
#IP_LP="192.168.1.22/32"
 
DL_RATE="1000kbit"
HP_RATE="900kbit"
LP_RATE="10kbit"
TC="tc"
IPTABLES="iptables"
IFCONFIG="ifconfig"
 
 # Loading the required modules
 # insmod ifb
 # insmod sch_htb
 # insmod sch_ingress
 # insmod ipt_IMQ
 # insmod act_mirred
 # insmod act_connmark
 # insmod cls_u32
 # insmod cls_fw
 # insmod em_u32
 
tc qdisc del dev imq1 root
 
# Bringing up the IMQ device
# $IFCONFIG $IMQ uphistor
 
# Adding the HTB scheduler to the ingress interface
$TC qdisc add dev $IMQ root handle 1: htb default
 
# add main rate limit classes
$TC class add dev $IMQ parent 1: classid 1:1 htb rate $DL_RATE
 
# add leaf classes: set the maximum bandwidth that each priority class can get, and the maximum borrowing they can do
$TC class add dev $IMQ parent 1:1 classid 1:10 htb rate $LP_RATE ceil $DL_RATE
$TC class add dev $IMQ parent 1:1 classid 1:11 htb rate $HP_RATE ceil $DL_RATE
 
# Filtering packets according to destination IP address
#$TC filter add dev $IMQ parent 1: protocol ip prio 1 u32 match ip dst $IP_LP flowid 1:10
 
# Filtering packets according to destination port
$TC filter add dev $IMQ parent 1: protocol ip prio 1 u32 match ip dport $SYNC_PORT 0xffff flowid 1:10
 
# Sending packets after SNAT has been done into the IMQ device
#$IPTABLES -t mangle -A FORWARD -i $INTERNET -j IMQ --todev 0
 
#tc class add dev imq0 parent 1:1 classid 1:3 htb ratekbit ceil0kbit leaf
#tc filter add dev imq0 parent 1: protocol ip prio 1 u32 match ip dport01 0xffff flowid 1:3

