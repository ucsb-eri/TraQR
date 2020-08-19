<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("QR Collection Data Display (SR)");
    $hd->css(CSSFILE);

    $ce = new covidqrEntryNew(getDSN());

    $hd->htmlBeg();

    if ( authorized() ){
        print '<section>' . NL;
        print $ce->reportAll();
        print '</section>' . NL;
    }
    else print authFail();

    $hd->htmlEnd();
?>
