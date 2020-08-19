<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("QR Management");
    $hd->htmlBeg();

    if ( authorized() ){
        $b = '';
        $ce = new covidqrEntryNew(getDSN());
        $b .= $ce->displayQrInfo();

        $b .= "<br><h3>General Info, Controls and Descriptions</h3>
        <ul>
            <li>Entries in this table are added during the generation of QR codes.</li>
            <li>Each QR code created generates a single row of data in this table.</li>
            <li>In the near future, this table will be used to validate incoming scans.  ie:
            make sure that the data being presented has been registered as valid during an administratively
            controlled process.</li>
            <li>Controls:
                <ul>
                <li>The \"Delete\" single row.  Confirm/Cancel functionality.</li>
                <li>\"Regen QR\" button - regenerate QR codes for this specific QR entry.  Confirm/Cancel functionality.</li>
                </ul>
            </li>
        </ul>
        ";
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
