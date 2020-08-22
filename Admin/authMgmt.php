<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Auth Management");
    //$hd->css(CSSFILE);

    $hd->htmlBeg();

    if ( authorized() ){
        $b = '';
        $ce = new traQRpdo(getDSN());
        $b .= $ce->authManagement();

        $b .= "<br><h3>General Information and Controls</h3>
        <ul>
        <li>Controls:
            <ul>
                <li>The \"Delete\" Delete a single row from the table.  Has confirm/cancel functionality.</li>
                <li>The \"Edit\" button creates a form at the top of the page to modify the editable values for a single row.  Has Confirm/Cancel functionality.</li>
            </ul>
        </li>

        ";
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
