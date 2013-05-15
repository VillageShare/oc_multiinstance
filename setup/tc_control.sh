  1 #!/bin/sh
  2 #################################################################
    # Aauthor David Johnson
    # Modified by Sarah Jones
  3 # We want to police ingress in the following way: all traffic is
  4 # moved to imqX that has two subclasses a HP one and a LP one.
  5 # All the traffic from $IP_LP is classified into the LP class,
  6 # and the rest of the traffic is classified into the HP class.
  7 # The two classes can borrow bandwidth from each other in case
  8 # one class does not have enough traffic.
  9 #################################################################
 10 
 11 INTERNET="eth1"	#VirtualBox uses eth1
 12 IMQ="imq1"
 13 SYNC_PORT=10001	#Rsync Port (alternate ssh listening port, also set in appinfo/app.php)
 14 #IP_LP="192.168.1.22/32"
 15 
 16 DL_RATE="1000kbit"
 17 HP_RATE="900kbit"
 18 LP_RATE="10kbit"
 19 TC="tc"
 20 IPTABLES="iptables"
 21 IFCONFIG="ifconfig"
 22 
 23 # Loading the required modules
 24 # insmod ifb
 25 # insmod sch_htb
 26 # insmod sch_ingress
 27 # insmod ipt_IMQ
 28 # insmod act_mirred
 29 # insmod act_connmark
 30 # insmod cls_u32
 31 # insmod cls_fw
 32 # insmod em_u32
 33 
 34 tc qdisc del dev imq1 root
 35 
 36 # Bringing up the IMQ device
 37 # $IFCONFIG $IMQ uphistor
 38 
 39 # Adding the HTB scheduler to the ingress interface
 40 $TC qdisc add dev $IMQ root handle 1: htb default 11
 41 
 42 # add main rate limit classes
 43 $TC class add dev $IMQ parent 1: classid 1:1 htb rate $DL_RATE
 44 
 45 # add leaf classes: set the maximum bandwidth that each priority class can get, and the maximum borrowing they can do
 46 $TC class add dev $IMQ parent 1:1 classid 1:10 htb rate $LP_RATE ceil $DL_RATE
 47 $TC class add dev $IMQ parent 1:1 classid 1:11 htb rate $HP_RATE ceil $DL_RATE
 48 
 49 # Filtering packets according to destination IP address
 50 #$TC filter add dev $IMQ parent 1: protocol ip prio 1 u32 match ip dst $IP_LP flowid 1:10
 51 
 52 # Filtering packets according to destination port
 53 $TC filter add dev $IMQ parent 1: protocol ip prio 1 u32 match ip dport $SYNC_PORT 0xffff flowid 1:10
 54 
 55 # Sending packets after SNAT has been done into the IMQ device
 56 #$IPTABLES -t mangle -A FORWARD -i $INTERNET -j IMQ --todev 0
 57 
 58 #tc class add dev imq0 parent 1:1 classid 1:3 htb rate 10kbit ceil 300kbit leaf 10
 59 #tc filter add dev imq0 parent 1: protocol ip prio 1 u32 match ip dport 5001 0xffff flowid 1:3

