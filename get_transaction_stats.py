#!/usr/bin/python

import sys, os
import math

filename = sys.argv[1]

f = open(filename, 'r')

linenum = 0
start = 0
end = 0
all_diff = []

for line in f:
	if ((linenum % 2) == 0):
		# even number is a start
		start = float(line)
	else:
		# odd number is an end
		end = float(line)
		all_diff.append(float(end - start))
	linenum += 1

# Pull out stats
# MEAN_DELTA, MEDIAN_DELTA, MODE_DELTA, RANGE, STD_DEV
all_diff = sorted(all_diff)
mean = sum(all_diff)/len(all_diff)
median = all_diff[len(all_diff)/2]
r = all_diff[-1] - all_diff[0]

print "=========================== SUMMARY ==========================="
print "mean: %s median: %s range: %s min: %s max: %s" % (mean, median, r, all_diff[0], all_diff[-1])
print "========================= END SUMMARY ========================="
