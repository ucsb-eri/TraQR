<?php
require_once(__DIR__ . '/../ext/phpqrcode/qrlib.php');
require_once(__DIR__ . '/utils.inc.php');

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
class traQRMgr {
    function __construct($numRooms){
        $this->buildingEntries = range(1,$numRooms);
        // this data is for if we are sending post data from another page to regenerate
        // an already existing QR
        $this->idData = array();
        $this->qrData = array();
        $this->regenData = array();
        $this->checkPostForVars();
    }
    function htmlFormInput($size,$name,$desc,$value = ''){
        $b = '';
        $val =  ( $value != '') ? $value : "{$GLOBALS[$name]}" ;
        //$val = "Fake Value";
        $b .= '<input type="text" size="' . $size . '" id="' . $name . '" name="' . $name . '" value="' . $val . '" placeholder="' . $desc . '">';
        return $b;
    }
    function identityFormInput($size,$name,$desc,$value = ''){
        $b = '';
        $globalVal = ( array_key_exists($name,$GLOBALS)) ? "{$GLOBALS[$name]}" : '';
        $globalVal = ( array_key_exists($name,$this->idData) && $globalVal == '') ? "{$this->idData[$name]}" : '';
        $val =  ( $value != '') ? $value : "$globalVal" ;
        //$val = "Fake Value";
        $b .= '<input type="text" size="' . $size . '" id="' . $name . '" name="' . $name . '" value="' . $val . '" placeholder="' . $desc . '">';
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function checkPostForVars(){
        $f = 'Identifier';
        if(array_key_exists($f,$_POST)){
            $this->regenData[$f] = filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING);
        }
        foreach($this->buildingEntries as $num){
            $f = 'Building'.$num;
            if(array_key_exists($f,$_POST)){
                $this->regenData[$f] = filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING);
            }
            $f = 'Room'.$num;
            if(array_key_exists($f,$_POST)){
                $this->regenData[$f] = filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING);
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    function identityFormInfo(){
        $b = '';
        $b .= "<strong>Identifier Order Preference:</strong><ul>
        <li>UCSBNetID based email: ie: &lt;UCSBNetID@ucsb.edu&gt;</li>
        <li>Email: ie: user@gmail.com</li>
        <li>Contractor-firstnameLastname (in title case): ie: ADT-JoeGaucho</li>
        <li>firstnameLastname (in title case)</li>
        <li>Phone: ie: 805-012-4567</li>
        </ul>
        ";
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function sanitizePost(&$destArray = array(),$keys = array(),$filter_input_type){
        foreach($keys as $key){
            $destArray[$key] = trim(filter_input(INPUT_POST,$key,$filter_input_type));
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    function identityFormPostCheck(){
        $tpdo = new traQRpdo(getDSN());
        $idKeys = array('id_ident','id_name_first','id_name_last','id_phone','id_email','id_UCSBNetID','id_extra','id_dept');
        //$strKeys = array('id_dept','id_phone');
        // should setup separate/specialized sanitization filters for phone number

        // want to look to see if we have a post for the identity form
        if( ! array_key_exists('id_ident',     $_POST)) return false;
        if( ! array_key_exists('id_name_first',$_POST)) return false;
        if( ! array_key_exists('id_name_last', $_POST)) return false;

        //print_pre($_POST,__METHOD__ . ": POST vars");
        //$this->sanitizePost($this->idData,array('id_ident','id_name_first','id_name_last','id_UCSBNetID','id_phone','id_extra'),FILTER_SANITIZE_STRING);
        // build list of string keys for sanitizing strings
        $strKeys = array();  // could possibly just do $strKeys = $idKeys;
        foreach($idKeys as $idk) $strKeys[] = $idk;

        foreach($this->buildingEntries as $num){
            $strKeys[] = 'Building' . $num;
            $strKeys[] = 'Room' . $num;
            $strKeys[] = 'Detail' . $num;
        }
        // do general filtering here at this step for everything
        $this->sanitizePost($this->idData,$strKeys,FILTER_SANITIZE_STRING);

        // now cover any specialized entries
        $this->sanitizePost($this->idData,array('id_email'),FILTER_SANITIZE_EMAIL);
        //print_pre($this->idData,__METHOD__ . ': idData');

        // do an upsert into idInfo
        if ($this->idData['id_ident'] == '') {
            print alertBanner('',"Ident field blank - skipping upsert");
            return "";
        }
        else {
            $tpdo->upsert('idInfo','id_ident',$this->idData,$idKeys);
        }

        ////////////////////////////////////////////////////////////////////////
        // work on qrInfo entries now
        ////////////////////////////////////////////////////////////////////////
        $qrKeys = array('qr_uuid','qr_ident','qr_building','qr_room','qr_detail');
        $this->qrData['qr_ident'] = $this->idData['id_ident'];
        // $tpdo->upsert('qrInfo','qr_uuid',$this->qrData,$idKeys);
        foreach($this->buildingEntries as $num){
            //foreach(array('Building','Room','Detail') as $key)
            $bkey = 'Building' . $num;
            $rkey = 'Room' . $num;
            $dkey = 'Detail' . $num;
            if( $this->qrData['qr_ident'] == '' || $this->idData[$bkey] == '' || $this->idData[$rkey] == '' ) continue;
            $this->qrData['qr_building'] = $this->idData[$bkey];
            $this->qrData['qr_room'] = $this->idData[$rkey];
            $this->qrData['qr_detail'] = $this->idData[$dkey];
            $this->qrData['qr_uuid'] = genUUID($this->qrData['qr_ident'],$this->qrData['qr_building'],$this->qrData['qr_room']);
            print_pre($this->qrData,__METHOD__ . ': qrData');

            // upsert QR table info
            $tpdo->upsert('qrInfo','qr_uuid',$this->qrData,$qrKeys);
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    function identityForm(){
        // print_pre($_POST,"POST vars");
        // print_pre($this->regenData,"Internal Regen Data");
        $rowsPerCol = 2;
        $rowCntr = 0;
        // currently no plan to support regenData type setup here, data is coming from/to multiple tables
        // if (isset($this->regenData['Identifier'])){
        //     $GLOBALS['Identfier'] = $this->regenData['Identifier'];
        //     foreach($this->buildingEntries as $num){
        //         if (isset($this->regenData['Building'.$num]))  $GLOBALS['Building'.$num] = $this->regenData['Building'.$num];
        //         if (isset($this->regenData['Room'.$num]))      $GLOBALS['Room'.$num]     = $this->regenData['Room'.$num];
        //
        //     }
        // }
        $b = '';
        $b .= "<button onclick=\"hideShowIdentityForm()\">Hide/Show Form</button>\n";
        $b .= "<div id=\"IdentityFormContainer\"><!-- begin IdentityFormContainer -->\n";
        $b .= "<div id=\"IdentityFormDiv\"><!-- begin IdentityFormDiv -->\n";
        $b .= $this->identityFormPostCheck();
        $b .= $this->identityFormInfo();

        $b .= '<div class="qr-id-form-container">' . NL;
        $b .= '  <div class="qr-id-form-col">' . NL;
        $b .= '<form class="qr-id-form-form" method="POST">' . NL;
        $b .= '<fieldset id="f1" class="identity-fs">' . NL;
        $b .= $this->identityFormInput(24,'id_ident','Identifier (email preferred)');
        $b .= "<br>\n";
        $b .= $this->identityFormInput(24,'id_name_first','First Name');
        $b .= "<br>\n";
        $b .= $this->identityFormInput(24,'id_name_last','Last Name');
        $b .= "<br>\n";
        $b .= $this->identityFormInput(24,'id_email','email address');
        $b .= "<br>\n";
        $b .= $this->identityFormInput(24,'id_UCSBNetID','UCSBNetID');
        $b .= "<br>\n";
        $b .= $this->identityFormInput(24,'id_phone','Phone number');
        $b .= '<br>' . NL;
        $b .= $this->identityFormInput(24,'id_dept','Department/Company');
        $b .= '<br>' . NL;
        $b .= $this->identityFormInput(24,'id_extra','Notes');
        $b .= '<br>' . NL;
        $b .= '</fieldset>' . NL;

        $b .= '<fieldset id="f2" class="identity-fs">' . NL;
        $rowCntr++;

        foreach( $this->buildingEntries as $num){
            $b .= $this->identityFormInput(15,'Building'.$num,'Building Name '.$num);
            $b .= $this->identityFormInput(8,'Room'.$num,'Room #'.$num);
            $b .= $this->identityFormInput(12,'Detail'.$num,'Detail '.$num);
            $b .= "<br>\n";
        }

        $b .= '<input class="qr-id-form-submit" type="submit" id="submit" value="&nbsp;Generate Initial Identity Records&nbsp;" name="Generate Initial Identity Records" />'  . NL ;
        $b .= '</fieldset>' . NL;
        $b .= '</form>' . NL;
        $b .= '  </div><!-- qr-form-col -->' . NL;
        $b .= '</div><!-- qr-form-container -->' . NL;
        $b .= "<br>" . NL;
        $b .= "</div><!-- end IdentityFormDiv -->\n";
        $b .= "<p>Additional Information for data entry:</p>";
        $b .= "</div><!-- end IdentityFormContainer -->\n";

        print $b;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function htmlForm(){
        // print_pre($_POST,"POST vars");
        // print_pre($this->regenData,"Internal Regen Data");
        $rowsPerCol = 2;
        $rowCntr = 0;
        if (isset($this->regenData['Identifier'])){
            $GLOBALS['Identfier'] = $this->regenData['Identifier'];
            foreach($this->buildingEntries as $num){
                if (isset($this->regenData['Building'.$num]))  $GLOBALS['Building'.$num] = $this->regenData['Building'.$num];
                if (isset($this->regenData['Room'.$num]))      $GLOBALS['Room'.$num]     = $this->regenData['Room'.$num];

            }
        }
        $b = '';
        $b .= '<div class="qr-form-container">' . NL;
        $b .= '  <div class="qr-form-col">' . NL;
        $b .= '<form method="POST">' . NL;
        $b .= $this->htmlFormInput(24,'Identifier','Identifier (email preferred)');
        $b .= '<br>' . NL;
        $rowCntr++;

        foreach( $this->buildingEntries as $num){
            $b .= $this->htmlFormInput(14,'Building'.$num,'Building Name'.$num);
            $b .= $this->htmlFormInput(8,'Room'.$num,'Room #'.$num);
            $b .= '<br>' . NL;
            $rowCntr++;
            if ( ($rowCntr % $rowsPerCol) == 0){
                $b .= '  </div><!-- qr-form-col -->' . NL;
                $b .= '  <div class="qr-form-col">' . NL;
            }
        }

        $b .= '<input type="submit" id="submit" value="Generate QR" name="Generate QR Code" />'  . NL ;
        $b .= '</form>' . NL;
        $b .= '  </div><!-- qr-form-col -->' . NL;
        $b .= '</div><!-- qr-form-container -->' . NL;
        $b .= "<br>" . NL;

        print $b;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    // This is checking for a form submission to reGenerate some QR codes
    ////////////////////////////////////////////////////////////////////////////
    // not sure if this is actually used at this point...
    function checkPost(){
        $toCheck = array();
        $toCheck[] = 'Identifier';

        foreach($this->buildingEntries as $num){
            $toCheck[] = 'Building' . "$num";
            $toCheck[] = 'Room' . "$num";
        }
        foreach( $toCheck as $fld){
            if (isset($_POST[$fld])){
                $GLOBALS[$fld] = "$_POST[$fld]";
                // print "Got: $fld<br>";
            }
            else {
                $GLOBALS[$fld] = '';
                // print "NOT: $fld<br>";
            }
        }
        // print_pre($_POST,"POST");
        // print_pre($GLOBALS,"GLOBALS");
    }
    ////////////////////////////////////////////////////////////////////////////
    function doQRcodes(){
        $this->cqgen = array();
        foreach($this->buildingEntries as $num){
            $bldg = "Building".$num;
            $room = "Room".$num;
            // Globals are used if we are passing data from a reGeneration call via POST
            if ( isset($GLOBALS[$bldg]) && isset($GLOBALS[$room]) && $GLOBALS[$bldg] != "" && $GLOBALS[$room] != "" ){
                $this->cqgen[] = new traQRcode('cqr'.$num,$GLOBALS['Identifier'],$GLOBALS[$bldg],$GLOBALS[$room]);
            }
        }
        foreach($this->cqgen as $c){
            $c->generateQRs();
            print $c->qrDisplayHTML();
        }
    }
}
?>
