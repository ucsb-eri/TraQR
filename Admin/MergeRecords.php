<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Report/Analyze Data");
    $hd->htmlBeg();

    if ( authorized() ){
        $ce = new traQRpdo(getDSN());
        print '<section>' . NL;
        print $ce->mergeRecords('87','78');
        print '</section>' . NL;
    }
    else print authFail();

    $hd->htmlEnd();
?>
