<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $cmgr = new covidQRMgr(MAX_BUILDING_ROOM_COMBOS);
    $hd = new traqrDoc("Generate QR Codes");
    $hd->css(CSSFILE);
    $hd->htmlBeg();

    print '<section>' . NL;

    $cmgr->checkPost();
    print $cmgr->htmlForm();

    $cmgr->doQRcodes();

    print '</section>' . NL;
    $hd->htmlEnd();
?>
