CREATE TABLE "AirplanesLinks"."models"
(
   "id" integer NOT NULL,
   "height"      decimal,
   "name Ü"      character varying(50),
   "picture"     character varying(100),
   "ext_link"    VARCHAR(100),
   "motors_code" INTEGER,
   "motors_text" VARCHAR(100)
);

CREATE INDEX models_id ON "AirplanesLinks"."models"(id);


CREATE TABLE "TestCSV"."test1" (
   "id"     VARCHAR(1) NOT NULL PRIMARY KEY,
   "val"    VARCHAR(1),
   "value"  VARCHAR(100),
   "dateX"  DATE,
   "dateY"  TIMESTAMP,
   "wingspan" DECIMAL(7, 2)
);

CREATE TABLE "TestCSV"."TEST2" (
   "id"     VARCHAR(1) NOT NULL PRIMARY KEY,
   "val"    VARCHAR(1),
   "value"  VARCHAR(100),
   "dateX"  DATE,
   "dateY"  TIMESTAMP,
   "wingspan" DECIMAL(7, 2)
);

CREATE TABLE "TestCSV"."test3" (
   "id"     VARCHAR(1) NOT NULL PRIMARY KEY,
   "val"    VARCHAR(1),
   "value"  VARCHAR(100),
   "dateX"  DATE,
   "dateY"  TIMESTAMP,
   "wingspan" DECIMAL(7, 2)
);

CREATE TABLE "TestCSV"."test4" (
   "id"     VARCHAR(1) NOT NULL PRIMARY KEY,
   "val"    VARCHAR(1),
   "value"  VARCHAR(100),
   "dateX"  DATE,
   "dateY"  TIMESTAMP,
   "wingspan" DECIMAL(7, 2)
);

-- we have to create a special table with codes as it is not available
CREATE VIEW "AirplanesLinks"."view_codes_NumOfEngines"
  AS
  SELECT DISTINCT "motors_code", "motors_text" FROM  "AirplanesLinks"."models" 
  WHERE "motors_code" IS NOT NULL 
  ORDER BY "motors_text"
