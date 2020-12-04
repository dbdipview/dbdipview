-- Demonstration
-- Do not forget to add the VIEWs to the list.txt

CREATE VIEW "HR members"."my_test_viewA"
AS
SELECT "HR employees"."Id" AS "ID", "HR employees"."date Hired /hq" AS "Hired" FROM "HR members"."HR employees"