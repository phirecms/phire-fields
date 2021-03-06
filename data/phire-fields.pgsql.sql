--
-- Fields Module PostgreSQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------

--
-- Table structure for table "field_groups"
--
CREATE SEQUENCE field_group_id_seq START 10001;

DROP TABLE IF EXISTS "[{prefix}]field_groups" CASCADE;
CREATE TABLE IF NOT EXISTS "[{prefix}]field_groups" (
  "id" integer NOT NULL DEFAULT nextval('field_group_id_seq'),
  "name" varchar(255),
  "order" integer,
  "prepend" integer NOT NULL,
  PRIMARY KEY ("id")
) ;

ALTER SEQUENCE field_group_id_seq OWNED BY "[{prefix}]field_groups"."id";
CREATE INDEX "field_group_name" ON "[{prefix}]field_groups" ("name");
CREATE INDEX "field_group_order" ON "[{prefix}]field_groups" ("order");

-- --------------------------------------------------------

--
-- Table structure for table "fields"
--

CREATE SEQUENCE field_id_seq START 11001;

CREATE TABLE IF NOT EXISTS "[{prefix}]fields" (
  "id" integer NOT NULL DEFAULT nextval('field_id_seq'),
  "group_id" integer,
  "storage" varchar(255),
  "type" varchar(255),
  "name" varchar(255),
  "label" varchar(255),
  "values" text,
  "default_values" text,
  "attributes" text,
  "validators" text,
  "encrypt" integer NOT NULL,
  "order" integer NOT NULL,
  "required" integer NOT NULL,
  "prepend" integer NOT NULL,
  "dynamic" integer NOT NULL,
  "editor" varchar(255),
  "models" text,
  PRIMARY KEY ("id"),
  CONSTRAINT "fk_group_id" FOREIGN KEY ("group_id") REFERENCES "[{prefix}]field_groups" ("id") ON DELETE SET NULL ON UPDATE CASCADE
) ;

ALTER SEQUENCE field_id_seq OWNED BY "[{prefix}]fields"."id";
CREATE INDEX "field_group_id" ON "[{prefix}]fields" ("group_id");
CREATE INDEX "field_storage" ON "[{prefix}]fields" ("storage");
CREATE INDEX "field_field_type" ON "[{prefix}]fields" ("type");
CREATE INDEX "field_field_name" ON "[{prefix}]fields" ("name");

-- --------------------------------------------------------

--
-- Table structure for table "field_values"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]field_values" (
  "field_id" integer NOT NULL,
  "model_id" integer NOT NULL,
  "model" varchar(255) NOT NULL,
  "value" text,
  "timestamp" integer,
  "history" text,
  UNIQUE ("field_id", "model_id", "model"),
  CONSTRAINT "fk_field_id" FOREIGN KEY ("field_id") REFERENCES "[{prefix}]fields" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE INDEX "field_id" ON "[{prefix}]field_values" ("field_id");
CREATE INDEX "model_id" ON "[{prefix}]field_values" ("model_id");
CREATE INDEX "model" ON "[{prefix}]field_values" ("model");
