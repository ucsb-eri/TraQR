<?php
    require_once(__DIR__ . '/inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Data Entry Point");
    $hd->setOption('nav',FALSE);
    $hd->htmlBeg();

    $ce2 = new traQRpdo(getDSN());
    $ce2->submitDataForProcessing();

    $hd->htmlEnd();
?>
