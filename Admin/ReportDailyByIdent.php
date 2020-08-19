<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Report/Analyze Data");
    if (isset($_GET['Mode']) && $_GET['Mode'] == 'email') $hd->setOption('embed-styles',TRUE);
    $hd->htmlBeg();

    if ( authorized() ){
        $ce = new covidqrEntryNew(getDSN());
        print '<section>' . NL;
        print $ce->analyzeData();
        print '</section>' . NL;
    }
    else print authFail();

    $hd->htmlEnd();
?>
