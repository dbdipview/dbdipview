# dbDIPview regression test
#
# For all TestAndDemo test cases these steps will be performed based on the related order XML file:
#    - uninstall the databases if they already exist
#    - build the DDV (.zip) or EXT DDV (.tar.gz) package
#    - copy the order and siard files to the DIP0 folder
#    - use the orders and install and activate the databases
#
# Run with this command:
#    .  ~/dbdipview/testing/run/run_all.sh
# To remove all installed databases:
#    .  ~/dbdipview/testing/run/run_all.sh -r
#
# Boris Domajnko
#

MH=~/dbdipview
DIP0=$MH/records/DIP0
UNPACKED=$MH/records/DIP0unpacked
INFO="Package created by run_all.sh"
#DBG="-d"
DBG=

if [ ! -d $DIP0 ]
then
	echo "Folder $DIP0 not found! Please
 	- check configa.txt for DDV_DIR_PACKED folder or
	- run the menu.php for the first time to complete the installation or
	- check the MH variable in this file."
else

	echo "== removing previously installed databases ==========="
	for TESTCASE in TestAndDemo2 TestAndDemo3 TestAndDemo4 TestAndDemo5
	do
		echo "== deleting ${TESTCASE} ============"
		#skip after first installation
		if [ -d $UNPACKED/${TESTCASE} ] ; then
			php ${MH}/admin/menu.php $DBG -r order_${TESTCASE}.xml
		fi
	done

	if [ "$1" != "-r" ]; then
		TESTCASE=TestAndDemo2
		echo "==${TESTCASE}========================================="
		php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
		cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
		php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

		TESTCASE=TestAndDemo3
		echo "==${TESTCASE}========================================="
		php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
		cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
		cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0/
		php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

		TESTCASE=TestAndDemo4
		echo "==${TESTCASE}========================================="
		php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
		cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
		cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0/
		php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

		TESTCASE=TestAndDemo5
		echo "==${TESTCASE}========================================="
		php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE} -t $DIP0 -n ${TESTCASE} -y -i "$INFO"
		cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
		php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

		echo "======================================================"
		echo "Done."
		echo "To remove the databases run: run_all -r"
		xip=`hostname -I`
		ip=`echo $xip | sed 's/ *$//g'`
		echo "Now you can check http://$ip/dbdipview/login.htm"
	fi

fi
