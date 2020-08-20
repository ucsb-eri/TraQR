<?php
    require_once(__DIR__ . '/inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Report/Analyze Data");
    $ce = new traQRpdo(getDSN());

    if (isset($_GET['Mode']) && $_GET['Mode'] == 'email') $hd->setOption('embed-styles',TRUE);

    $hd->htmlBeg();

    if ( authorized() ){
        print '<section>' . NL;
        print $ce->clearTestEntries();
        print '</section>' . NL;
    }
    else print authFail();

    $hd->htmlEnd();
?>
