-- Demonstration of use of this file
-- Do not forget to add the VIEWs to the list.txt

CREATE VIEW "HR members"."my_test_viewB"
AS
SELECT "HR employees"."date Hired /hq" AS "Hired" FROM "HR members"."HR employees"