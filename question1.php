<?php

$help = array("The PHP script include these command line options (directives):", "--file [csv file name] – this is the name of the CSV to be parsed", "--create_table – this will cause the MySQL users table to be built (and no further action will be taken)", "--dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered", "-u – MySQL username", "-p – MySQL password", "-h – MySQL host", "--help – which will output the above list of directives with details.");

$username = null;
$password = null;
$mysqlHost = null;
$dbname = "codeInterview";
$mysqlTableName = null;
$fileLocation = null;

$writeToDatabase = true;    //Default will allow

$usersOriginal = null;

setParameters();

/*echo("Username: $username");
echo("Password: $password");
echo("mysqlHost: $mysqlHost");
echo("mysqlTable: $mysqlTableName");
echo("File: $fileLocation");*/

$usersOriginal = getCsv($fileLocation);
print_r($usersOriginal);

if($writeToDatabase) {
    include 'includes/mysql_connect.inc';   //For mysql connect,

    if(is_null($mysqlTableName)) {
        echo("Use default table name: users");
        $mysqlTableName = "users";
    }

    if(isTableExist()) {

    } else {    //Creat the table if not exist.
        include 'includes/mysql_connect.inc';   //For mysql connect,
        $sql = "CREATE TABLE $mysqlTableName (email varchar(255) NOT NULL, name varchar(255), surname varchar(255), PRIMARY KEY (email))";
        mysqli_query($db, $sql);
    }

} else {
    echo "This is Dry_run. Will not write to DB.";
}

function isTableExist(): bool
{
    global $dbname, $mysqlTableName, $username, $password, $mysqlHost;  //Some parameters needed for mysql connect.
    $tableExistFlag = null;

    include 'includes/mysql_connect.inc';   //For mysql connect,

    $sql = "SELECT count(*) AS CHECKTABLE FROM information_schema.TABLES WHERE (TABLE_SCHEMA = '$dbname') AND (TABLE_NAME = '$mysqlTableName')";    //SQL alias CHECKTABLE
    $result = mysqli_query($db, $sql);

    while($row = $result -> fetch_assoc()){
        if($row["CHECKTABLE"] == 0) {
            $tableExistFlag = false;
        } else {
            $tableExistFlag = true;
        }
    }

    return $tableExistFlag;
}

function getCsv($file) {
    $users = null;
    if(file_exists($file)) {
        $readCsv = fopen($file, "r");
        while (($row = fgetcsv($readCsv)) !== false) {
            $users[] = $row;
        }
        fclose($readCsv);

        array_shift($users);
    } else {
        echo("Please check the file is exist. $file");
    }
    return $users;

}

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