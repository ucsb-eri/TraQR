<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("About this site");
    $hd->htmlBeg();
?>
<section>
    <h2>Introduction/History</h2>
    <p>This site originated as a proof of concept test of using customized QR codes for doing ingress/egress scans for research and essential personnel to access UCSB facilities.
        Building committees were looking for solutions that provides a few items that the ETS sponsored app was unable to provide:</p>
    </p>
    <ul>
        <li>Support for older phones (UCSB app will not run on older phones).</li>
        <li>Keep user from having to type a lot of info into a phone to provide necessary info.</li>
        <li>Provide some data consistency (kinda coupled with the entry above) something to minimize differences in data entry.</li>
        <li>Data maintained locally and accessible to personnel that needed access.</li>
    </ul>
    <p>Since it showed some promise, additional effort has been directed at the project.</p>
    <p>Original Concept and Initial Developement by Aaron Martin, Earth Research Institute (ERI) August 2020</p>
    <h2>Development Evolution</h2>
    <p>Initial data entry was separate records for INGRESS and EGRESS.<br>
        Pretty early on that was switched to a single record.  INGRESS created a row in the db.  EGRESS found a matching row and updated.<br>
        Shortly thereafter, the INGRESS/EGRESS mode was ignored and matching was done by whether there was a matching records or not.<br>
        At that point we were still making direct entries to the db from a visit to the page.  But we quickly realized that it was either from caching or even more likely that it was from page reloads.<br>
        To deal with that we added a confirmation button.  Because the data had to pass through the site twice, we are able to detect the time delay and invalidate the confirmation if it happens too long after the scan (or initial visit)<br>

    <p>As more interest started to be expressed in this tool, we started to start considering what would be required for a broader distribution.  Some concerns were:
        <ul>
            <li>folks figuring out the patten and dumping false data into the system</li>
            <li>how to handle non-ucsb folks (contractors) that would not have UCSBNetIDs</li>
        </ul>
        The next stage of development will likely focus on replacing the triplet of data (Identifier (ie: UCSBNetID email in most cases), Building, Room) with a code/key/passcode/uuid of some type.  Security wise, if we load the code/key/passcode/uuid into the db when QR codes
        are generated, we could just check incoming entries against that db to make sure the code is valid.  If not connection dropped.

</section>
<section>
    <h2>ToDo - Next Steps</h2>
    <ul>
        <li>DB Schema updates:
            <ul>
                <li>scanData
                    <ul>
                        <li>move pCMZ, aCMZ into another table that maps in building and room number?</li>
                        <li>add uuid (sc_uuid?) field for our checksum/passcode/code data to associate with qrgen info</li>
                        <li>Remove building/room in scanData - those will be joined in from idInfo table</li>
                        <li>remove Mode? - not sure its needed in db at this point.<li>
                    </ul>
                </li>
                <li>qrInfo
                    <ul>
                        <li>Add qi_uuid</li>
                        <li>add uuid field for our checksum/passcode/code data to associate with qrgen info</li>
                        <li>Remove building/room in scanData - those will be joined in from idInfo table</li>
                        <li>remove Mode? - not sure its needed in db at this point.<li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>
</section>

<?php

    $hd->htmlEnd();
?>
