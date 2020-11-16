<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Import File");
    //$hd->css(CSSFILE);

    $hd->htmlBeg();

    if ( authorized() ){
        $b = '';
        $ce = new traQRpdo(getDSN());
        $b .= $ce->importFileForm();

        $b .= "<br><h3>Import File</h3>
        <ul>
        <li>File is expected to be a | delimited file.</li>
        <li>Controls:
            <ul>
                <li>The \"Delete\" Delete a single row from the table.  Has confirm/cancel functionality.</li>
                <li>The \"Edit\" button creates a form at the top of the page to modify the editable values for a single row.  Has Confirm/Cancel functionality.</li>
                <li>The \"Regen QR\" Loads QR info for any/all locations that the user has associated with that Identifier (up to the max).</li>
            </ul>
        </li>

        ";
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
