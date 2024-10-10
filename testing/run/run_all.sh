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
ALLTESTCASES_ALL=$ALLTESTCASES
ALLTESTCASESRM="TestAndDemo2 TestAndDemo3 TestAndDemo6 TestAndDemo5 TestAndDemo4"
OPTIONS_USED=false

usage() {
	echo "Usage: $0 [-r] [-v] [-t TestAndDemoN]" 1>&2
	echo "  -r              remove (uninstall) only" 1>&2
	echo "  -v              verbose mode" 1>&2
	echo "  -t TestAndDemoN only test case N" 1>&2
	exit 1
}

while getopts "rvht:" o; do
	case "${o}" in
		r)	RMONLY=true
			OPTIONS_USED=true
			;;
		v)	DBG="-v"
			OPTIONS_USED=true
			;;
		t)	ALLTESTCASES=${OPTARG}
			ALLTESTCASESRM=${OPTARG}
			OPTIONS_USED=true
			;;
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
	echo "== Deleting ${TESTCASE} ========================================="
	#skip after first installation
	if [ -d $UNPACKED/${TESTCASE} ] ; then
		case $TESTCASE in
			TestAndDemo2)
				CMD="${MH}/admin/ordeploy.php $DBG -r TAD2/${TESTCASE}_order.xml"
				;;
			TestAndDemo3)
				CMD="${MH}/admin/ordeploy.php $DBG -r TAD3/${TESTCASE}_order.xml"
				;;
			TestAndDemo4)
				CMD="${MH}/admin/ordeploy.php $DBG -r TAD4/${TESTCASE}_order.xml"
				;;
			TestAndDemo5)
				CMD="${MH}/admin/ordeploy.php $DBG -r TAD5/${TESTCASE}_order.xml"
				;;
			TestAndDemo6)
				CMD="${MH}/admin/ordeploy.php $DBG -r TAD6/${TESTCASE}_order.xml"
				;;
			*)
				echo "Unknown testcase $TESTCASE."
				exit 1
				;;
		esac
		echo "   Running command: $CMD"
		php $CMD
				
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
			DIP0s=${DIP0}/TAD2
			mkdir -p $DIP0s
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0s -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DIP0s/
			;;

		TestAndDemo3)
			echo "== ${TESTCASE} ========================================="
			#test scenario with a special folder, it is used also in the order file
			DIP0s=${DIP0}/TAD3
			mkdir -p $DIP0s
			# SIARD files will be extracted from a .zip/.tar/.tar.gz package, see order file
			AIP=TestAndDemo3_AIP_content_as_DIP0.tar.gz
			# copy or link a file
			# cp  ${MH}/testing/${TESTCASE}/package/$AIP   $DIP0s/
			ln -sf ${MH}/testing/${TESTCASE}/package/$AIP   $DIP0s/$AIP
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0s -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DIP0s/
			cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0s/
			;;

		TestAndDemo4)
			echo "== ${TESTCASE} ========================================="
			#test scenario with a special folder, it is used also in the order file
			DIP0s=${DIP0}/TAD4
			mkdir -p $DIP0s
			AIP=TestAndDemo4_AIP_content_as_DIP0.zip
			cp       ${MH}/testing/${TESTCASE}/package/$AIP   $DIP0s/
			#temporarily unpack CSV files to enable verification during package creation
			mkdir -p ${MH}/testing/${TESTCASE}/data
			unzip -j -q -o $DIP0s/$AIP -d ${MH}/testing/${TESTCASE}/data '*.csv'

			#create EDDV package for a separate TestAndDemo5 scenario
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0s -n ${TESTCASE} -y -i "$INFO with CSV files"
			#create DDV package without CSV files
			# check list.xml
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}
			# remove CSV files, they will be in a separate package AIP
			rm -rf -v ${MH}/testing/${TESTCASE}/data
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0s -n ${TESTCASE} -y -i "$INFO without CSV files" -a

			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DIP0s/
			cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0s/
			;;

		TestAndDemo5)
			echo "== ${TESTCASE} ========================================="
			DIP0s=${DIP0}/TAD5
			mkdir -p $DIP0s
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0s -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DIP0s/
			;;

		TestAndDemo6)
			echo "== ${TESTCASE} ========================================="
			DIP0s=${DIP0}/TAD6
			mkdir -p $DIP0s
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0s -n ${TESTCASE} -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DIP0s/
			;;
		*)
			echo "Unknown testcase $TESTCASE. Alowed are: $ALLTESTCASES_ALL"
			exit 1
			;;
	esac
done

echo "== Deployment ==========="
sleep 2

for TESTCASE in $ALLTESTCASES
do
	echo "== Deploying ${TESTCASE} ========================================="
	case $TESTCASE in
		TestAndDemo2)
			php ${MH}/admin/ordeploy.php $DBG -p TAD2/${TESTCASE}_order.xml
			;;
		TestAndDemo3)
			php ${MH}/admin/ordeploy.php $DBG -p TAD3/${TESTCASE}_order.xml
			;;
		TestAndDemo4)
			php ${MH}/admin/ordeploy.php $DBG -p TAD4/${TESTCASE}_order.xml
			;;
		TestAndDemo5)
			php ${MH}/admin/ordeploy.php $DBG -p TAD5/${TESTCASE}_order.xml
			;;
		TestAndDemo6)
			php ${MH}/admin/ordeploy.php $DBG -p TAD6/${TESTCASE}_order.xml
			;;
		*)
			echo "Unknown testcase $TESTCASE"
			exit 1
			;;
	esac
done

echo "======================================================"
echo "Done."
xip=`hostname -I`
ip=`echo $xip | sed 's/\s.*$//g'`
echo "Now you can check http://$ip/dbdipview/login.htm"
echo "To remove TestAndDemo databases run: ./run_all.sh -r"
if ! $OPTIONS_USED ; then
	echo "Check also: ./run_all.sh -h"
fi

