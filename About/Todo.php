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
                        <li class="done">move pCMZ, aCMZ into another table that maps in building and room number?</li>
                        <li class="done">Remove building/room in scanData - those will be joined in from idInfo table</li>
                        <li>remove Mode? - not sure its needed in db at this point.<li>
                    </ul>
                </li>
                <li>qrInfo
                    <ul>
                        <li class="done">Add qr_uuid</li>
                        <li class="done">add uuid field for our checksum/passcode/code data to associate with qrgen info</li>
                    </ul>
                </li>
            </ul>
        </li>
        <li>UI/UX:
            <ul>
                <li>Remove old generateQR menu item</li>
                <li>Add links to Help Index</li>
                <li>Add version somewhere (this is included below as well)</li>
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
      <li>Add version to about page?  Or elsewhere?</li>
  </ul>
</li>
<li>QR code generation
  <ul>
      <li>clean up new QR display code:
          <ul>
              <li>remove st_mode if possible (ie: BIDIR)</li>
              <li>remove looping over mode... part of above</li>
              <li>utilize qr_uuid in file names:
                  <ul>
                      <li>This might remove need to index qr filenames in loops</li>
                      <li>Would allow for checking for existence of file before doing generation</li>
                  </ul>
              </li>

          </ul>
      </li>
      <li>Input checking on room number(numerical with optionally one trailing alpha)</li>
      <li class="done">Switch to UCSBNetID based (aaron_martin@ucsb.edu)</li>
      <li>Add in QR code display to new Identity form
          <ul>
              <li>Modularize to just show based on qr_uuid, pass in as an array?</li>
          </ul>
      </li>
      <li>Remove old QR Code Generation once the above is done</li>


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
    //print $hd->contentTodo();
    $hd->htmlEnd();
?>
