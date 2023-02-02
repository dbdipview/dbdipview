-- additional commands that follow execution of createdb.sql or installation from a SIARD file
-- for instance: CREATE VIEW or COMMENT ON COLUMN
-- Do not forget to add the VIEWs to the list.xml

CREATE VIEW "AirplanesLinks"."my_view"
  AS
  SELECT id, "name Ü" FROM "AirplanesLinks"."models";

COMMENT ON COLUMN "TestCSV"."test1"."value" IS 'Description for column "value" in table test1';
COMMENT ON COLUMN "TestCSV"."TEST2"."value" IS 'Description for column "value" in table TEST2';
COMMENT ON COLUMN "TestCSV"."test3"."value" IS 'Description for column "value" in table test3';
COMMENT ON COLUMN "TestCSV"."test4"."value" IS 'Description for column "value" in table test4';
