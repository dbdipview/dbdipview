#regression test
#for all test cases:
# uninstall, build, install, activate
# run with this command:
#    .  ~/dbdipview/testing/run/run_all.sh
#
DIP0=~/dbdipview/records/DIP0
MH=~/dbdipview

TESTCASE=TestAndDemo2
echo "==${TESTCASE}========================================="
php ${MH}/admin/menu.php -r order_${TESTCASE}.xml
php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
php ${MH}/admin/menu.php -p order_${TESTCASE}.xml

TESTCASE=TestAndDemo3
echo "==${TESTCASE}========================================="
php ${MH}/admin/menu.php -r order_${TESTCASE}.xml
php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
php ${MH}/admin/menu.php -p order_${TESTCASE}.xml

TESTCASE=TestAndDemo4
echo "==${TESTCASE}========================================="
php ${MH}/admin/menu.php -r order_${TESTCASE}.xml
php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
php ${MH}/admin/menu.php -p order_${TESTCASE}.xml

TESTCASE=TestAndDemo5
echo "==${TESTCASE}========================================="
php ${MH}/admin/menu.php -r order_${TESTCASE}.xml
php ${MH}/packager/createPackage.php -s ${MH}/testing/${TESTCASE}  -t $DIP0 -n ${TESTCASE}
cp  ${MH}/testing/${TESTCASE}/package/order*  $DIP0/
php ${MH}/admin/menu.php -p order_${TESTCASE}.xml

