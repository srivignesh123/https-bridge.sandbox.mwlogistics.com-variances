<?php
require "config/db.php";
require "inc/functions.php";
require "inc/must-haves.php";

if ($LoggedIn == "TRUE") {

    if (!in_array("Super Administrator", $UserLevels) && !in_array("Finance Administrator", $UserLevels)) {
        header("Location: ./");
        die;
    }
    
    // Get previous imports
    $stmt = $dbCon->prepare('SELECT * FROM `import` ORDER BY `date` DESC');
    $stmt->execute(array());
    $count = $stmt->rowCount();

    $perpage = RESULTS_PER_PAGE;

    if (isset($_GET["page"])){
        $pageView = intval($_GET["page"]);
    } else {
        $pageView = 1;
    }

    $calc = $perpage * $pageView;
    $start = $calc - $perpage;
    $totalPages = ceil($count / $perpage);
    
    $stmt = $dbCon->prepare('SELECT * FROM `import` ORDER BY `date` DESC LIMIT '.$perpage.' OFFSET '.$start.'');
    $stmt->execute(array());
    $imports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $_SESSION['lastPage'] = "import-data";
    $pageCSS = "";
    require("inc/head.php");
    ?>

        <!-- Page Content -->
        <div id="page-wrapper">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header">
                            <i class="fa fa-cloud-upload fa-fw"></i> Import Data
                            <div class="pull-right">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" style="padding: 4px 4px;">
                                        <i class="fa fa-plus fa-fw"></i>
                                    </button>
                                    <ul class="dropdown-menu pull-right" role="menu">
                                        <li>
                                            <a href="#" id="import-tms-data-button">Import TMS Data</a>
                                        </li>
                                        <li>
                                            <a href="#" id="import-gp-data-button">Import GP Data</a>
                                        </li>
                                        <li>
                                            <a href="#" id="import-tms-gp-data-process">Process All Data</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </h3>
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
                        <div class="alert alert-info">
                            <p>If you have more data to import from TMS or GP, you can import the data in to the bridge portal from a CSV file. Please note there are different CSV file headers that need to be used in order for an import to be successful. To get started importing data, click the plus button above, and select the appropriate option from the drop down. See previous imports below.</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">Date</th>
                                        <th style="width: 120px;">Import Type</th>
                                        <th style="width: 350px;">File Name</th>
                                        <th style="width: 250px;">Status</th>
                                        <th style="text-align: right;">
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach($imports as $import) {
                                        ?>
                                        <tr>
                                            <td><?php print date('m/d/Y h:i A', strtotime($import['date'])); ?></td>
                                            <td><?php if ($import['dataType'] == "tms"){ print "TMS"; } else if ($import['dataType'] == "gp") { print "GP"; } ?></td>
                                            <td><?php print $import['originalName']; ?></td>
                                            <td><?php print $import['status']; ?></td>
                                            <td style="text-align: right;">
                                                <button class="btn btn-xs btn-primary preview-import" data-import-id="<?php print $import['id']; ?>">
                                                    Preview
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($totalPages > 1) { ?>
                        <ul class="pagination">
                            <li<?php if ($pageView == 1) { ?> class="disabled"<?php } ?>><a href="import-data">First Page</a></li>
                                <?php
                                  if ($totalPages > 10) {

                                    if ($pageView <= 5) {

                                        // If we are within the first 5 pages...
                                        for($i=1; $i<= 10; $i++) {
                                            ?>
                                            <li <?php if ($pageView == $i) { ?>class="active"<?php } ?>><a href="import-data?page=<?php print $i; ?>"><?php print $i; ?></a></li>
                                            <?php  
                                        }

                                    } else {

                                        if ($totalPages - $pageView <= 5) {

                                            // If we have less than 5 pages of results left
                                            for($i=$totalPages - 9; $i<= $totalPages; $i++) {
                                                ?>
                                                <li <?php if ($pageView == $i) { ?>class="active"<?php } ?>><a href="import-data?page=<?php print $i; ?>"><?php print $i; ?></a></li>
                                                <?php  
                                            }

                                        } else {

                                            for($i=$pageView - 5; $i<= $pageView + 4; $i++) {
                                                ?>
                                                <li <?php if ($pageView == $i) { ?>class="active"<?php } ?>><a href="import-data?page=<?php print $i; ?>"><?php print $i; ?></a></li>
                                                <?php  
                                            }    

                                        }

                                    }

                                  } else {
                                    for($i=1; $i<= $totalPages; $i++) {
                                        ?>
                                        <li <?php if ($pageView == $i) { ?>class="active"<?php } ?>><a href="import-data?page=<?php print $i; ?>"><?php print $i; ?></a></li>
                                        <?php  
                                    }          
                                  }
                                ?>
                            <li <?php if ($pageView >= $totalPages) { ?> class="disabled"<?php } ?>><a href="import-data?page=<?php print $totalPages; ?>">Last Page</a></li>
                        </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- /#page-wrapper -->

        <!-- Modal -->
        <div class="modal fade" id="data-process-please-wait-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-keyboard="false" data-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <h3 style="text-align: center;">Processing Data</h3>
                        <p style="text-align: center;">
                            Please wait while we work on processing all the TMS and GP data. This will take a while.
                        </p>
                        <p style="text-align: center; font-weight: bold;">DO NOT RELOAD OR CLOSE THE PAGE UNTIL THIS IS COMPLETE</p>
                        <h2 style="text-align: center;"><i class="fa fa-spinner fa-pulse"></i></h2>
                        <div style="height: 20px;"></div>
                        <div class="progress">
                            <div id="data-process-progress" class="progress-bar progress-bar-danger progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%; text-align: right; padding-right: 5px;"></div>
                        </div>

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
    var dataType = '';
    
    $(function() {
        
        new AjaxUpload(
            $('#import-tms-data-button'), {  
                action: 'import-data-process?dataType=tms',  
                //Name of the file input box  
                name: 'data-import',
                onSubmit: function(file, ext){  
                    if (! (ext && /^(csv)$/.test(ext))){   
                          // check for valid file extension   
                        showError('Only CSV files are allowed.');
                        return false;  
                    }  
                    showPleaseWait();
                },  
                onComplete: function(file, response){  
                    hidePleaseWait();
                    //Add uploaded file to list  
                    if (response==='ok'){
                        window.location.href='import-data-preview'
                    } else{  
                        showError(response); 
                    }  
                }  
            }
        );
        
        new AjaxUpload(
            $('#import-gp-data-button'), {  
                action: 'import-finance-adjustments-process?dataType=gp',  
                //Name of the file input box  
                name: 'data-import',
                onSubmit: function(file, ext){  
                    if (! (ext && /^(csv)$/.test(ext))){   
                          // check for valid file extension   
                        showError('Only CSV files are allowed.');
                        return false;  
                    }  
                    showPleaseWait();
                },  
                onComplete: function(file, response){  
                    hidePleaseWait();
                    //Add uploaded file to list  
                    if (response==='ok'){
                        window.location.href='import-data-preview'
                    } else{  
                        showError(response); 
                    }  
                }  
            }
        );
        
        $('.preview-import').on('click', function() {
            var importId = $(this).data('import-id');
            window.location.href='import-data-preview-import?id='+importId;
        });

        $('#import-tms-gp-data-process').on('click', function(e) {
            e.preventDefault();
            
            $('#data-process-please-wait-modal').modal('show');
            
            var es = new EventSource('finance-tms-gp-data-process');

            es.addEventListener('message', function(e) {
                var result = JSON.parse( e.data );
                if(e.lastEventId == 'CLOSE') {
                    es.close();
                    $('#data-process-progress').css('width', '100%').attr('aria-valuenow', 100).text('100%');
                    setTimeout(function() { window.location.reload(); }, 2000);
                } else {
                    $('#data-process-progress').css('width', result.progress+'%').attr('aria-valuenow', result.progress).text(result.progress+'%');
                }
            });

        });

        
        
    });
    </script>";
    require("inc/foot.php");
    
} else {
    
    $_SESSION['lastPage'] = "import-data";
    header('Location: login');
    die;
        
}

/*
$("#data-process-progress")
      .css("width", current_progress + "%")
      .attr("aria-valuenow", current_progress)
      .text(current_progress + "% Complete");

$('#import-tms-gp-data-process').on('click', function(e) {
            e.preventDefault();
            
            $('#data-process-please-wait-modal').modal('show');
            $.ajax({
                url: 'finance-tms-gp-data-process',
                type: 'GET'
            }).done(function(response) { 
                if(response == 'ok') {
                    window.location.reload();
                } else if(response == 'processing') {
                    $('#data-process-please-wait-modal').modal('hide');
                    setTimeout(function() { showError('The system is already processing data. Please try again in a few minutes.'); }, 400);
                } else {
                    $('#data-process-please-wait-modal').modal('hide');
                    setTimeout(function(response) { showError(response); }, 400);
                }
            });
        });
*/
?>