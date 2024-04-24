#!/bin/bash
# dbDIPview regression test
#
# For all TestAndDemo test cases these steps will be performed based on the related order XML file:
#    - uninstall the databases if they already exist
#    - build the DDV (.zip) or EXT DDV (.tar.gz) package
#    - copy the order and siard files to the DIP0 folder
#    - use the orders and install and activate the databases
#
# Run with this command (remove and install):
#    ./run_all.sh
# Only remove all installed databases:
#    ./run_all.sh -r
#
# Boris Domajnko
#

prog=$(readlink -f $0)
dirR="$(dirname $prog)"
dirT="$(dirname $dirR)"
MH="$(dirname $dirT)"  #dbdipview

DIP0=$MH/records/DIP0
UNPACKED=$MH/records/DIP0unpacked
INFO="Package created by run_all.sh"
DBG=
RMONLY=false
TESTCASE="all"
ALLTESTCASES="TestAndDemo2 TestAndDemo3 TestAndDemo4 TestAndDemo5 TestAndDemo6"
ALLTESTCASESRM="TestAndDemo2 TestAndDemo3 TestAndDemo6 TestAndDemo5 TestAndDemo4"

usage() {
	echo "Usage: $0 [-r] [-d] [-t TestAndDemoN]" 1>&2
	echo "  -r              remove (uninstall) only" 1>&2
	echo "  -v              verbose mode" 1>&2
	echo "  -t TestAndDemoN only test case N" 1>&2
	exit 1
}

while getopts "rvht:" o; do
	case "${o}" in
		r)	RMONLY=true;;
		v)	DBG="-d";;
		t)	ALLTESTCASES=${OPTARG}
			ALLTESTCASESRM=${OPTARG};;
		*)	usage;;
	esac
done

if [ ! -d $DIP0 ]
then
	echo "Folder $DIP0 not found! Please
 	- check configa.txt for DDV_DIR_PACKED folder or
	- run the menu.php for the first time to complete the installation or
	- check the MH variable in this file."
	exit 1
fi

echo "== Removing previously installed databases ==========="
for TESTCASE in $ALLTESTCASESRM
do
	echo "== deleting ${TESTCASE} ========================================="
	#skip after first installation
	if [ -d $UNPACKED/${TESTCASE} ] ; then
		echo "   Running command: ${MH}/admin/menu.php $DBG -r order_${TESTCASE}.xml"
		php ${MH}/admin/menu.php $DBG -r order_${TESTCASE}.xml
	else
		echo "   Probably not installed, folder does not exist: $UNPACKED/${TESTCASE}"
	fi
done

if [ "$RMONLY" = true ] ; then
	exit 0
fi

echo "== Building and copying the packages ==========="
sleep 2
for TESTCASE in $ALLTESTCASES
do
	case $TESTCASE in
		TestAndDemo2)
			echo "== ${TESTCASE} ========================================="
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
			;;

		TestAndDemo3)
			echo "== ${TESTCASE} ========================================="
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
			cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0/
			;;

		TestAndDemo4)
			echo "== ${TESTCASE} ========================================="
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
			cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0/
			;;

		TestAndDemo5)
			echo "== ${TESTCASE} ========================================="
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
			;;

		TestAndDemo6)
			echo "== ${TESTCASE} ========================================="
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
			;;
	esac
done

echo "== Deployment ==========="
sleep 2

for TESTCASE in $ALLTESTCASES
do
	echo "== Deploying ${TESTCASE} ========================================="
	php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml
done

echo "======================================================"
echo "Done."
xip=`hostname -I`
ip=`echo $xip | sed 's/\s.*$//g'`
echo "Now you can check http://$ip/dbdipview/login.htm"
echo "To remove TestAndDemo databases run: ./run_all.sh -r"

