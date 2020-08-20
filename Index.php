<?php
    require_once(__DIR__ . '/inc/all.inc.php');
    defineRelPath(__DIR__);

    //$cmgr = new traQRMgr(7);
    $hd = new traqrDoc("ERI TraQR Portal");
    $hd->htmlBeg();

    print '<section>' . NL;
    print $hd->contentIndex();
    print '</section>' . NL;

    //print '<a href="Logout.php">Logout</a><br>';
    $hd->htmlEnd();
?>
