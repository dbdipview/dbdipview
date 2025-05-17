#!/bin/bash
# dbDIPview regression test
#
# For all TestAndDemo test cases these steps will be performed based on the related order XML file:
#    - uninstall the databases if they already exist
#    - build the DDV (.zip) or EXT DDV (.tar.gz) package
#    - copy the order and siard files to the DIP0 folder
#    - use the orders and install and activate the databases
#
# Run with this command (remove and install) or check options with -h:
#    ./run_all.sh

#
# Boris Domajnko
#

prog=$(readlink -f $0)
dirR="$(dirname $prog)"
dirT="$(dirname $dirR)"
MH="$(dirname $dirT)"  #dbdipview

DIP0=$MH/records/DIP0
UNPACKED=$MH/records/DIP0unpacked
INFO="Package created by run_all.sh;"
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
	echo "Running command: php $CMD"
	php $CMD
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
			DEPLOYER_INPUT=${DIP0}/TAD2
			mkdir -p $DEPLOYER_INPUT

			# some LOB files will be extracted from a package, see order file
			AIP=external_package_with_LOB_packages.zip
			# copy or link a file
			cp  ${MH}/testing/${TESTCASE}/package/$AIP   $DEPLOYER_INPUT/

			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DEPLOYER_INPUT -n ${TESTCASE}_eddv -y -i "$INFO" -a
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DEPLOYER_INPUT/
			;;

		TestAndDemo3)
			echo "== ${TESTCASE} ========================================="
			#test scenario with a special folder, it is used also in the order file
			DEPLOYER_INPUT=${DIP0}/TAD3
			mkdir -p $DEPLOYER_INPUT
			# SIARD files will be extracted from a .zip/.tar/.tar.gz package, see order file
			AIP=TestAndDemo3_AIP_content_as_DIP0.tar.gz
			# copy or link a file
			# cp  ${MH}/testing/${TESTCASE}/package/$AIP   $DEPLOYER_INPUT/
			ln -sf ${MH}/testing/${TESTCASE}/package/$AIP  $DEPLOYER_INPUT/$AIP
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DEPLOYER_INPUT -n ${TESTCASE}_ddv -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DEPLOYER_INPUT/
			cp  ${MH}/testing/${TESTCASE}/package/*.siard $DEPLOYER_INPUT/
			;;

		TestAndDemo4)
			echo "== ${TESTCASE} ========================================="
			DEPLOYER_INPUT=${DIP0}/TAD4
			mkdir -p $DEPLOYER_INPUT
			AIP=TestAndDemo4_AIP_content_as_DIP0.zip
			cp       ${MH}/testing/${TESTCASE}/package/$AIP   $DEPLOYER_INPUT/
			
			#create DDV package without CSV files
				CMD="${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DEPLOYER_INPUT -n ${TESTCASE}_ddv -a -y -i '$INFO scenario without CSV files' "
				echo "Running command: php $CMD"
				php $CMD

			#create EDDV package for a separate TestAndDemo5 scenario
				# step 1: temporarily unpack CSV files to enable verification during package creation
				mkdir -p ${MH}/testing/${TESTCASE}/data
				unzip -j -q -o $DEPLOYER_INPUT/$AIP -d ${MH}/testing/${TESTCASE}/data '*.csv'

				# step 2: check filenames in list.xml 
				CMD="${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}"
				echo "Running command: php $CMD"
				php $CMD

				# step 3:create EDDV
				CMD="${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DEPLOYER_INPUT -n ${TESTCASE}_eddv -a -y -i '$INFO scenario with CSV files' "
				echo "Running command: php $CMD"
				php $CMD

				# step 4: remove CSV files, they will be in a separate package AIP
				rm -rf -v ${MH}/testing/${TESTCASE}/data

			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DEPLOYER_INPUT/
			cp  ${MH}/testing/${TESTCASE}/package/*.siard     $DEPLOYER_INPUT/
			;;

		TestAndDemo5)
			echo "== ${TESTCASE} ========================================="
			DEPLOYER_INPUT=${DIP0}/TAD5
			mkdir -p $DEPLOYER_INPUT
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DEPLOYER_INPUT -n ${TESTCASE}_ddv -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DEPLOYER_INPUT/
			;;

		TestAndDemo6)
			echo "== ${TESTCASE} ========================================="
			DEPLOYER_INPUT=${DIP0}/TAD6
			mkdir -p $DEPLOYER_INPUT
			php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DEPLOYER_INPUT -n ${TESTCASE}_ddv -y -i "$INFO"
			cp  ${MH}/testing/${TESTCASE}/package/*order.xml  $DEPLOYER_INPUT/
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
			CMD="${MH}/admin/ordeploy.php $DBG -p TAD2/${TESTCASE}_order.xml"
			;;
		TestAndDemo3)
			CMD="${MH}/admin/ordeploy.php $DBG -p TAD3/${TESTCASE}_order.xml"
			;;
		TestAndDemo4)
			CMD="${MH}/admin/ordeploy.php $DBG -p TAD4/${TESTCASE}_order.xml"
			;;
		TestAndDemo5)
			CMD="${MH}/admin/ordeploy.php $DBG -p TAD5/${TESTCASE}_order.xml"
			;;
		TestAndDemo6)
			CMD="${MH}/admin/ordeploy.php $DBG -p TAD6/${TESTCASE}_order.xml"
			;;
		*)
			echo "Unknown testcase $TESTCASE"
			exit 1
			;;
	esac
	echo "Running command: php $CMD"
	php $CMD
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

