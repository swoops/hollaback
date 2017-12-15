#!/bin/bash

if [[ $EUID -ne 0  ]]; then
	echo "This script must be run as root" 
	exit 1
fi

if [[ "$#" -ne 1 ]]; then 
	echo need path to web root
	exit 1;
fi

WEB_ROOT="$1"
API_DIR=$WEB_ROOT/holla
LIBDIR="/opt/hollaback"
CONFIG=$LIBDIR/config.php
DBCMDS="install_db_cmds"

#### error checking
if [[ ! -d /opt ]]; then
	echo "You don't have a /opt?"
	exit 1;
fi

if [[ -d  ${LIBDIR} ]]; then
	echo "The ${LIBDIR} already exists!!!"
	echo "Do you have a previous install?"
	echo "exiting..."
	exit 1;
fi

if [[ -e ${WEB_ROOT}/index.php ]]; then
	echo ${WEB_ROOT}/index.php exists, exiting
	exit 1
fi

if [[ -d ${API_DIR} ]]; then
	echo ${API_DIR} exists, exiting
	exit 1
fi

if [[ ! -e ${DBCMDS} ]]; then
	echo "I can't find ${DBCMDS}, exiting"
	exit 1
fi

if ! grep -q "__PASSWORD__" ${DBCMDS}; then
	echo "Can't find password replacement in ${DBCMDS}"
	exit 1
fi

### get password for DB
PASS=$( dd if=/dev/urandom bs=1 count=32 2>/dev/null |xxd -g0 -c32|cut -d' ' -f2 )
if [[ $( echo $PASS |wc -c ) -ne 65 ]]; then
	echo "messed up password somehow?"
fi

echo "[**] installing DB"
echo "what is the root password for mysql? " 
sed "s/__PASSWORD__/\"${PASS}\"/" ${DBCMDS} |mysql -uroot -p${ROOT_PWD}
if [[ $? -ne 0 ]]; then
	echo Could not install database
	exit 1
fi

mkdir ${LIBDIR}
if [[ $? -ne 0 ]]; then 
	echo could not make ${API_DIR}
	exit 1
fi
echo "[**] installing ${CONFIG}"
install -v -o www-data -g www-data -m 400 config.php ${CONFIG}
sed -i "s/--PASSWORD--/\"${PASS}\"/" ${CONFIG}
if [[ $? -ne 0 ]]; then
	echo "Failed to set database password in the config.php file"
	exit 1
fi

echo "[**] Installing HTML files"
mkdir ${API_DIR}
if [[ $? -ne 0 ]]; then 
	echo could not make ${API_DIR}
	exit 1
fi
for i in ./html/*; do
	if [[ -f $i ]]; then
		install -v -o root -g root -m 004 $i ${WEB_ROOT} 
		if [[ $? -ne 0 ]]; then
			echo "Failed to install HTML files"
			exit 1
		fi
	fi
	if [[ -d $i ]]; then
		for j in $i/*; do
			install -v -o root -g root -m 004 $j ${API_DIR} 
			if [[ $? -ne 0 ]]; then
				echo "Failed to install HTML files"
				exit 1
			fi
		done
	fi
done

echo "[**] installing lib files"
for i in ./hollaback/*; do
	if [[ -f $i ]]; then
		install -v -o root -g root -m 004 $i ${LIBDIR} 
		if [[ $? -ne 0 ]]; then
			echo "Failed to install HTML files"
			exit 1
		fi
	fi
done
echo
echo "Looks like the install worked!"
echo "Don't forget to create an API user!!!"
