<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Report/Analyze Data");
    $hd->css(CSSFILE);

    $ce = new covidqrEntryNew(getDSN());

    if (isset($_GET['Mode']) && $_GET['Mode'] == 'email') $hd->setOption('embed-styles',TRUE);

    $hd->htmlBeg();

    if ( authorized() ){
        print '<section>' . NL;
        print $ce->analyzeData();
        print '</section>' . NL;
    }
    else print authFail();

    $hd->htmlEnd();
?>
