<?php
require "config/db.php";

if ($_GET) {
    $importId = $_GET['id'];
    $_SESSION[COOKIE_PREFIX]['ImportingData'] = TRUE;
    $_SESSION[COOKIE_PREFIX]['ImportID'] = $importId;
    header("Location: import-data-preview");
    die;
} else {
    header('Location: ./');
    die;
}
?>