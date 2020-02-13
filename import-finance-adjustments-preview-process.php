<?php
require "config/db.php";
require "inc/functions.php";
require "inc/must-haves.php";

if ($LoggedIn == "TRUE") {

    if (!in_array("Super Administrator", $UserLevels) && !in_array("Finance Administrator", $UserLevels)) {
        header("Location: ./");
        die;
    }
    
    if (isset($_SESSION[COOKIE_PREFIX]['ImportingData']) && $_SESSION[COOKIE_PREFIX]['ImportingData'] == TRUE) {
        // Good
    } else {
        header("Location: ./");
        die;
    }
    
    if (isset($_SESSION[COOKIE_PREFIX]['ImportID'])) {
        $importId = $_SESSION[COOKIE_PREFIX]['ImportID'];
    } else {
        header("Location: ./");
        die;
    }
    
    //unset($_SESSION[COOKIE_PREFIX]['ImportingData']);
    //unset($_SESSION[COOKIE_PREFIX]['ImportID']);
    
    // Get import details
    $stmt = $dbCon->prepare('SELECT * FROM `import` WHERE `id` = ?');
    $stmt->execute(array($importId));
    $num = $stmt->rowCount();
    
    if ($num == 0) {
        header("Location: ./");
        die;
    }
    
    $import = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    
    // Do something
    // Let's start by loading up the file and checking the headings
    $fileName = APP_BASE_PATH.'/private/uploads/csv/'.$import['fileName'];
    
    $num = 0;
    $csvData = array();
    
    if (($handle = fopen($fileName, 'r')) !== FALSE) { // Check the resource is valid
        while (($data = fgetcsv($handle, 0, ",")) !== FALSE) { // Check opening the file is OK!
            if ($num < 10) {
                array_push($csvData, $data);
                $num++;    
            } else {
                break;
            }
        }
        fclose($handle);
    } else {

        print "Error opening file ".$fileName;
        die;

    }
    
    $headings = $csvData[0]; // Grab the first line
    $hasError = FALSE;
    
    if ($import['dataType'] == "tms") {
        // If this is TMS data, check for TMS headings
        if (remove_utf8_bom(trim(preg_replace('/\t+/', '', $headings[0]))) != "Primary Reference") {
            //print $headings[0];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[1])) != "Load ID") {
            //print $headings[1];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[2])) != "Customer Invoice Number") {
            //print $headings[2];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[3])) != "Actual Delivery") {
            //print $headings[3];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[4])) != "Bill To Code") {
            //print $headings[4];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[5])) != "Carrier Name") {
            //print $headings[5];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[6])) != "Customer Rate Total") {
            //print $headings[6];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[7])) != "Customer Invoice Total") {
            //print $headings[7];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[8])) != "Carrier Rate Total") {
            //print $headings[8];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[9])) != "Carrier Invoice Total") {
            //print $headings[9];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[10])) != "OID") {
            //print $headings[10];
            $hasError = TRUE;
        } else {
            //print "Nothing";
            $hasError = FALSE;
        }
    } else if ($import['dataType'] == "gp") {
        // If this is GP data, check for GP headings
        if (remove_utf8_bom(trim(preg_replace('/\t+/', '', $headings[0]))) != "Journal Entry") {
            //print $headings[0];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[1])) != "Series") {
            //print $headings[1];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[2])) != "TRX Date") {
            //print $headings[2];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[3])) != "Account Number") {
            //print $headings[3];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[4])) != "Account Description") {
            //print $headings[4];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[5])) != "Debit Amount") {
            //print $headings[5];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[6])) != "Credit Amount") {
            //print $headings[6];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[7])) != "Originating Document Number") {
            //print $headings[7];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[8])) != "Originating Master Name") {
            //print $headings[8];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[9])) != "Reference") {
            //print $headings[9];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[10])) != "Voided") {
            //print $headings[10];
            $hasError = TRUE;
        } else
        if (trim(preg_replace('/\t+/', '', $headings[11])) != "User Who Posted") {
            //print $headings[11];
            $hasError = TRUE;
        } else {
            //print "None";
            $hasError = FALSE;
        }
    }
    
    if ($hasError) {
        // If we have an error at this point it is due to not having the correct headings
        if ($import['dataType'] == "tms") {
            ?>
            <div class="alert alert-danger">Headings from the file you uploaded are incorrect for TMS Data Import.</div>
            <h2>Headings Should Be</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="">Primary Reference</th>
                            <th style="">Load ID</th>
                            <th style="">Customer Invoice Number</th>
                            <th style="">Actual Delivery</th>
                            <th style="">Bill To Code</th>
                            <th style="">Carrier Name</th>
                            <th style="">Customer Rate Total</th>
                            <th style="">Customer Invoice Total</th>
                            <th style="">Carrier Rate Total</th>
                            <th style="">Carrier Invoice Total</th>
                            <th style="">OID</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <h2>Imported Headings Are</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style=""><?php print $headings[0]; ?></th>
                            <th style=""><?php print $headings[1]; ?></th>
                            <th style=""><?php print $headings[2]; ?></th>
                            <th style=""><?php print $headings[3]; ?></th>
                            <th style=""><?php print $headings[4]; ?></th>
                            <th style=""><?php print $headings[5]; ?></th>
                            <th style=""><?php print $headings[6]; ?></th>
                            <th style=""><?php print $headings[7]; ?></th>
                            <th style=""><?php print $headings[8]; ?></th>
                            <th style=""><?php print $headings[9]; ?></th>
                            <th style=""><?php print $headings[10]; ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
            <?php
            die;
        } else if ($import['dataType'] == "gp") {
            ?>
            <div class="alert alert-danger">Headings from the file you uploaded are incorrect for GP Data Import.</div>
            <h2>Headings Should Be</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="">Journal Entry</th>
                            <th style="">Series</th>
                            <th style="">TRX Date</th>
                            <th style="">Account Number</th>
                            <th style="">Account Description</th>
                            <th style="">Debit Amount</th>
                            <th style="">Credit Amount</th>
                            <th style="">Originating Document Number</th>
                            <th style="">Originating Master Name</th>
                            <th style="">Reference</th>
                            <th style="">Voided</th>
                            <th style="">User Who Posted</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <h2>Imported Headings Are</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style=""><?php print $headings[0]; ?></th>
                            <th style=""><?php print $headings[1]; ?></th>
                            <th style=""><?php print $headings[2]; ?></th>
                            <th style=""><?php print $headings[3]; ?></th>
                            <th style=""><?php print $headings[4]; ?></th>
                            <th style=""><?php print $headings[5]; ?></th>
                            <th style=""><?php print $headings[6]; ?></th>
                            <th style=""><?php print $headings[7]; ?></th>
                            <th style=""><?php print $headings[8]; ?></th>
                            <th style=""><?php print $headings[9]; ?></th>
                            <th style=""><?php print $headings[10]; ?></th>
                            <th style=""><?php print $headings[11]; ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
            <?php
            die;
        }
    }
    
    // If we have no errors let's continue
    if ($import['dataType'] == 'tms') {
        ?>
        <div class="alert alert-info">
            <p>Below is some of the data from the CSV file you uploaded. Please review the information to ensure data is where it should be and in the correct format.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="">Primary Reference</th>
                        <th style="">Load ID</th>
                        <th style="">Customer Invoice Number</th>
                        <th style="">Actual Delivery</th>
                        <th style="">Bill To Code</th>
                        <th style="">Carrier Name</th>
                        <th style="">Customer Rate Total</th>
                        <th style="">Customer Invoice Total</th>
                        <th style="">Carrier Rate Total</th>
                        <th style="">Carrier Invoice Total</th>
                        <th style="">OID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for($i=1;$i<count($csvData);$i++) {
                        ?>
                        <tr>
                            <td><?php print $csvData[$i][0]; ?></td>
                            <td><?php print $csvData[$i][1]; ?></td>
                            <td><?php print $csvData[$i][2]; ?></td>
                            <td><?php print $csvData[$i][3]; ?></td>
                            <td><?php print $csvData[$i][4]; ?></td>
                            <td><?php print $csvData[$i][5]; ?></td>
                            <td><?php print $csvData[$i][6]; ?></td>
                            <td><?php print $csvData[$i][7]; ?></td>
                            <td><?php print $csvData[$i][8]; ?></td>
                            <td><?php print $csvData[$i][9]; ?></td>
                            <td><?php print $csvData[$i][10]; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-success" id="import-proceed">Looks Good, Import</button>
        <button type="button" class="btn btn-warning" id="dont-import">Maybe Later, Not Now</button>
        <button type="button" class="btn btn-danger" id="cancel-import">Don't Import, Ever</button>
        <?php
        die;
    } else if ($import['dataType'] == 'gp') {
        ?>
        <div class="alert alert-info">
            <p>Below is some of the data from the CSV file you uploaded. Please review the information to ensure data is where it should be and in the correct format.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="">Journal Entry</th>
                        <th style="">Series</th>
                        <th style="">TRX Date</th>
                        <th style="">Account Number</th>
                        <th style="">Account Description</th>
                        <th style="">Debit Amount</th>
                        <th style="">Credit Amount</th>
                        <th style="">Originating Document Number</th>
                        <th style="">Originating Master Name</th>
                        <th style="">Reference</th>
                        <th style="">Voided</th>
                        <th style="">User Who Posted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    for($i=1;$i<count($csvData);$i++) {
                        ?>
                        <tr>
                            <td><?php print $csvData[$i][0]; ?></td>
                            <td><?php print $csvData[$i][1]; ?></td>
                            <td><?php print $csvData[$i][2]; ?></td>
                            <td><?php print $csvData[$i][3]; ?></td>
                            <td><?php print $csvData[$i][4]; ?></td>
                            <td><?php print $csvData[$i][5]; ?></td>
                            <td><?php print $csvData[$i][6]; ?></td>
                            <td><?php print $csvData[$i][7]; ?></td>
                            <td><?php print $csvData[$i][8]; ?></td>
                            <td><?php print $csvData[$i][9]; ?></td>
                            <td><?php print $csvData[$i][10]; ?></td>
                            <td><?php print $csvData[$i][11]; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-success" id="import-proceed">Looks Good, Import</button>
        <button type="button" class="btn btn-warning" id="dont-import">Maybe Later, Not Now</button>
        <button type="button" class="btn btn-danger" id="cancel-import">Don't Import, Ever</button>
        
        <?php
        die;
    }
    ?>
    
    <?php
    
} else {
    
    $_SESSION['lastPage'] = "import-data";
    header('Location: login');
    die;
        
}
?>