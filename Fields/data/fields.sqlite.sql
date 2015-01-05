--
-- Fields Module SQLite Database for Phire CMS 2.0
--

--  --------------------------------------------------------

--
-- Set database encoding
--

PRAGMA encoding = "UTF-8";
PRAGMA foreign_keys = ON;

-- --------------------------------------------------------

--
-- Table structure for table "field_groups"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]field_groups" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar,
  "order" integer,
  "dynamic" integer,
  UNIQUE ("id")
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]field_groups', 10000);
CREATE INDEX "field_group_name" ON "[{prefix}]field_groups" ("name");
CREATE INDEX "field_group_order" ON "[{prefix}]field_groups" ("order");

-- --------------------------------------------------------

--
-- Table structure for table "fields"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]fields" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "group_id" integer,
  "type" varchar,
  "name" varchar,
  "label" varchar,
  "values" text,
  "default_values" text,
  "attributes" varchar,
  "validators" varchar,
  "encryption" integer NOT NULL,
  "order" integer NOT NULL,
  "required" integer NOT NULL,
  "models" text,
  UNIQUE ("id"),
  CONSTRAINT "fk_group_id" FOREIGN KEY ("group_id") REFERENCES "[{prefix}]field_groups" ("id") ON DELETE SET NULL ON UPDATE CASCADE
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]fields', 11000);
CREATE INDEX "field_group_id" ON "[{prefix}]fields" ("group_id");
CREATE INDEX "field_field_type" ON "[{prefix}]fields" ("type");
CREATE INDEX "field_field_name" ON "[{prefix}]fields" ("name");

-- --------------------------------------------------------

--
-- Table structure for table "field_values"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]field_values" (
  "field_id" integer NOT NULL,
  "model_id" integer NOT NULL,
  "value" text,
  "timestamp" integer,
  "history" text,
  UNIQUE ("field_id", "model_id"),
  CONSTRAINT "fk_field_id" FOREIGN KEY ("field_id") REFERENCES "[{prefix}]fields" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE INDEX "field_id" ON "[{prefix}]field_values" ("field_id");
CREATE INDEX "model_id" ON "[{prefix}]field_values" ("model_id");
