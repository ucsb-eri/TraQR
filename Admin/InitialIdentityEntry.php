<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $tmgr = new traQRMgr(MAX_BUILDING_ROOM_COMBOS);
    $hd = new traqrDoc("Initial Identity Entry");
    //$hd->css(CSSFILE);
    $hd->htmlBeg();

    print '<section>' . NL;

    //$tmgr->checkPost();
    $tmgr->identityForm();
    $tmgr->displayQRcodesMode('DATA');

    print '</section>' . NL;
    $hd->htmlEnd();
?>
