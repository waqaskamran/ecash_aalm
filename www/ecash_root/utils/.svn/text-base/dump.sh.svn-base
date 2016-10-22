#!/bin/bash
# dumps the tables in the group(s) selected

###############
## FUNCTIONS ##
###############

# Prints an error message for the user (non-fatal)
# @param string $1 Message to print
function Dump_Error
{
	MESSAGE=$1
	if [ "${2}" ]
	then
		ROW=$2
	else
		if [ "${CROW}" ]
		then
			ROW=$((${CROW}+2))
		else
			ROW=${ROWS}
		fi
	fi

	ERASE=""
	for (( START=0, LENGTH=${#MESSAGE} ; ${START} <= ${LENGTH} ; START++ ))
	do
		ERASE="${ERASE} "
	done

	tp $ROW 10 ; echo "${MESSAGE}"
	sleep 1
	tp $ROW 10 ; echo "${ERASE}"
}

# @param integer[optional] $1 row
# @param integer[optional] $2 column
function tp
{
	if [ ! "${1}" -o "${1}" = 'clear' ]
	then
		tput clear
		CROW=0
	else
		tput cup $1 $2
	fi
}

# Displays the tables (Schema only, data, ignore) in 3 columns
function display_tables
{
	local SCHEMA_WIDTH=17   # strlen() of longest schema table name
	local SCHEMA_S_WIDTH=4  # strlen() of longest schema table size
	local DATA_WIDTH=12     # strlen() of longest data   table name
	local DATA_S_WIDTH=4    # strlen() of longest data   table size
	local IGNORE_WIDTH=13   # strlen() of longest ignore table name
	local IGNORE_S_WIDTH=4  # strlen() of longest ignore table size
	local NUMBER_WIDTH=2
	local SROW=$CROW        # Starting row
	local LROW=$CROW        # Current  row
	local MAXROW            # Maximum  row reached
	local SCOL=2            # Schema table name column
	local DCOL              # Data   table name column
	local ICOL              # Ignore table name column
	local SSCOL             # Schema table size column
	local DSCOL             # Data   table size column
	local ISCOL             # Ignore table size column

	# Get longest table index
	TEMP=${#TABLES[@]}
	NUMBER_WIDTH=${#TEMP}

	# Get width for schema column
	KEYS=(${!SCHEMA[@]})
	COUNT=0
	for S in ${SCHEMA[@]}
	do
		KEY=${KEYS[${COUNT}]}

		if [ ${SCHEMA_WIDTH} -lt ${#S} ]
		then
			SCHEMA_WIDTH=${#S}
		fi

		TEMP=${SIZES[${KEY}]}
		if [ ${SCHEMA_S_WIDTH} -lt ${#TEMP} ]
		then
			SCHEMA_S_WIDTH=${#TEMP}
		fi

		COUNT=$((${COUNT}+1))
	done
	SCHEMA_WIDTH=$((${SCHEMA_WIDTH}+4))

	# Get width for data column
	KEYS=(${!DATA[@]})
	COUNT=0
	for D in ${DATA[@]}
	do
		KEY=${KEYS[${COUNT}]}

		if [ ${DATA_WIDTH} -lt ${#D} ]
		then
			DATA_WIDTH=${#D}
		fi

		TEMP=${SIZES[${KEY}]}
		if [ ${DATA_S_WIDTH} -lt ${#TEMP} ]
		then
			DATA_S_WIDTH=${#TEMP}
		fi

		COUNT=$((${COUNT}+1))
	done
	DATA_WIDTH=$((${DATA_WIDTH}+4))

	# Get width for ignore column
	KEYS=(${!IGNORE[@]})
	COUNT=0
	for I in ${IGNORE[@]}
	do
		KEY=${KEYS[${COUNT}]}

		if [ ${IGNORE_WIDTH} -lt ${#I} ]
		then
			IGNORE_WIDTH=${#I}
		fi

		TEMP=${SIZES[${key}]}
		if [ ${IGNORE_S_WIDTH} -lt ${#TEMP} ]
		then
			IGNORE_S_WIDTH=${#TEMP}
		fi

		COUNT=$((${COUNT}+1))
	done
	IGNORE_WIDTH=$((${IGNORE_WIDTH}+4))

	# Where columns start
	SSCOL=$((${SCOL}+${SCHEMA_WIDTH}+1))
	DCOL=$((${SSCOL}+${SCHEMA_S_WIDTH}+4))
	DSCOL=$((${DCOL}+${DATA_WIDTH}+1))
	ICOL=$((${DSCOL}+${DATA_S_WIDTH}+4))
	ISCOL=$((${ICOL}+${IGNORE_WIDTH}+1))

	# Header info
	LROW=${SROW}
	tp $LROW $SCOL  ; echo "Tables"
	LROW=$((${LROW}+1))
	tp $LROW $SCOL  ; echo "Schema only tables"
	tp $LROW $SSCOL ; echo "  Size"
	tp $LROW $DCOL  ; echo "Data tables"
	tp $LROW $DSCOL ; echo "  Size"
	tp $LROW $ICOL  ; echo "Ignore tables"
	tp $LROW $ISCOL ; echo "  Size"
	LROW=$((${LROW}+1))
	tp $LROW $SCOL
	for (( X=${SCOL} ; X <= ${ISCOL}+${IGNORE_S_WIDTH}+1 ; X++ ))
	do
		printf "="
	done

	LROW=$((${LROW}+1))
	SROW=${LROW}

	# Print schema column
	KEYS=(${!SCHEMA[@]})
	COUNT=0
	for S in ${SCHEMA[@]}
	do
		KEY=${KEYS[${COUNT}]}
		PKEY=${KEY}
		while [ ${#PKEY} -lt ${NUMBER_WIDTH} ]
		do
			PKEY=" ${PKEY}"
		done
		tp $LROW $SCOL  ; echo "${PKEY}) ${S} "
		tp $LROW $SSCOL ; echo ": ${SIZES[${KEY}]}"
		LROW=$((${LROW}+1))
		COUNT=$((${COUNT}+1))
	done

	MAXROW=${LROW}

	# Print data column
	LROW=${SROW}
	KEYS=(${!DATA[@]})
	COUNT=0
	for D in ${DATA[@]}
	do
		KEY=${KEYS[${COUNT}]}
		PKEY=${KEY}
		while [ ${#PKEY} -lt ${NUMBER_WIDTH} ]
		do
			PKEY=" ${PKEY}"
		done
		tp $LROW $DCOL  ; echo "${PKEY}) ${D} "
		tp $LROW $DSCOL ; echo ": ${SIZES[${KEY}]}"
		LROW=$((${LROW}+1))
		COUNT=$((${COUNT}+1))
	done

	if [ ${MAXROW} -lt ${LROW} ]
	then
		MAXROW=${LROW}
	fi

	# Print ignore column
	LROW=${SROW}
	KEYS=(${!IGNORE[@]})
	COUNT=0
	for I in ${IGNORE[@]}
	do
		KEY=${KEYS[${COUNT}]}
		PKEY=${KEY}
		while [ ${#PKEY} -lt ${NUMBER_WIDTH} ]
		do
			PKEY=" ${PKEY}"
		done
		tp $LROW $ICOL  ; echo "${PKEY}) ${I} "
		tp $LROW $ISCOL ; echo ": ${SIZES[${KEY}]}"
		LROW=$((${LROW}+1))
		COUNT=$((${COUNT}+1))
	done

	if [ ${MAXROW} -lt ${LROW} ]
	then
		MAXROW=${LROW}
	fi

	CROW=${MAXROW}
}

# Displays the menu options
function display_menu
{
	local ROW
	local COL
	local SROW

	if [ "${1}" ]
	then
		ROW=$1
	else
		ROW=$CROW
	fi

	COL=10

	tp $ROW $COL ; echo
	ROW=$(($ROW+1))
	tp $ROW $COL ; echo "1) Move Table to Schema Dump"
	ROW=$(($ROW+1))
	tp $ROW $COL ; echo "2) Move Table to Data Dump"
	ROW=$(($ROW+1))
	tp $ROW $COL ; echo "3) Move Table to Ignore List"
	ROW=$(($ROW+1))
	tp $ROW $COL ; echo "4) Commence Dumping"
	ROW=$(($ROW+1))

	CROW=${ROW}
}

# Removes a specified table from all table arrays
# @param string $TNAME Name of table to remove
# @returns integer $RKEY
# @returns string  $RNAME
function remove
{
	local isFOUND
	RNAME=""
	RKEY=-1

	if [ "${TNAME#[0-9]}" = "" -o "${TNAME#[0-9][0-9]}" = "" ]
	then
		# If entered table number
		RKEY=${TNAME}
		RNAME=${TABLES[${TNAME}]}
	else
		# entered table name
		# Find the key
		for (( X=0 ; X < ${#TABLES[@]} ; X++ ))
		do
			if [ "x${TABLES[${X}]##${TNAME}}" = "x" ]
			then
				RKEY=${X}
				RNAME=${TABLES[${X}]}
				break
			fi
		done
	fi

	unset -v SCHEMA[${RKEY}]
	unset -v DATA[${RKEY}]
	unset -v IGNORE[${RKEY}]
}

# Adds a table to the specified array
# @param string $1 Which table to add the item to
function add_to_array
{
	local WHICH=$1
	local KEY=$2
	local VALUE=$3

	case $WHICH in
		"SCHEMA")
			SCHEMA[$KEY]=$VALUE
		;;

		"DATA")
			DATA[$KEY]=$VALUE
		;;

		"IGNORE")
			IGNORE[$KEY]=$VALUE
		;;

		*)
		;;
	esac
}

#####################
## MAIN PROCESSING ##
#####################
DHOST='db1.clkonline.com'
DPORT='13306'
DNAME='ldb'
DUSER='test_ecash'

ROWS=`stty -a | head -n1 | cut -d" " -f5 | cut -d";" -f1`
CROW=0

declare -a SCHEMA
declare -a DATA
declare -a IGNORE
declare -a RESULT
declare -a RECOMMENDED=('access_group' 'access_group_control_option' 'ach_company' 'ach_return_code' 'acl' 'agent' 'agent_access_group' 'agent_affiliation'  'application_status' 'bureau' 'bureau_inquiry_type' 'bureau_login' 'company' 'company_contact' 'company_property' 'document_list' 'event_type' 'flag_type' 'holiday' 'loan_actions' 'loan_type' 'rule_component' 'rule_component_parm' 'rule_set' 'rule_set_component' 'rule_set_component_parm_value' 'section' 'site' 'state' 'system' 'time_zone' 'transaction_type')

###################
## GET USER INFO ##
###################
tp
CROW=5
# System
tp ${CROW} 10 ; read -p"System [${DHOST}:${DPORT}]: " SYSTEM
CROW=$((${CROW}+1))
if [ ! "${SYSTEM}" ]
then
	SYSTEM="${DHOST}:${DPORT}"
fi

HOST=`cut -d: -f1 <<< ${SYSTEM}`
PORT=`cut -d: -f2 <<< ${SYSTEM}`

# Port
if [ ! "${PORT}" -o "${PORT}" = "${HOST}" ]
then
	tp ${CROW} 10 ; read -p"Port [${DPORT}]: " PORT
	CROW=$((${CROW}+1))
fi
if [ ! "${PORT}" ]
then
	PORT=${DPORT}
fi

# Database
tp ${CROW} 10 ; read -p"Database [${DNAME}]: " DATABASE
CROW=$((${CROW}+1))
if [ ! "${DATABASE}" ]
then
	DATABASE=${DNAME}
fi

# User
tp ${CROW} 10 ; read -p"User [${DUSER}]: " USER
CROW=$((${CROW}+1))
if [ ! "${USER}" ]
then
	USER=${DUSER}
fi

# Password
tp ${CROW} 10 ; read -p"Password: " -s PASSWORD
CROW=$((${CROW}+1))

# Filename
DFILENAME=`cut -d. -f1 <<< ${DHOST}`
DFILENAME="${DFILENAME}_${DATABASE}"
tp ${CROW} 10 ; read -p"Filename [${DFILENAME}].sql: " FILENAME
CROW=$((${CROW}+1))

if [ ! "${FILENAME}" ]
then
	FILENAME=${DFILENAME}
fi

if [ "${FILENAME:0-4}" = ".sql" ]
then
	FILENAME=${FILENAME:0:${#FILENAME}-4}
fi

# ensure we have a unique filename
FULLFILE="${FILENAME}.sql"
COUNT=1
while [ -e ${FULLFILE} ]
do
	FULLFILE="${FILENAME}-${COUNT}.sql"
	COUNT=$((${COUNT}+1))
done

############################
## GET INITIAL TABLE INFO ##
############################
if [ "${PASSWORD}" ]
then
	RESULT=(`echo "SELECT table_name, data_length FROM information_schema.tables WHERE table_schema='${DATABASE}' AND data_length IS NOT NULL ORDER BY table_name ASC" | mysql -B -N -u${USER} -p${PASSWORD} -h${HOST} -P${PORT} 2>&1`)
else
	RESULT=(`echo "SELECT table_name, data_length FROM information_schema.tables WHERE table_schema='${DATABASE}' AND data_length IS NOT NULL ORDER BY table_name ASC" | mysql -B -N -u${USER}               -h${HOST} -P${PORT} 2>&1`)
fi

if [ ${#RESULT[@]} -eq 0 ]
then
	echo
	echo "No tables found"
	exit
fi

if [ `cut -d" " -f1 <<< ${RESULT[@]}` = "ERROR" ]
then
	echo
	echo "${RESULT[@]}"
	exit
fi

COUNT=0
# Split up the list of tables into arrays of table names and sizes
for (( X=0 ; X < ${#RESULT[@]} ; X++ ))
do
	TEMP=${RESULT[$X]}
	TABLES[${COUNT}]=$TEMP
	X=$(($X+1))
	TEMP=${RESULT[$X]}
	SIZES[${COUNT}]=$TEMP
	COUNT=$(($COUNT+1))
done

# Initial Schema/data setup
for (( X=0 ; X < ${#TABLES[@]} ; X++ ))
do
	isFOUND=0
	for RNAME in ${RECOMMENDED[@]}
	do
		if [ "x${TABLES[${X}]##${RNAME}}" = "x" ]
		then
			add_to_array "DATA" $X $RNAME
			isFOUND=1
			break
		fi
	done

	if [ ${isFOUND} -eq 0 ]
	then
		add_to_array "SCHEMA" $X ${TABLES[${X}]}
	fi
done

#######################
## DECIDE WHAT TO DO ##
#######################
while [ "${ACTION} " != "4 " ]
do
	NOCLEAR=0

	if [ ${NOCLEAR} -eq 0 ]
	then
		# Show options
		tp
		display_tables
		display_menu
		CROW=$((${CROW}+1))
	fi

	# Ask what the user wants
	tp ${CROW} 18 ; echo " "
	tp ${CROW} 10 ; read -p"Action: " -n1 ACTION

	case "${ACTION}x" in
		"1x")	# Add to Schema
			tp ${CROW} 10 ; echo "Move table to 'Schema Only'"
			CROW=$((${CROW}+1))
			tp ${CROW} 10 ; echo "(Table # or name with pathname expansion)"
			CROW=$((${CROW}+1))
			tp ${CROW} 10 ; read -p"Which Table: " TNAME
			remove
			if [ $RKEY -gt -1 ]
			then
				add_to_array "SCHEMA" ${RKEY} ${RNAME}
			else
				Dump_Error "Table not found"
			fi
			tp $((${CROW}+1)) 10 ; echo "                                                                            "
			;;
		"2x")	# Add to Data
			tp ${CROW} 10 ; echo "Move table to 'Data'"
			CROW=$((${CROW}+1))
			tp ${CROW} 10 ; echo "(Table # or name with pathname expansion)"
			CROW=$((${CROW}+1))
			tp ${CROW} 10 ; read -p"Which Table: " TNAME
			remove
			if [ $RKEY -gt -1 ]
			then
				add_to_array "DATA" ${RKEY} ${RNAME}
			else
				Dump_Error "Table not found"
			fi
			tp $((${CROW}+1)) 10 ; echo "                                                                            "
			;;
		"3x")	# Remove (Ignore)
			tp ${CROW} 10 ; echo "Move table to Ignore list"
			CROW=$((${CROW}+1))
			tp ${CROW} 10 ; echo "(Table # or name with pathname expansion)"
			CROW=$((${CROW}+1))
			tp ${CROW} 10 ; read -p"Which Table: " TNAME
			remove
			if [ $RKEY -gt -1 ]
			then
				add_to_array "IGNORE" ${RKEY} ${RNAME}
			else
				Dump_Error "Table not found"
			fi
			tp $((${CROW}+1)) 10 ; echo "                                                                            "
			;;
		"4x")	# Dump
			tp $((${CROW}-1)) 18 ; echo " "
			tp ${CROW} 10 ; echo "Commencing"
			break 2
			;;
		*)
			Dump_Error "Invalid option"
			NOCLEAR=1
			;;
	esac
done

###################
## DUMP THE DATA ##
###################
if [ ${#PASSWORD} -ge 1 ]
then
	ADDPASS="-p${PASSWORD}"
else
	ADDPASS=""
fi

echo "Getting Schema"
echo "=============="
for SNAME in ${SCHEMA[@]}
do
	printf "%s... " $SNAME
	mysqldump -e -u${USER} ${ADDPASS} -P${PORT} -h${HOST} -d ${DATABASE} ${SNAME} >> ${FULLFILE}
	printf "ok!\n"
done
echo
echo "Getting Data"
echo "============"
for DNAME in ${DATA[@]}
do
	printf "%s... " $DNAME
	mysqldump -e -u${USER} ${ADDPASS} -P${PORT} -h${HOST}    ${DATABASE} ${DNAME} >> ${FULLFILE}
	printf "ok!\n"
done
# All done
