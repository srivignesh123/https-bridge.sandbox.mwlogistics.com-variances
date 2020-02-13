<?php
require "config/db.php";
require "inc/functions.php";
require "inc/must-haves.php";

if ($LoggedIn == "TRUE") {

    if (!in_array("Super Administrator", $UserLevels) && !in_array("Finance Administrator", $UserLevels)) {
        header("Location: ./");
        die;
    }
    
    if (!isset($_GET['dataType'])) {
        print "Invalid Request";
        die;
    }
        
    if (isset($_FILES['data-import'])) {
        
        $dataType = $_GET['dataType'];
        
        if ($dataType == "gp" || $dataType == "tms") {
            // Good
        } else {
            print "Invalid Request";
            die;
        }
        
        $uploaddir = '/var/www/bridge.sandbox.mwlogistics.com/private/uploads/';
        $moveToDir = '/var/www/bridge.sandbox.mwlogistics.com/private/uploads/csv/'; 
        $file = $uploaddir.basename($_FILES['data-import']['name']);
        $originalFile = basename($_FILES['data-import']['name']);
        
        // Generate GUID for this file
        $guid = generateGUID();
        $newFileNoPath = $guid.'.csv';
        $newFile = $moveToDir.$guid.'.csv';

        // If the file we are moving already exists, let's remove it. 
        if (file_exists($file)) unlink($file); 
        
        // If we can successfuly move the temp file to a more permanent place to work with it...
        if (move_uploaded_file($_FILES['data-import']['tmp_name'], $newFile)) {   
            
            // Now that we have the file in a permanent place, let's do some stuff...
            //print "Successfully uploaded file to ".$newFile;
            
            // Let's add this import to the database
            $stmt = $dbCon->prepare('INSERT INTO `import` (`date`, `status`, `fileName`, `originalName`, `dataType`) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(array(date('Y-m-d H:i:s'), 'pending', $newFileNoPath, $originalFile, $dataType));
            
            $_SESSION[COOKIE_PREFIX]['ImportingData'] = TRUE;
            $_SESSION[COOKIE_PREFIX]['ImportID'] = $dbCon->lastInsertId();
            print "ok";
            die;
            
        } else {
            print "An error occured while uploading the file. Please try again.";
            die;
        }
        
        
    } else {
        print "Invalid Request";
        die;
    }
    
} else {
    
    header('Location: login');
    die;
        
}
?>