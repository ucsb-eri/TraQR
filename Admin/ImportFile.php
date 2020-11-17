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
            $target_file = $target_dir . 'uploaded.psv';
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

        $b .= "<form method=\"post\" enctype=\"multipart/form-data\">
  Select Pipe \"|\" Separated Value import text file to upload:
  <input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\">
  <input type=\"submit\" value=\"Upload Data File\" name=\"submit\">
</form>";


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
    }
    else $b .= authFail();
    print "$b";

    $hd->htmlEnd();
?>
