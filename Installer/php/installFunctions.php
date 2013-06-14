<?php

/**
* TODO: Inputs needs sanitizing.
*       Optimization like a boss!
*/

try {
    require("DB.php");
    $method = $_GET["method"];
    $retval = call_user_func($method);
    echo json_encode($retval);
}
catch(Exception $e) {
    echo json_encode($e->getMessage());
}

function CreateTables()
{
    $username = $_GET["username"];
    $password = $_GET["password"];
    $database  = $_GET["database"];
    $host = $_GET["host"];
    
    $DB = new DB($username, $password, $database, "mysql", $host);
    $sql = "
            CREATE TABLE IF NOT EXISTS `configuration` (
            `updatetime` int(11) NOT NULL,
            `yellowtemp` int(11) NOT NULL,
            `yellowrejects` int(11) NOT NULL,
            `yellowdiscards` int(11) NOT NULL,
            `yellowstales` int(11) NOT NULL,
            `yellowgetfails` int(11) NOT NULL,
            `yellowremfails` int(11) NOT NULL,
            `maxtemp` int(11) NOT NULL,
            `maxrejects` int(11) NOT NULL,
            `maxdiscards` int(11) NOT NULL,
            `maxstales` int(11) NOT NULL,
            `maxgetfails` int(11) NOT NULL,
            `maxremfails` int(11) NOT NULL,
            `email` varchar(200) NOT NULL,
            `yellowfan` int(11) NOT NULL,
            `maxfan` int(11) NOT NULL,
            `yellowgessper` int(11) NOT NULL,
            `maxgessper` int(11) NOT NULL,
            `yellowavgmhper` int(11) NOT NULL,
            `maxavgmhper` int(11) NOT NULL,
            `theme_id` int(3) NOT NULL DEFAULT 1
        ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
        ";
    $DB->ExecuteSQL($sql);
    
    if(checkIfTableExists("configuration") === 0) throw new Exception("Failed to create table configuration");
    
    $sql ="CREATE TABLE IF NOT EXISTS `hosts` (
            `id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(255) NOT NULL,
            `address` varchar(255) NOT NULL,
            `port` mediumint(6) NOT NULL DEFAULT '4028',
            `mhash_desired` decimal(10,2) NOT NULL,
            `conf_file_path` varchar(255) NULL
        ) ENGINE=MyISAM  DEFAULT CHARSET=latin1";
    $DB->ExecuteSQL($sql);

    if(checkIfTableExists("hosts") == 0) throw new Exception("Failed to create table hosts");
    
    //If we have got this far, we can assume we are safe to write the values to the config file?
    writeToConfig($host, $username, $password, $database);
    
    return "Tables Created";
}

function updateTables()
{
    $DB = new DB($_GET["username"], $_GET["password"], $_GET["database"]);
    $SQL = "ALTER TABLE configuration ADD COLUMN theme_id INT(3) NOT NULL DEFAULT 1 AFTER maxavgmhper;";
    $DB->ExecuteSQL($SQL);
    return "Table configuration updated successfully";
}

function updateOrInstall()
{
    return checkIfTableExists("configuration");
}

function checkIfTableExists($table)
{
    $SQL = "select 1 from {$table}";
    $DB = new DB($_GET["username"], $_GET["password"], $_GET["database"]);
    $tableExists = (gettype($DB->ExecuteSQL($SQL)) == "integer")?true:false;
    if($tableExists == 1) {
        return 1;
    }
    else {
        return 0;
    }
}

function writeToConfig($host, $username, $password, $database)
{   
    try 
    {
        $file = "..\..\config.inc.php";
        $content = file_get_contents($file);
        
        $content = preg_writeVar("dbdatabase", $content, $database);
        $content = preg_writeVar("dbusername", $content, $username);
        $content = preg_writeVar("dbpassword", $content, $password);
        $content = preg_writeVar("dbhost", $content, $host);
        
        file_put_contents($file, $content);
        echo "Database login detials successfully written";
    }
    catch(Exception $e)
    {
        throw new Exception("Failed to write values to config.inc.php");  
    }
}

function writeAuthDetails()
{
    try
    {
        $username = $_GET["user"];
        $password = $_GET["pass"];
        
        $file = "..\..\auth.inc.php";
        $content = file_get_contents($file);

        $content = preg_writeVar("user", $content, $username);
        $content = preg_writeVar("pass", $content, $password);
        
        file_put_contents($file, $content);

        return "Authentication Details successfully written";
    }
    catch(Exception $e)
    {
        throw new Exception("Failed to write Authentication details to auth.inc.php");
    }
}

function preg_writeVar($varname, $content, $value)
{
    return preg_replace('/\$'.$varname.'\s*=\s*\"(.*?)\"/',
                        '\$'.$varname.' = "'.$value.'"',
                        $content);
}

?>