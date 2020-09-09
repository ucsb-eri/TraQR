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
        $b .= "Identifier Order Preference:<ul>
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
        $idKeys = array('id_ident','id_name_first','id_name_last','id_phone','id_email','id_UCSBNetID','id_extra');
        // want to look to see if we have a post for the identity form
        if( ! array_key_exists('id_ident',     $_POST)) return false;
        if( ! array_key_exists('id_name_first',$_POST)) return false;
        if( ! array_key_exists('id_name_last', $_POST)) return false;

        //print_pre($_POST,__METHOD__ . ": POST vars");
        $strKeys = array();  // could possibly just do $strKeys = $idKeys;
        foreach($idKeys as $idk) $strKeys[] = $idk;
        foreach($this->buildingEntries as $num){
            $strKeys[] = 'Building' . $num;
            $strKeys[] = 'Room' . $num;
        }
        //$this->sanitizePost($this->idData,array('id_ident','id_name_first','id_name_last','id_UCSBNetID','id_phone','id_extra'),FILTER_SANITIZE_STRING);
        $this->sanitizePost($this->idData,$strKeys,FILTER_SANITIZE_STRING);
        $this->sanitizePost($this->idData,array('id_email'),FILTER_SANITIZE_EMAIL);
        print_pre($this->idData,__METHOD__ . ': idData');

        // do an upsert into idInfo
        $tpdo = new traQRpdo(getDSN());
        $tpdo->upsert('idInfo','id_ident',$this->idData,$idKeys);
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
        $b .= $this->identityFormInput(24,'id_extra','Notes');
        $b .= '<br>' . NL;
        $b .= '</fieldset>' . NL;

        $b .= '<fieldset id="f2" class="identity-fs">' . NL;
        $rowCntr++;

        foreach( $this->buildingEntries as $num){
            $b .= $this->identityFormInput(15,'Building'.$num,'Building Name'.$num);
            $b .= $this->identityFormInput(8,'Room'.$num,'Room #'.$num);
            $b .= "<br>\n";
        }

        $b .= '<input class="qr-id-form-submit" type="submit" id="submit" value="&nbsp;Generate Initial Identity Records&nbsp;" name="Generate Initial Identity Records" />'  . NL ;
        $b .= '</fieldset>' . NL;
        $b .= '</form>' . NL;
        $b .= '  </div><!-- qr-form-col -->' . NL;
        $b .= '</div><!-- qr-form-container -->' . NL;
        $b .= "<br>" . NL;
        $b .= "</div><!-- end IdentityFormDiv -->\n";
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
////////////////////////////////////////////////////////////////////////////////
// This class is designed to manage a single traQR QR code
////////////////////////////////////////////////////////////////////////////////
class traQRcode {
    public $baseurl = BASEURL;
    public $modes = array('BIDIR');

    function __construct($key,$Identifier,$building,$room){
        $this->key = $key;
        $this->data = array();
        $this->data['qr_uuid'] = genUUID($Identifier,$building,$room);
        $this->data['sd_uuid'] = genUUID($Identifier,$building,$room);
        $this->data['qr_building'] = $building;
        $this->data['qr_room'] = $room;
        $this->data['qr_ident'] = $Identifier;
        $this->qrfiles = array();
        $this->urls = array();
        $this->qrurls = array();
    }
    function urlArgs($sep = "&"){
        $urlElements = array();
        $urlElements['sd_uuid']  = $this->data['sd_uuid'];
        $urlElements['sd_stage'] = $this->data['sd_stage'];

        return http_build_query($urlElements,'','&',PHP_QUERY_RFC3986);
        // basic
        // foreach($this->data as $key => $val){
        //     $elem[] = "${key}=" . "{$this->data[$key]}";
        // }
        // return implode("$sep",$elem);
    }
    function genURL($script,$mode,$sep = "&"){
        return "{$this->baseurl}/{$script}?sd_mode={$mode}&" . $this->urlArgs($sep);
    }
    function generateQRs(){
        // This was originally designed for INGRESS/EGRESS modes, but evolution has
        // moved away from that, leaving loop in for time being though.
        // eventually we should be able to remove Mode from the QR code itself
        // hoping to ultimately have JUST the uuid field.
        foreach( $this->modes as $mode ){
            $ce = new traQRpdo(getDSN());

            // might be nice to check to see if entry exists and just update.
            // not a huge overhead with the replace, but rowids will just continue to climb
            //$count = $ce->fetchValNew("SELECT COUNT(*) FROM qrInfo WHERE qr_ident = ? AND qr_building = ? AND qr_room = ? AND qr_uuid' = ?;",array($this->data['Identifier'],$this->data['Building'],$this->data['Room'],$this->data['UUID']));
            $ce->q("INSERT INTO qrInfo (qr_ident,qr_building,qr_room,qr_uuid) VALUES (?,?,?,?)",array($this->data['qr_ident'],$this->data['qr_building'],$this->data['qr_room'],$this->data['qr_uuid']));
            $ce->q("INSERT INTO idInfo (id_ident) VALUES (?)",array($this->data['qr_ident']));

            $this->qrfiles[$mode] = '../var/qrs/' . $this->key . '-' . $mode . '.png';
            $this->data['sd_stage'] = 'INIT';
            //$this->data['Variant'] = 'DIRECT';

            // Not sure I really need both of these, need to scope that out
            $this->urls[$mode] = $this->genURL("Enter.php",$mode,"&amp;");
            $this->qrurls[$mode] = $this->genURL("Enter.php",$mode);

            // //$this->data['sd_stage'] = 'NOTSET';
            // $this->data['Variant'] = 'VARIANT1';
            // $this->alturls1[$mode] = $this->genURL("Enter.php",$mode);
            //
            //$this->data['sd_stage'] = 'NOTSET';
            // $this->data['Variant'] = 'VARIANT2';
            // $this->alturls2[$mode] = $this->genURL("Enter.php",$mode);
            //
            QRcode::png($this->genURL("Enter.php",$mode),$this->qrfiles[$mode]);
        }
    }
    function qrDisplayHTML(){
        if ( $this->data['qr_ident']    == '' ) return '';
        if ( $this->data['qr_building'] == '' ) return '';
        if ( $this->data['qr_room']     == '' ) return '';
        $netid = preg_replace('/@ucsb.edu/','',$this->data['qr_ident']);
        $b = '';
        $b .= '<div class="qrs-container"><!-- begin container for QR set (both ingress and egress) -->' . NL;
        foreach( $this->modes as $mode ){
            // deal with database submission
            $b .= '  <div class="qr-container"><!-- begin container for single QR (either ingress or egress) -->' . NL;
            $b .= '      <img class="qr-image" src="' . $this->qrfiles[$mode] . '" alt="QR code" />' . NL;
            $b .= '      <div class="qr-info">' . NL;
            $b .= '      <a href="' . $this->urls[$mode] . '">';
            $b .= '      ' . '  ' . $netid . '<br>' . NL;
            $b .= '      ' . $this->data['qr_building'] . '&nbsp;&nbsp;'  . $this->data['qr_room'] .'<br>' . NL;

            $b .= '</a>' . '<br>' . NL;
            $b .= '      </div>' . NL;
            $b .= '  </div><!-- end container for single QR (either ingress or egress) -->' . NL;
        }
        $b .= '</div><!-- end container for QR set -->' . NL;
        return $b;
    }
}
?>
