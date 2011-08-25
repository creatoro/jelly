# Unit Tests

The unit tests for Jelly are included and they use Kohana's Unit Test module.
Please run these tests if you contribute any code to minimalize the chances of creating new bugs.

If you are adding new features to Jelly (or changing some behavior) it's highly recommended to write or modify tests for it.

The tests can be run on MySQL, SQLite and PostgreSQL databases.

## Requirements to run tests
If you don't find the configuration files mentioned below copy them from the corresponding module's folder to your application's config folder.

### Set the database profile for unit testing
Find your unit test configuration file: *APPATH/config/unittest.php*.
Set `db_connection` to the database profile (config name) you'll use for unit testing.

### Always set the database name
Find the profile for unit testing in your dabatase configuration file: *APPATH/config/database.php*.
You have to set the database name (`database`) in the `connection` array even when using the PDO driver.

### Use the correct name for database type
Find the profile for unit testing in your dabatase configuration file: *APPATH/config/database.php*.
The database type (`type`) has to be one of the following: **mysql**, **sqlite**, **postgresql**.

If you have a custom PDO driver with a different name, for example **pdo_sqlite** and you don't want to change it, you'll have to rename the database schema file for the corresponding database (this is used to set up the tables for the tests).

To do this find the *MODPATH/jelly/tests/test_data/jelly/test-schema-**YOUR DATABASE TYPE**.sql* file and rename the default database type to your needs.

Using our example we would rename the **test-schema-sqlite.sql** to **test-schema-pdo_sqlite.sql**.

### Set identifier for SQLite {#sqlite_ident}

When using SQLite through the PDO driver it is necessary to set the quoting identifier in your database config file.
Failing to do this will cause various SQL syntax errors.

	'unittest' => array
	(
		'type'       => 'pdo',
		'connection' => array(
			...
		),
		...
		'identifier' => '`',
	),

## The test workflow

To get a better understanding of how tests work read the following points about what happens before each test group is run:

1. the tables are created using the database schema files from *MODPATH/jelly/tests/test_data/jelly/*
2. connection is set up to the database
3. data is inserted into the database from *MODPATH/jelly/tests/test_data/jelly/test.xml*
4. tests are run

## Troubleshooting

### SQL Syntax Errors

If you are using PDO and SQLite, please check that you have [set the quoting identifier](#sqlite_ident).