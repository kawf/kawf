#!/usr/bin/php
<?php

$kawf_base = realpath(dirname(__FILE__) . "/../..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc");
require_once($kawf_base . "/db/include/migration.inc");

if(!ini_get('safe_mode'))
    set_time_limit(0);

sql_open($database);

array_shift($argv); // Remove script name from the argument list.
$command = array_shift($argv);

if($command == "showcurrent") {
  $version = DatabaseMigration::find_current_version();
  echo "Current schema version: " . ($version ? $version : "UNKNOWN") . "\n";

} elseif($command == "showpending") {
  $version = DatabaseMigration::find_current_version();
  echo "Current schema version: " . ($version ? $version : "UNKNOWN") . "\n";
  $pending = DatabaseMigration::find_migrations($version, $kawf_base . "/db/migrations", true);
  if($pending) {
    echo "Pending migrations:\n";
    foreach($pending as $filepath) {
      echo "    " . basename($filepath, ".php") . "\n";
    }
  } else {
    echo "Schema up to date, no pending migrations.\n";
  }

} elseif($command == "runnext" or $command == "runpending") {
  $version = DatabaseMigration::find_current_version();
  echo "Current schema version: " . ($version ? $version : "UNKNOWN") . "\n";
  $pending = DatabaseMigration::find_migrations($version, $kawf_base . "/db/migrations", true);
  if($pending) {
    $to_run = $command == "runnext" ? array($pending[0]) : $pending;
    foreach($to_run as $filepath) {
      echo "Running " . basename($filepath, ".php") . "\n";
      DatabaseMigration::run_migration($filepath); // Will throw an exception on failure.
      $updated_version = DatabaseMigration::find_current_version();
      echo "Migration succeeded, updated schema version is " .
           ($updated_version ? $updated_version : "UNKNOWN") . "\n";
    }
  } else {
    echo "Schema up to date, no pending migrations.\n";
  }

} else {
  echo "Available commands:\n";
  echo "    showcurrent - show the current schema version\n";
  echo "    showpending - show migrations that are newer than current version\n";
  echo "    runnext     - run the next pending migration\n";
  echo "    runpending  - run all pending migrations\n";
}

?>
