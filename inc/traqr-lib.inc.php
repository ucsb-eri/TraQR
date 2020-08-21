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
