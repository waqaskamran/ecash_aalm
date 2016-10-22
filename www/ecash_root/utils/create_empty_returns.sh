#!/bin/bash
#
# Create empty return and corrections files for each company
# for testing purposes.  This makes the returns and corrections
# scripts happy.
#
RETURNS_DIR='/home/achtestrc/returns/'
CORRECTIONS_DIR='/home/achtestrc/corrections/'

for i in CBN JY2 MDP OML PYA
do
	touch $RETURNS_DIR$i"`date +%m%d`A.CSV"
	touch $CORRECTIONS_DIR$i"`date +%m%d`A.CSV"
done
