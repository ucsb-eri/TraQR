<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Todo");
    $hd->htmlBeg();
?>
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
                        <li>Add qr_uuid</li>
                        <li>add uuid field for our checksum/passcode/code data to associate with qrgen info</li>
                        <li>Remove building/room in scanData - those will be joined in from idInfo table</li>
                        <li>remove Mode? - not sure its needed in db at this point.<li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>
</section>

<section>
<h2>To Do - Other</h2>
<ul>
<li>General
  <ul>
    <li>Authenticate to SSO?</li>
  </ul>
</li>
<li>QR code generation
  <ul>
  <li>Input checking on room number(numerical with optionally one trailing alpha)</li>
  <li class="done">Switch to UCSBNetID based (aaron_martin@ucsb.edu)</li>
  </ul>
</li>
<li>Data Scanning
  <ul>
    <li class="done">Improve Display (you have been logged)</li>
    <li>Check for existence of user to flash NOT approved (?)</li>
  </ul>
</li>
<li>Data Display
  <ul>
    <li>Sortable</li>
    <li class="done">Some data checking (Egress with no ingress, ingress with no egress, who is in the building)</li>
  </ul>
</li>
<li>Data Management
  <ul>
    <li>Table with Mapping from UCSBNetID to FirstName, LastName, PhoneNumber (?)</li>
    <li class="done">Some data checking (Egress with no ingress, ingress with no egress, who is in the building)</li>
  </ul>
</li>
</ul>
</section>


<?php
    print $hd->contentTodo();
    $hd->htmlEnd();
?>
