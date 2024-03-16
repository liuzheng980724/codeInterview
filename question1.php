<?php

$help = array("The PHP script include these command line options (directives):", "--file [csv file name] – this is the name of the CSV to be parsed", "--create_table – this will cause the MySQL users table to be built (and no further action will be taken)", "--dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. All other functions will be executed, but the database won't be altered", "-u – MySQL username", "-p – MySQL password", "-h – MySQL host", "--help – which will output the above list of directives with details.");

$username = null;
$password = null;
$mysqlHost = null;
$dbname = "codeInterview";
$mysqlTableName = "users";
$dbParametersFlag = false;

$flagUserNeedInputFile = false;
$fileLocation = null;

$writeToDatabase = true;    //All process in DB include create table, search
$allowInsertData = true;   //Only process on insert data

$usersOriginal = null;
$validUsers = null;

setParameters();

if(file_exists($fileLocation) AND $flagUserNeedInputFile) {
    $usersOriginal = getCsv($fileLocation); //Read all user from CSV.
    $validUsers = formatData($usersOriginal);   //Filter all invalid user and show.

    echo("All valid users information:\n");
    foreach ($validUsers as $user) {    //Show all valid users list.
        echo("Email: $user[0] Name: $user[1] Surname: $user[2]\n");
    }
} else if($flagUserNeedInputFile) { //Do not show msm not need
    echo("Please check the file is exist. $fileLocation\n");
}

if((!is_null($username) OR !is_null($password) OR !is_null($mysqlHost)) AND ($writeToDatabase)) {
    try {
        include 'includes/mysql_connect.inc';   //For mysql connect,
        if ($db->connect_error) { //Check mysql can login
            echo("Check your DB's username, password, host. \n");
        } else {
            $dbParametersFlag = true;
        }
    }catch(Exception $e)
    {
        echo $e->getMessage();
        echo "\n";
    }

} else if($writeToDatabase) {
    echo("You have to set the parameters of DB username, password, host. \n");
}

if($writeToDatabase AND $dbParametersFlag) {
    include 'includes/mysql_connect.inc';   //For mysql connect,

    if(!isTableExist()) {   //Creat the table if not exist.
        echo("Creating the Table...\n");
        include 'includes/mysql_connect.inc';   //For mysql connect,
        $sql = "CREATE TABLE $mysqlTableName (email varchar(255) NOT NULL, name varchar(255), surname varchar(255), PRIMARY KEY (email))";
        mysqli_query($db, $sql);
    } else {
        echo("Table already in the DB\n");
    }

    if($allowInsertData AND !is_null($validUsers)) {  //Start insert users to DB
        include 'includes/mysql_connect.inc';   //For mysql connect,

        foreach ($validUsers as $user) {    //Show all valid users list.
            echo("Start insert user $user[0]\n");

            if(checkDuplicateUser($user[0])) {  //Check if already have a same email in DB, if not add the user.
                echo("Error!! The user $user[0] already in the DB.\n");
            } else {
                try {
                    $sqlInsertData = "INSERT INTO $mysqlTableName (email, name, surname) VALUES (?, ?, ?)"; //Prevent symbol '
                    $stmt = $db->prepare($sqlInsertData);
                    $stmt->bind_param("sss", $user[0], $user[1], $user[2]);
                    $stmt->execute();
                }catch(Exception $e) {
                    echo $e->getMessage();
                    echo "\n";
                }
            }

        }
        echo("Done\n");
    }

} else if(!$writeToDatabase) {
    echo "This is Dry_run. Will not write to DB.\n";
}

function checkDuplicateUser($userEmail): bool
{
    global $mysqlTableName, $dbname, $username, $password, $mysqlHost;  //Some parameters needed for mysql connect.;
    $userIsExist = null;
    include 'includes/mysql_connect.inc';   //For mysql connect,

    $sql = "select * from $mysqlTableName where email = ?"; //Prevent symbol '
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();

    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $userIsExist = true;
    } else {
        $userIsExist = false;
    }
    return $userIsExist;
}

function formatName($input): String //Remove unusual !@#...
{
    $input = str_replace(' ', '-', ucfirst(strtolower($input)));

    return preg_replace('/[^A-Za-z0-9\-]/', '', $input);
}


function formatData($originalUsers): array
{
    $validUsers = array();
    echo("Start processing CSV file\n");
    foreach ($originalUsers as $thisuser) {
        $validFlag = true;

        $name = trim(ucfirst(strtolower($thisuser[0])));
        $surname = trim(ucfirst(strtolower($thisuser[1])));

        $email = trim(strtolower($thisuser[2]));

        if (!preg_match("/^[a-zA-Z-' ]*$/",$name)) {    //If cannot handle send to force remove symbol
            $name = formatName($name);
            //$validFlag = false;
            //echo ("Invalid name format Founded: $name\n");
        }

        if (!preg_match("/^[a-zA-Z-' ]*$/",$surname)) { //If cannot handle send to force remove symbol
            $surname = formatName($surname);
            //$validFlag = false;
            //echo ("Invalid surname format Founded: $surname\n");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validFlag = false;
            echo("Invalid email format Founded: $email\n");
        }
        $cacheUser = [$email, $name, $surname];
        if ($validFlag) {
            array_push($validUsers, $cacheUser);
        } else {
            $errorDataOut = fopen('php://stdout', 'w');
            fputs($errorDataOut, "Invalid user data: $email | $name | $surname\n");
            fclose($errorDataOut);
        }
    }

    return $validUsers;
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
    $readCsv = fopen($file, "r");
    while (($row = fgetcsv($readCsv)) !== false) {
        $users[] = $row;
    }
    fclose($readCsv);
    array_shift($users);
    return $users;
}

function setParameters(): void
{
    global $argv, $username, $password, $mysqlHost, $fileLocation, $mysqlTableName, $writeToDatabase, $flagUserNeedInputFile;
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
                    $flagUserNeedInputFile = true;
                    break;

                case "--create_table":
                    //$mysqlTableName = $filteredArgument[$index + 1];
                    $mysqlTableName = "users";  //Force use users
                    $allowInsertData = false;   //Only creat table not insert data.
                    break;

                case "--dry_run":
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