<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc.php");

class DatabaseMigration {
  public static function get_migration($filepath) {
    // Given a path to the file containing a database migration, this method
    // will return an instance of the class defined in the file.
    //
    // The name of the file in the $filepath is expected to be in the format of
    // 20091226184857_create_cool_new_table.php, and is expected to define a
    // class based on the name of the file - CreateCoolNewTable in this case.
    // Class name should the camel-cased version of the file name, without the
    // timestamp.
    //
    // Example:
    //   $file = "/home/foo/bar/baz/20091215231142_update_user_table.php";
    //   $m = DatabaseMigration::get_migration($file);
    //   // $m is now an instance of UpdateUserTable
    include($filepath);
    $name = preg_replace('/^\d+_/', "", basename($filepath, ".php"));
    $class_name = preg_replace_callback('/(?:^|_)(.)/',
	function ($matches) { return strtoupper($matches[1]); },
	$name);
    return new $class_name();
  }

  public static function find_migrations($current_version, $dirpath, $newer=true) {
    // Given the current database version ("20091226101123"), and the path to
    // the directory containing the migrations, will return a list of filepaths
    // of all migrations newer than the current version (if $newer is true), or
    // older or equal to the current version if $newer is false.
    if(! is_dir($dirpath)) {
      return array();
    }
    $all = glob($dirpath . "/*.php");
    $matching = array();
    foreach($all as $filepath) {
      $parts = explode("_", basename($filepath, ".php"), 2);
      $version = $parts[0];
      if(! preg_match('/^\d+$/', $version)) {
        continue;
      }
      if(($newer and $version > $current_version) or (!$newer and $version <= $current_version)) {
        $matching[] = $filepath;
      }
    }
    return $matching;
  }

  public static function get_latest_schema_version($dirpath) {
    // Given the path to the directory containing the migrations,
    // will return latest database SCHEMA_VERSION based list of filepaths.
    if(! is_dir($dirpath)) {
      return array();
    }
    $all = glob($dirpath . "/*.php");
    $latest = '';
    foreach($all as $filepath) {
      $parts = explode("_", basename($filepath, ".php"), 2);
      $version = $parts[0];
      if(! preg_match('/^\d+$/', $version)) {
        continue;
      }
      if($version > $latest) {
        $latest = $version;
      }
    }
    return $latest;
  }

  public static function find_current_version() {
    // Find the current schema version in the database.
    // db_connect() should have been called before this.
    $row = db_query_first("SELECT version FROM schema_version LIMIT 1");
    if($row) {
      $version = $row[0];
    } else {
      // Table probably doesn't exist, so version is unknown.
      $version = "";
    }
    return $version;
  }

  public static function run_migration($filepath) {
    // Load the migration at $filepath and execute its migrate() method,
    // then update the schema_version table if successful.
    $parts = explode("_", basename($filepath, ".php"), 2);
    $migration_version = $parts[0];
    $migration = self::get_migration($filepath);

    $migration->migrate(); // Should throw an exception failure.
    $migration->update_schema_version($migration_version);
  }

  // Instance methods start here.

  public function migrate() {
    // This function should contain code to execute the migration on the
    // database.  Use db_exec() to run database queries.
    throw new BadMethodCallException("Must be overridden by a subclass.");
  }

  public function update_schema_version($new_version) {
    // Update schema version to $new_version, throw an exception on failure.
    db_exec("UPDATE schema_version SET version = ?", array($new_version));
  }
}

?>
