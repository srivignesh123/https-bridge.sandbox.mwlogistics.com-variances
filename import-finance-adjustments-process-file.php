<?php
require "config/db.php";
require "inc/functions.php";
require "inc/must-haves.php";

if ($LoggedIn == "TRUE") {

    if (!in_array("Super Administrator", $UserLevels) && !in_array("Finance Administrator", $UserLevels)) {
        header("Location: ./");
        die;
    }
    
    if ($_POST) {
        
        $importId = $_POST['importId'];
        
        // Get import details
        $stmt = $dbCon->prepare('SELECT * FROM `import` WHERE `id` = ?');
        $stmt->execute(array($importId));
        $num = $stmt->rowCount();

        if ($num == 0) {
            header("Location: ./");
            die;
        }
        
        // Do stuff
        $import = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
        
        // Let's start by making a backup of the TMS and GP data tables
        $backupFileName = 'tms-gp-backup-import-id-'.$importId.'-'.date("Y-m-d-H-i-s", time()).'.sql';
        $outputDir = APP_BASE_PATH.'/private/backup/db/';
        exec('mysqldump -h '.DB_SERVER.' -u '.DB_USER.' '.DB_NAME.' gpData tmsData --password='.DB_PASS.' > '.$outputDir.'/'.$backupFileName);
        
        if (!file_exists($outputDir.'/'.$backupFileName)) {
            // If backup file doesn't exist, let's not continue
            print "Failed to create database backup. Terminating import.";
            die;
        }
        
        // Let's start by loading up the file
        $fileName = APP_BASE_PATH.'/private/uploads/csv/'.$import['fileName'];
        $first = true;
        $numRows = 0;
        
        if (($handle = fopen($fileName, 'r')) !== FALSE) { // Check the resource is valid
            while(($data = fgetcsv($handle, 0, ",")) !== FALSE) { // Check opening the file is OK!
                if ($first) {
                    // Ignore the first row since these are headings
                    $first = false;
                } else {                
                    
                    $numRows++;
                    
                    // Let's do something with each row of data that we're working with
                    // $data array
                    if ($import['dataType'] == "tms") {
                        
                        $primaryReference = $data[0];
                        $loadId = $data[1];
                        $customerInvoiceNumber = $data[2];
                        $actualDelivery = $data[3];
                        $billToCode = $data[4];
                        $carrierName = $data[5];
                        $customerRateTotal = $data[6];
                        $customerInvoiceTotal = $data[7];
                        $carrierRateTotal = $data[8];
                        $carrierInvoiceTotal = $data[9];
                        $oid = $data[10];
                        
                        if ($customerRateTotal == "") {
                            $customerRateTotal = 0;
                        }

                        if ($customerInvoiceTotal == "") {
                            $customerInvoiceTotal = 0;
                        }

                        if ($carrierInvoiceTotal == "") {
                            $carrierInvoiceTotal = 0;
                        }

                        if ($carrierRateTotal == "") {
                            $carrierRateTotal = 0;
                        }

                        // Remove dollar signs
                        $customerRateTotal = str_replace("$", "", $customerRateTotal);
                        $customerInvoiceTotal = str_replace("$", "", $customerInvoiceTotal);
                        $carrierRateTotal = str_replace("$", "", $carrierRateTotal);
                        $carrierInvoiceTotal = str_replace("$", "", $carrierInvoiceTotal);

                        // Remove commas
                        $customerRateTotal = str_replace(",", "", $customerRateTotal);
                        $customerInvoiceTotal = str_replace(",", "", $customerInvoiceTotal);
                        $carrierRateTotal = str_replace(",", "", $carrierRateTotal);
                        $carrierInvoiceTotal = str_replace(",", "", $carrierInvoiceTotal);

                        // Remove spaces
                        $customerRateTotal = str_replace(" ", "", $customerRateTotal);
                        $customerInvoiceTotal = str_replace(" ", "", $customerInvoiceTotal);
                        $carrierRateTotal = str_replace(" ", "", $carrierRateTotal);
                        $carrierInvoiceTotal = str_replace(" ", "", $carrierInvoiceTotal);

                        if ($actualDelivery != "") {
                            $actualDelivery = date('Y-m-d H:i:s', strtotime($actualDelivery));
                        } else {
                            $actualDelivery = "0000-00-00 00:00:00";
                        }

                        if ($oid == "") {
                            $oid = 0;
                        }

                        $sqlParms = [
                            $primaryReference, 
                            $loadId, 
                            $customerInvoiceNumber, 
                            $actualDelivery,
                            $billToCode, 
                            $carrierName, 
                            number_format($customerRateTotal, 2, '.', ''), 
                            number_format($customerInvoiceTotal, 2, '.', ''), 
                            number_format($carrierRateTotal, 2, '.', ''), 
                            number_format($carrierInvoiceTotal, 2, '.', ''), 
                            $oid
                        ];

                        // Let's see if we have a row matching the customer invoice number or not
                        $stmt = $dbCon->prepare('
                            SELECT * FROM `tmsData` 
                                WHERE `primaryReference` = ? 
                                AND `loadId` = ? 
                                AND `customerInvoiceNumber` = ? 
                                AND `actualDelivery` = ? 
                                AND `billToCode` = ? 
                                AND `carrierName` = ? 
                                AND `customerRateTotal` LIKE ? 
                                AND `customerInvoiceTotal` LIKE ? 
                                AND `carrierRateTotal` LIKE ? 
                                AND `carrierInvoiceTotal` LIKE ? 
                                AND `oid` = ?
                            '
                        );
                        $stmt->execute($sqlParms);
                        $num = $stmt->rowCount();

                        if ($num == 0) {
                            // If no row exists exactly, let's add it
                            $stmt = $dbCon->prepare('INSERT INTO `tmsData` (`primaryReference`, `loadId`, `customerInvoiceNumber`, `actualDelivery`, `billToCode`, `carrierName`, `customerRateTotal`, `customerInvoiceTotal`, `carrierRateTotal`, `carrierInvoiceTotal`, `oid`, `hasVariances`,`ignored`, `matched`, `revenueVariance`, `expenseVariance`, `marginVariance`, `ignoreFromExpenseVariance`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                            $stmt->execute([$primaryReference, $loadId, $customerInvoiceNumber, $actualDelivery, $billToCode, $carrierName, $customerRateTotal, $customerInvoiceTotal, $carrierRateTotal, $carrierInvoiceTotal, $oid, 0, 0, 0, 0.00, 0.00, 0.00, 0]);
                        }
                        
                        
                    }
                    
                    if ($import['dataType'] == "gp") {
                        
                        $journalEntry = $data[0];
                        $series = $data[1];
                        $trxDate = $data[2];
                        $accountNumber = $data[3];
                        $accountDescription = $data[4];
                        $debitAmount = $data[5];
                        $creditAmount = $data[6];
                        $originatingDocumentNumber = $data[7];
                        $originatingMasterName = $data[8];
                        $reference = $data[9];
                        $voided = $data[10];
                        $userWhoPosted = $data[11];

                        if ($debitAmount == "") {
                            $debitAmount = 0;
                        }

                        if ($creditAmount == "") {
                            $creditAmount = 0;
                        }

                        $trxDate = date('Y-m-d', strtotime($trxDate));
                        $debitAmount = number_format(str_replace(',', '', $debitAmount), 5, '.', '');
                        $creditAmount = number_format(str_replace(',', '', $creditAmount), 5, '.', '');
                        
                        $sqlParms = [
                            $journalEntry, 
                            $series, 
                            $trxDate, 
                            $accountNumber, 
                            $accountDescription, 
                            $debitAmount, 
                            $creditAmount, 
                            $originatingDocumentNumber, 
                            $originatingMasterName, 
                            $reference, 
                            $voided, 
                            $userWhoPosted
                        ];

                        // Let's see if we have a row matching the customer invoice number or not
                        $stmt = $dbCon->prepare(
                            'SELECT * FROM `gpData` 
                                WHERE `journalEntry` = ?
                                AND `series` = ?
                                AND `trxDate` = ?
                                AND `accountNumber` = ?
                                AND `accountDescription` = ?
                                AND `debitAmount` LIKE ?
                                AND `creditAmount` LIKE ?
                                AND `originatingDocumentNumber` = ?
                                AND `originatingMasterName` = ?
                                AND `reference` = ?
                                AND `voided` = ?
                                AND `userWhoPosted` = ?'
                        );
                        $stmt->execute($sqlParms);
                        $num = $stmt->rowCount();
                        
                        if ($num == 0) {
                            // If no row exists exactly, let's add it
                            $stmt = $dbCon->prepare('
                            INSERT INTO `gpData` (`journalEntry`, `series`, `trxDate`, `accountNumber`, `accountDescription`, `debitAmount`, `creditAmount`, `originatingDocumentNumber`, `originatingMasterName`, `reference`, `voided`, `userWhoPosted`, `gpTypeId`, `ignored`, `matched`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                            $stmt->execute(array($journalEntry, $series, $trxDate, $accountNumber, $accountDescription, $debitAmount, $creditAmount, $originatingDocumentNumber, $originatingMasterName, $reference, $voided, $userWhoPosted, 0, 0, 0));
                        }
                    }                    
                    
                }
            }
            fclose($handle);

            /*
            // We need to now process the data...trigger another process?? 
            // Or have the user still wait??
            
            // Get settings
            $stmt = $dbCon->prepare('SELECT * FROM `settings`');
            $stmt->execute(array());
            $allSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($allSettings as $setting) {
                $settings[$setting['name']] = $setting['value'];
            }
            // $settings['ignore-expense-variance-below']
            // $settings['processing-tms-gp-data']
            
            $customerInvoiceNumbersOnly = array();

            // Start by getting TMS data first
            $stmt = $dbCon->prepare('SELECT DISTINCT `customerInvoiceNumber` FROM `tmsData`');
            $stmt->execute(array());
            $tmsRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Let's first figure out our list of unique invoice numbers not ending in a letter
            foreach ($tmsRecords as $tmsRecord) {
                // Let's see if this is an invoice ending with a letter
                if (is_numeric(substr($tmsRecord['customerInvoiceNumber'], -1, 1))) {
                    // If we are on an invoice ending in a number
                    $customerInvoiceNumbersOnly[] = $tmsRecord['customerInvoiceNumber'];
                } else {
                    continue; // Do nothing for invoices ending with a letter because these will be associated to other records
                }
            }


            // Now that we have our list of unique invoice numbers generated, let's process them
            foreach ($customerInvoiceNumbersOnly as $customerInvoiceNumber) {
                // Reset some totals and variables
                $exportDate = '';
                $exportSONumber = '';
                $exportLoadID = '';
                $exportInvoiceNumber = 0.00;
                $exportGPRevenue = 0.00;
                $exportGPExpense = 0.00;
                $exportGPMargin = 0.00;
                $exportTMSRevenue = 0.00;
                $exportTMSExpense = 0.00;
                $exportTMSMargin = 0.00;
                $exportRevenueVariance = 0.00;
                $exportExpenseVariance = 0.00;
                $exportMarginVariance = 0.00;
                $exportAccrual = 0;
                $exportDup = 0;

                $tmsRevenueTotal = 0.00;
                $tmsExpenseTotal = 0.00;
                $gpExpenseTotal = 0.00;
                $gpCreditTotal = 0.00;
                $tmsMargin = 0.00;    

                $stmt = $dbCon->prepare('SELECT * FROM `tmsData` WHERE `customerInvoiceNumber` = ? OR `customerInvoiceNumber` = ? OR `customerInvoiceNumber` = ? OR `customerInvoiceNumber` = ? OR `customerInvoiceNumber` = ? OR `customerInvoiceNumber` = ? OR `customerInvoiceNumber` = ?');

                $stmt->execute(array($customerInvoiceNumber, $customerInvoiceNumber.'A', $customerInvoiceNumber.'B', $customerInvoiceNumber.'C', $customerInvoiceNumber.'D', $customerInvoiceNumber.'BD', $customerInvoiceNumber.'R'));

                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Do some maths here first to calculat the totals from TMS data
                foreach ($records as $record) {

                    $tmsRevenueTotal = $tmsRevenueTotal + $record['customerInvoiceTotal'];
                    $primaryReference = $record['primaryReference']; // SO Number
                    $exportSONumber = $primaryReference;
                    $exportLoadID = $record['loadId']; // Load ID
                    $tmsExpenseTotal = $record['carrierInvoiceTotal'];
                }

                // Get assoticated GP Data
                // Get GP Expense
                $stmt = $dbCon->prepare('SELECT * FROM `gpData` WHERE `reference` LIKE ? OR `reference` LIKE ? OR `reference` LIKE ? OR `reference` LIKE ? OR `reference` LIKE ? OR `reference` LIKE ? OR `reference` LIKE ? ORDER BY `gpData`.`journalEntry` ASC');
                $stmt->execute(array('%'.$primaryReference, '%'.$primaryReference.'A', '%'.$primaryReference.'B', '%'.$primaryReference.'C', '%'.$primaryReference.'D', '%'.$primaryReference.'BD', '%'.$primaryReference.'R'));
                $gpExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($gpExpenses as $gpExpense) {
                    $gpExpenseTotal = $gpExpenseTotal + $gpExpense['debitAmount'] - $gpExpense['creditAmount'];
                }

                // Get GP Inv
                $stmt = $dbCon->prepare('SELECT * FROM `gpData` WHERE `originatingDocumentNumber` = ? OR  `originatingDocumentNumber` = ? OR  `originatingDocumentNumber` = ? OR  `originatingDocumentNumber` = ? OR  `originatingDocumentNumber` = ? OR  `originatingDocumentNumber` = ? OR  `originatingDocumentNumber` = ? ORDER BY `gpData`.`journalEntry` ASC');
                $stmt->execute(array($customerInvoiceNumber, $customerInvoiceNumber.'A', $customerInvoiceNumber.'B', $customerInvoiceNumber.'C', $customerInvoiceNumber.'D', $customerInvoiceNumber.'BD', $customerInvoiceNumber.'R'));
                $gpCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($gpCredits as $gpCredit) {
                    $gpCreditTotal = $gpCreditTotal + $gpCredit['creditAmount'] - $gpCredit['debitAmount'];
                    if ($exportDate == NULL) {
                        $exportDate = date('m/d/Y', strtotime($gpCredit['trxDate']));
                    }
                }

                $exportInvoiceNumber = $customerInvoiceNumber;
                $exportGPRevenue = number_format($gpCreditTotal, 2, '.', '');
                $exportGPExpense = number_format($gpExpenseTotal, 2, '.', '');
                $exportGPMargin = number_format($exportGPRevenue - $exportGPExpense, 2, '.', '');
                $exportTMSRevenue = number_format($tmsRevenueTotal, 2, '.', '');
                $exportTMSExpense = number_format($tmsExpenseTotal, 2, '.', '');
                $exportTMSMargin = number_format($tmsRevenueTotal - $tmsExpenseTotal, 2, '.', '');

                $exportRevenueVariance = number_format($exportGPRevenue - $exportTMSRevenue, 2, '.', '');
                $exportExpenseVariance = number_format($exportGPExpense - $exportTMSExpense, 2, '.', '');
                $exportMarginVariance = number_format($exportGPMargin - $exportTMSMargin, 2, '.', '');

                if ($exportLoadID == "0") {
                    $exportAccrual = $exportGPExpense;
                } else {
                    $exportAccrual = 0;
                }

                if ($exportRevenueVariance == $exportGPRevenue) {
                    $exportDup = $exportGPRevenue;
                } else {
                    $exportDup = 0;
                }
                
                // Update TMS data to show calculated variances
                // Since we already have these rows in an array let's re-use it
                foreach ($records as $record) {
                    
                    $tmsId = $record['id'];
                    
                    
                    
                    if ($exportExpenseVariance < floatval($settings['ignore-expense-variance-below']) && $exportExpenseVariance > (-1 * floatval($settings['ignore-expense-variance-below'])) && $exportExpenseVariance != 0) {
                        $ignoreFromExpenseVariance = 1;
                    } else {
                        $ignoreFromExpenseVariance = 0;
                    }

                    if ($exportRevenueVariance != 0 || $exportExpenseVariance != 0 || $exportMarginVariance != 0) {
                        // If there is a variance
                        $stmt = $dbCon->prepare('UPDATE `tmsData` SET `revenueVariance` = ?, `expenseVariance` = ?, `marginVariance` = ?, `ignoreFromExpenseVariance` = ?, `hasVariances` = ? WHERE `id` = ?');
                        $stmt->execute(array($exportRevenueVariance, $exportExpenseVariance, $exportMarginVariance, $ignoreFromExpenseVariance, 1, $tmsId));
                    } else {
                        // If there is no variance, match?
                        $stmt = $dbCon->prepare('UPDATE `tmsData` SET `revenueVariance` = ?, `expenseVariance` = ?, `marginVariance` = ?, `ignoreFromExpenseVariance` = ?, `matched` = ? WHERE `id` = ?');
                        $stmt->execute(array($exportRevenueVariance, $exportExpenseVariance, $exportMarginVariance, $ignoreFromExpenseVariance, 1, $tmsId));
                    }
                    
                }

            }
            */

            // Mark the import as processed
            $stmt = $dbCon->prepare('UPDATE `import` SET `status` = ? WHERE `id` = ?');
            $stmt->execute(['processed', $importId]);

            $_SESSION['message'] = "Data has been imported and processed successfuly. ".$numRows." rows were processed. <strong>Process All Data now required.</strong>";
            print "ok";
            die;
        } else {
            print "Error opening file ".$fileName;
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