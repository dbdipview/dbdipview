# dbDIPview regression test
# For all TestAndDemo test cases these steps will be performed based on the related order XML file:
#    - uninstall the database from the order
#    - build the DDV (.zip) or EXT DDV (.tar.gz) package
#    - copy the order and siard files to the DIP0 folder
#    - install and activate the database from the order
# Run with this command:
#    .  ~/dbdipview/testing/run/run_all.sh
# Boris Domajnko
#
DIP0=~/dbdipview/records/DIP0
MH=~/dbdipview
#DBG="-d"
DBG=

if [ ! -d $DIP0 ]
then
	echo "Folder $DIP0 not found! Please check configa.txt for DDV_DIR_PACKED folder or run the menu.php for the first time to complete the installation."
else

	TESTCASE=TestAndDemo2
	echo "==${TESTCASE}========================================="
	php ${MH}/admin/menu.php $DBG -r order_${TESTCASE}.xml
	php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
	cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
	php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

	TESTCASE=TestAndDemo3
	echo "==${TESTCASE}========================================="
	php ${MH}/admin/menu.php $DBG -r order_${TESTCASE}.xml
	php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
	cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
	cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0/
	php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

	TESTCASE=TestAndDemo4
	echo "==${TESTCASE}========================================="
	php ${MH}/admin/menu.php $DBG -r order_${TESTCASE}.xml
	php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
	cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
	cp  ${MH}/testing/${TESTCASE}/package/*.siard $DIP0/
	php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

	TESTCASE=TestAndDemo5
	echo "==${TESTCASE}========================================="
	php ${MH}/admin/menu.php $DBG -r order_${TESTCASE}.xml
	php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
	cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
	php ${MH}/admin/menu.php $DBG -p order_${TESTCASE}.xml

fi
