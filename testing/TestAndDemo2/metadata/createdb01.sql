-- additional commands that follow execution of createdb.sql or installation from a SIARD file
-- for instance: CREATE VIEW or COMMENT ON COLUMN
-- Do not forget to add the VIEWs to the list.xml

CREATE VIEW "views"."my_view"
  AS
  SELECT
	id,
	"name Ü" 
  FROM "AirplanesLinks"."models";

COMMENT ON COLUMN "TestCSV"."test1"."value" IS 'Description for column "value" in table test1';
COMMENT ON COLUMN "TestCSV"."TEST2"."value" IS 'Description for column "value" in table TEST2';
COMMENT ON COLUMN "TestCSV"."test3"."value" IS 'Description for column "value" in table test3';
COMMENT ON COLUMN "TestCSV"."test4"."value" IS 'Description for column "value" in table test4';

-- enable simple full text search
CREATE VIEW "views"."models_full_text_view" 
	AS
	SELECT
		"id",
		"name Ü",
		COALESCE("name Ü", '')     ||
		COALESCE("ext_link", '') ||
		COALESCE("motors_text", '') AS "text"
	FROM "AirplanesLinks"."models";

-- create a special table for dropdown list in the search form
CREATE VIEW "views"."view_codes_NumOfEngines"
  AS
  SELECT DISTINCT "motors_code", "motors_text" FROM  "AirplanesLinks"."models"
  WHERE "motors_code" IS NOT NULL
  ORDER BY "motors_text"
