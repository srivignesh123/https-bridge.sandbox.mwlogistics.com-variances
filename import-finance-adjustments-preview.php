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
    
    if ($import['dataType'] == "tms") {
        $dataType = "TMS";
    }
    
    if ($import['dataType'] == "gp") {
        $dataType = "GP";
    }
    
    $_SESSION['lastPage'] = "import-data";
    $pageCSS = "";
    require("inc/head.php");
    ?>

        <!-- Page Content -->
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header"><i class="fa fa-eye fa-fw"></i> Import Data Preview <?php print $dataType; ?></h3>
                    </div>
                    <!-- /.col-lg-12 -->
                </div>
                <!-- /.row -->
                <?php if (isset($_SESSION['error-message'])) { ?>
                <div class="row">
                    <div class="col-lg-12">
                        <?php
                        if ($_SESSION['error-message']) {
                            print '<div class="alert alert-danger">'.$_SESSION['error-message'].'</div>';
                            //print '<div style="height: 15px;"></div>';
                            unset($_SESSION['error-message']);    
                        }
                        ?>                        
                    </div>
                </div>
                <!-- /.row -->
                <?php } ?> 
                <?php if (isset($_SESSION['message'])) { ?>
                <div class="row">
                    <div class="col-lg-12">
                        <?php
                        if ($_SESSION['message']) {
                            print '<div class="alert alert-success">'.$_SESSION['message'].'</div>';
                            //print '<div style="height: 15px;"></div>';
                            unset($_SESSION['message']);    
                        }
                        ?>
                    </div>
                </div>
                <!-- /.row -->
                <?php } ?>
                <div class="row">
                    <div class="col-lg-12">
                        <div id="preview-output"></div>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- /#page-wrapper -->

        <!-- Modal -->
        <div class="modal fade" id="confirm-import-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel">Confirm Import</h4>
                    </div>
                    <div class="modal-body">
                        <h4>Are you sure you would like to process this import?</h4>
                        <div style="height: 10px;"></div>
                        <p><strong>Please Note:</strong> Processing this import will add new data to the database if *any* column doesn't match a row from the CSV file. Matching data is based on every column matching database columns and CSV file columns. If this is the case the row will be skipped, and if not, added.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="confirm-import-modal-cancel">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirm-import-modal-import">Import</button>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->

        <!-- Modal -->
        <div class="modal fade" id="import-please-wait-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <h3 style="text-align: center;">Importing Data</h3>
                        <p style="text-align: center;">
                            Please wait while we work on importing the data from the CSV file. This may take a few minutes. Depending on how much data there is, this process may take longer...
                        </p>
                        <p style="text-align: center; font-weight: bold;">DO NOT RELOAD OR CLOSE THE PAGE UNTIL THIS IS COMPLETE</p>
                        <h2 style="text-align: center;"><i class="fa fa-spinner fa-pulse"></i></h2>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
        
    <?php
    $pageJS = "
    <script nonce='".$nonce."'>
    $(function() {
        showPleaseWait();
        setTimeout(function() {
        $.ajax({
            url: 'import-data-preview-process'
        }).done(function(response){ 
            hidePleaseWait();
            $('#preview-output').html(response);
        });
        }, 500);
        
        $(document).on('click', '#dont-import', function() {
            window.location.href='import-data';
        });
        
        $(document).on('click', '#import-proceed', function() {
            $('#confirm-import-modal').modal('show');
        });
        
        $('#confirm-import-modal-cancel').on('click', function() {
            $('#confirm-import-modal').modal('hide');
        });
        
        $('#confirm-import-modal-import').on('click', function() {
            $('#confirm-import-modal').modal('hide');
            $('#import-please-wait-modal').modal('show');
            $.ajax({
                type: 'POST',
                url: 'import-finance-adjustments-process-file',
                data: { importId: ".$importId." }
            }).done(function(response) {
                if (response == 'ok') {
                    window.location.href='import-data';
                } else {
                    $('#import-please-wait-modal').modal('hide');
                    showError(response);
                }
            });            
        });
        
        $(document).on('click', '#cancel-import', function() {
            window.location.href='import-data';
        });
        
    });
    </script>";
    require("inc/foot.php");
    
} else {
    
    $_SESSION['lastPage'] = "import-data";
    header('Location: login');
    die;
        
}
?>