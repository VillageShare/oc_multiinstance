#!/bin/bash

# Name of the traffic control command.
TC=/sbin/tc

# The network interface we're planning on limiting bandwidth.
IF=eth1        # Interface

# Download limit (in mega bits)
DNLD=10kbit          # DOWNLOAD Limit

# Upload limit (in mega bits)
UPLD=10kbit          # UPLOAD Limit

# Delay value
DELAY=1000ms
# delay plus-minus
PM=100ms

# IP address of the machine we are controlling
IP=192.168.56.102 # Host IP 192.168.0.11

# Filter options for limiting the intended interface.
U32="$TC filter add dev $IF protocol ip parent 1:0 prio 1 u32"


# We'll use Hierarchical Token Bucket (HTB) to shape bandwidth.
# For detailed configuration options, please consult Linux man
# page.

# Make sure previous traffic shaping is deleted (may give "file does not exist" etc if traffic shaping was not done before)
$TC qdisc del dev $IF root 
# Start traffic shaping
$TC qdisc add dev $IF root handle 1:0 htb default 30
# Bandwidth control
$TC class add dev $IF parent 1: classid 1:1 htb rate $DNLD
$TC class add dev $IF parent 1: classid 1:2 htb rate $UPLD
$U32 match ip dst $IP/32 flowid 1:1
$U32 match ip src $IP/32 flowid 1:2
# Delay control
$TC qdisc add dev $IF parent 1:1 netem delay $DELAY $PM distribution normal
$TC qdisc add dev $IF parent 1:2 netem delay $DELAY $PM distribution normal


# The first line creates the root qdisc, and the next two lines
# create two child qdisc that are to be used to shape download 
# and upload bandwidth.
#
# The 4th and 5th line creates the filter to match the interface.
# The 'dst' IP address is used to limit download speed, and the 
# 'src' IP address is used to limit upload speed.

$TC -s qdisc ls dev $IF

exit 0




