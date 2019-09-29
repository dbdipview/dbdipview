CREATE TABLE "AirplanesLinks"."models"
(
    id integer NOT NULL,
    name character varying(50),
    picture character varying(100)
);

CREATE INDEX models_id ON "AirplanesLinks"."models"(id);


