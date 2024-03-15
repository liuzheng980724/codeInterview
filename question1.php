<?php

$help = array("The PHP script include these command line options (directives):", "--file [csv file name] – this is the name of the CSV to be parsed", "--create_table – this will cause the MySQL users table to be built (and no further action will be taken)", "--dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered", "-u – MySQL username", "-p – MySQL password", "-h – MySQL host", "--help – which will output the above list of directives with details.");

$username = null;
$password = null;
$mysqlHost = null;
$mysqlTableName = null;
$fileLocation = null;

$writeToDatabase = false;

setParameters();
echo("Username: $username");
echo("Password: $password");
echo("mysqlHost: $mysqlHost");
echo("mysqlTable: $mysqlTableName");
echo("File: $fileLocation");

function setParameters(): void
{
    global $argv, $username, $password, $mysqlHost, $fileLocation, $mysqlTableName, $writeToDatabase;
    $filteredArgument = array_filter($argv, fn($i) => $i > 0, ARRAY_FILTER_USE_KEY);
    foreach ($filteredArgument as $index => $arg) {

        if (str_starts_with($arg, "-")) {
            $arg = strtolower($arg);
            switch ($arg) {
                case "-u":
                    $username = $filteredArgument[$index + 1];
                    break;

                case "-p":
                    $password = $filteredArgument[$index + 1];
                    break;

                case "-h":
                    $mysqlHost = $filteredArgument[$index + 1];
                    break;

                case "--file":
                    $fileLocation = $filteredArgument[$index + 1];
                    break;

                case "--create_table":
                    $mysqlTableName = $filteredArgument[$index + 1];
                    $writeToDatabase = true;
                    break;

                case "--dry_run":
                    $mysqlTableName = $filteredArgument[$index + 1];
                    $writeToDatabase = false;
                    break;

                /*case "--help":
                    printHelp();
                    break;*/    //Use Default
                default:
                    printHelp();
                    break;
            }
        }
    }
}

function printHelp(): void
{
    global $help;
    foreach ($help as $thisHelpLine) {
        echo("$thisHelpLine\n");
    }
}