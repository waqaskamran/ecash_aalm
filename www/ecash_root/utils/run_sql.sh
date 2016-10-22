#!/bin/bash

TMP_DIR='/tmp/'
DATE=`date +%Y%m%d%H%M%S`
TMP_FILE="${TMP_DIR}run_sql_${DATE}_${RANDOM}.sql"
TMP_FILE_2="${TMP_DIR}run_sql_${DATE}_${RANDOM}.sql.2"
# If a supplied file can't be read, should execution continue?
FORCE='n'

# Possible DBs
declare -a DESCS=('David'          'George'   'Marc'     'Brian'    'Jason'		'RC'                  'Parallel'          'Dev'          'Conversion'             'DB1-LDB')
declare -a HOSTS=('ds72.tss'      'ds17.tss' 'ds52.tss' 'ds68.tss'  'ds74.tss'	        'db101.clkonline.com' 'db1.clkonline.com' 'monster.tss'  'db1.clkonline.com'      'db1.clkonline.com')
declare -a PORTS=('3306'          '3306'     '3306'     '3306'      '3306'		'3308'                '3307'             '3309'         '13306'                   '13306')
declare -a NAMES=('ldb'           'ldb'      'ldb'      'ldb'       'ldb'		'ldb'                 'ldb'               'ldb'          'ldb_int'                'ldb')
declare -a USERS=('root'          'root'     'root'     'root'      'root'		'test_ecash'          'ecash'        'ecash'        'test_ecash'             'test_ecash')
  # must specify keys to force allowance of empty strings
declare -a PASSS=([0]='' [1]='sellingsource' [2]="" [3]="" [4]="" [5]='3cash' [6]='3cash' [7]='lacosanostra' [8]='3cash'  [9]='3cash')

# Keep this seperate, will default to no when asking if should run script on it
# Safety reasons
LIVE_HOST='db3.clkonline.com'
LIVE_PORT='23306'
LIVE_USER='ecash'
LIVE_PASS='ugd2vRjv'
LIVE_NAME='ldb'

# Maybe add additional options later?
# only -f supported now
# and only if first option
# ideally, allow turning on/off of options between filenames
CONTINUE='true'
while [ ${CONTINUE} = 'true' ]
do
	case "x${1}" in
		("x--force")
			FORCE='y'
			shift
			;;
		("x-f")
			FORCE='y'
			shift
			;;
		("x--execute")
			# Command line sql instead of from files
			shift
			;;
		("x-e")
			# Command line sql instead of from files
			shift
			;;
		("x--no-force")
			FORCE='n'
			shift
			;;
		("x-F")
			FORCE='n'
			shift
			;;
		("x--with")
			# Additional mysql options (like -B, -X, etc...)
			shift
			;;
		("x-w")
			# Additional mysql options (like -B, -X, etc...)
			shift
			;;
		("x--without")
			# Remove some mysql options
			shift
			;;
		("x-W")
			# Remove some mysql options
			shift
			;;
		(*)
			# File names
			CONTINUE='false'
			;;
	esac
done

touch ${TMP_FILE}
COUNT=$#

if [ $# -eq 0 ]
then
	# prompt for files
	FILE=${TMP_FILE}
	while [ "${FILE}x" != "x" ]
	do
		if [ ${FILE} != ${TMP_FILE} ]
		then
			printf "Reading ${FILE}... "
			if [ -r ${FILE} ]
			then
				cat ${TMP_FILE} ${FILE} > ${TMP_FILE_2}
				mv ${TMP_FILE_2} ${TMP_FILE}
				printf "Ok!\n"
			else
				printf "Failed!\n"
			fi
		fi

		read -p"File: " FILE
	done
else
	# get files from command line
	for (( X = 1 ; X <= ${COUNT} ; X++ ))
	do
		printf "Reading %s... " $1
		if [ ! -r $1 ]
		then
			printf "Failed!\n"
			if [ ${FORCE} = 'n' ]
			then
				read -n1 -p"Continue anyway [y]? " CONTINUE
				echo
				if [ "${CONTINUE}x" = "nx" -o "${CONTINUE}x" = "Nx" ]
				then
					echo "Ok, canceling"
					rm -f ${TMP_FILE}
					exit
				else
					#cat ${TMP_FILE} $1 > ${TMP_FILE_2}
					#mv ${TMP_FILE_2} ${TMP_FILE}
					echo "Skipped!"
				fi
			fi
		else
			cat ${TMP_FILE} $1 > ${TMP_FILE_2}
			mv ${TMP_FILE_2} ${TMP_FILE}
			echo "Ok!"
		fi
		shift
	done
fi
echo

# Make sure there is something to do
if [ ! -e ${TMP_FILE} -o ! -s ${TMP_FILE} ]
then
	echo "Nothing to execute"
	cat ${TMP_FILE}
	exit
fi

# Find out where to do it
echo "Execute scripts on:"
COUNT=${#DESCS[@]}
for (( x=0 ; x < ${COUNT}; x++ ))
do
	read -n1 -s -p"${DESCS[${x}]} (${HOSTS[${x}]}:${PORTS[${x}]}) [y]? " DO_DB

	if [ ${#DO_DB} -gt 0 ]
	then
		printf "%s\n" ${DO_DB}
	else
		echo
	fi

	if [ "${DO_DB}x" = "nx" -o "${DO_DB}x" = "Nx" ]
	then
		unset DESCS[${x}]
		unset HOSTS[${x}]
		unset PORTS[${x}]
		unset NAMES[${x}]
		unset USERS[${x}]
		unset PASSS[${x}]
	fi
done

# Do live too?  really?
read -n1 -s -p"Live (${LIVE_HOST}:${LIVE_PORT}) [n]? " DO_LIVE
if [ ${#DO_DB} -gt 0 ]
then
	printf "%s\n" ${DO_DB}
fi

if [ "${DO_LIVE}x" = "yx" -o "${DO_LIVE}x" = "Yx" ]
then
	DESCS[${COUNT}]='Live'
	HOSTS[${COUNT}]=${LIVE_HOST}
	PORTS[${COUNT}]=${LIVE_PORT}
	NAMES[${COUNT}]=${LIVE_NAME}
	USERS[${COUNT}]=${LIVE_USER}
	PASSS[${COUNT}]=${LIVE_PASS}
	COUNT=$((${COUNT}+1))
fi

# Do it!
echo
for (( x=0 ; x < ${COUNT} ; x++ ))
do
	if [ "${DESCS[${x}]}x" != "x" ]
	then
		if [ "${HOSTS[${x}]}x" = "x" ]
		then
			HOST=""
		else
			HOST="-h${HOSTS[${x}]}"
		fi

		if [ "${PORTS[${x}]}x" = "x" ]
		then
			PORT=""
		else
			PORT="-P${PORTS[${x}]}"
		fi

		if [ "${USERS[${x}]}x" = "x" ]
		then
			USER=""
		else
			USER="-u${USERS[${x}]}"
		fi

		if [ "${PASSS[${x}]}x" = "x" ]
		then
			PASS=""
		else
			PASS="-p${PASSS[${x}]}"
		fi

		if [ "${NAMES[${x}]}x" = "x" ]
		then
			NAME=""
		else
			NAME="${NAMES[${x}]}"
		fi

		echo "Executing for ${DESCS[${x}]} on ${HOST:2}... "
		mysql -B ${HOST} ${PORT} ${USER} ${PASS} ${NAME} < ${TMP_FILE}
	fi
done

rm -f ${TMP_FILE}
