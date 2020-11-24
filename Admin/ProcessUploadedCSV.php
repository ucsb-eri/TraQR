<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("Process Uploaded Import File");
    $uploaded = REL . "var/uploads/uploaded.csv";
    //$hd->css(CSSFILE);

    $hd->htmlBeg();
    $b = '';

    if ( authorized() ){
        if ( file_exists($uploaded)) {
            $ce = new traQRpdo(getDSN());

            $b .= "
            Begin processing previously uploaded file: $uploaded file
            ";
            // $row = 1;
            $data = readCSV($uploaded);

            $requiredFields = array('id_ident','qr_ident','qr_building','qr_room','qr_uuid');
            $optionalFields = array('id_email','id_name_first','id_name_last','id_phone','id_dept','id_UCSBNetID','qr_detail');

            $idFields = array('id_ident','id_dept','id_email','id_name_first','id_name_last','id_phone','id_UCSBNetID');
            $qrFields = array('qr_uuid','qr_ident','qr_building','qr_room','qr_detail');
            // Pre process data:
            // We can reasonably define some defaults/conversions/reuse
            foreach($data as &$d){
                // if id_ident doesnt exist or is empty, but id_email is set and appears to be an email, use that
                if(! isset($d['id_ident']) || $d['id_ident'] == ''){
                    if( isset($d['id_email']) && (strpos($d['id_email'],"@")) !== FALSE ) $d['id_ident'] = $d['id_email'];
                }
                // if id_email field doesn't exist or is blank, but id_ident looks like an email address, the use that
                if(! isset($d['id_email']) || $d['id_email'] == ''){
                    if( isset($d['id_ident']) && (strpos($d['id_ident'],"@")) !== FALSE ) $d['id_email'] = $d['id_ident'];
                }

                // hardwire the qr_ident to id_ident value
                $d['qr_ident'] = $d['id_ident'];

                if (endsWith($d['id_email'],'@ucsb.edu')){
                    list($netid,$unused) = explode("@",$d['id_email']);
                    $d['id_UCSBNetID'] = $netid;
                }

                // make sure we have indexes for any optional fields
                foreach($optionalFields as $opt){
                    if (! isset($d[$opt])) $d[$opt] = "";
                }
            }
            foreach($data as &$d){
                // generate qr_uuid now....
                $d['qr_uuid'] = genUUID($d['qr_ident'],$d['qr_building'],$d['qr_room']);

                // Make sure we have required fields
                foreach($requiredFields as $req){
                    if (! isset($d[$req])) {
                        $b .= "Failed data row - field: $req<br>\n";
                        continue;
                    }
                }
                // OK, so things are reasonable, lets upsert idInfo and qrInfo entries
                $ce->upsert('idInfo',array('id_ident'),$d,$idFields);
                $ce->upsert('qrInfo',array('qr_uuid'),$d,$qrFields);
            }
            print_pre($data,"CSV Data");
        }
        else {
            $b .= alertBanner('','Upload file not found');
        }
    }
    else $b .= authFail();

    print "$b";

    $hd->htmlEnd();
?>
