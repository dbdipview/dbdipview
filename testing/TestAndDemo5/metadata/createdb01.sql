-- additional commands that follow execution of createdb.sql or installation from a SIARD file
-- for instance: CREATE VIEW or COMMENT ON COLUMN
-- Do not forget to add the VIEWs to the list.xml

CREATE VIEW "HR members"."my_test_viewA"
  AS
  SELECT "HR employees"."Id" AS "ID", "HR employees"."date Hired /hq" AS "Hired"
    FROM "HR members"."HR employees";

CREATE VIEW "HR members"."my_test_viewB"
  AS
  SELECT "HR employees"."date Hired /hq" AS "Hired"
    FROM "HR members"."HR employees";
