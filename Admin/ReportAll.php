<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("QR Collection Data Display (SR)");
    $hd->htmlBeg();


    if ( authorized() ){
        $ce = new covidqrEntryNew(getDSN());
        print '<section>' . NL;
        print $ce->reportAll();
        print '</section>' . NL;
    }
    else print authFail();

    $hd->htmlEnd();
?>
