<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Import File");
    //$hd->css(CSSFILE);

    $hd->htmlBeg();
    $b = '';

    if ( authorized() ){
        print_pre($_POST,"POST array");
        print_pre($_FILES,"FILES array");
        if (isset($_POST['submit'])){
            $b .= "Upload file detected<br>";

            $target_dir = REL . "var/uploads/";
            $target_file = $target_dir . 'uploaded.csv';
            $uploadOk = 1;
            if(isset($_POST["submit"])) {
                if($_FILES["fileToUpload"]["error"] == 0){
                    if( move_uploaded_file($_FILES['fileToUpload']['tmp_name'],$target_file)){
                        $b .= "File successfully moved to $target_file<br>";
                    }
                    else {
                        $b .= "Failed to move uploaded file to: $target_file<br>";
                    }
                }
            }
            else {
                $b .= "Upload Failed for some reason<br>";
            }
        }

        $ce = new traQRpdo(getDSN());
        // $b .= $ce->importFileForm();
        $b .= "<br><hr>
        <span style=\"color: red;\">
        <h3>NOTE: This is not ready for \"Prime Time\" yet.</h3>
        Contact someone on the dev team to use this functionality.
        </span>
        ";

        $b .= "<hr><form method=\"post\" enctype=\"multipart/form-data\">
  Select Pipe \",\" Separated Value (CSV) import text file to upload:
  <input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\">
  <input type=\"submit\" value=\"Upload Data File\" name=\"submit\">
</form>";


        $b .= "<br><hr><h3>Import File Description</h3>
        <ul>
        <li>File is expected to be a CSV text file containing multiple id+qr records (one record/line)</li>
        <li>Each record has the following 10 &quot;,&quot; delimited fields:
            <ul>
            <li>id_ident - individual's unique identifier (Required: usually email)</li>
            <li>id_name_first - individuals first name (optional)</li>
            <li>id_name_last - individuals last name (optional)</li>
            <li>id_phone - individuals phone number (optional)</li>
            <li>id_email - individuals email (optional)</li>
            <li>id_UCSBNetID - individuals UCSBNetID (optional)</li>
            <li>id_dept - individuals department at the university or other primary affiliation (optional)</li>
            <li>qr_building - building name that individual will be occupying (Required)</li>
            <li>qr_room - room or room cluster that the individual will be occupying (Required)</li>
            <li>qr_detail - if room cluster is used, this should provide details of location(s) involved (optional)</li>
            </ul>
        </li>
        <li>All 10 fields are REQUIRED for each import record:
            <ul>
            <li>The &quot;Required&quot; and &quot;Optional&quot; notes above concern what the overall system needs to operate.
            </ul>
        </li>

        <li>Header should look like:
            <ul>
                <li>id_ident,id_name_first,id_name_last,id_phone,id_email,id_UCSBNetID,id_dept,qr_building,qr_room,qr_detail</li>
            </ul>
        </li>
        <li>Prepping export file from Google Sheets:
            <ul>
                <li>Step 1:</li>
            </ul>
        </li>

        ";
    }
    else $b .= authFail();

    print "$b";

    $hd->htmlEnd();
?>
