<?php
require_once(__DIR__ . '/../ext/phpqrcode/qrlib.php');
require_once(__DIR__ . '/utils.inc.php');

class traQRcodeNew {
    public $baseurl = BASEURL;
    public $modes = array('BIDIR');

    ////////////////////////////////////////////////////////////////////////////
    // key is basically just an index attached to the image so we can cache multiple created QR images for display
    // uuid is the qr_uuid valid to lookup from the db
    function __construct($key,$uuid){
        $this->key = $key;
        $this->error = False;
        $this->db = new traQRpdo(getDSN());

        // get information about the uuid in question
        $h = $this->db->getKeyedHash('qr_uuid',"SELECT * FROM qrInfo WHERE qr_uuid = ?;",array($uuid));
        if(count($h) == 0){
            $this->error = True;
        }
        elseif (isset($h[$uuid])){
            $this->data = $h[$uuid];
        }
        else {
            $this->error = True;
        }

        //print_pre($h,"qrInfo db fetch");
        //$this->data = array();
        $this->data['sd_stage'] = 'INIT';
        $this->generateQRs();

        // $this->data['qr_uuid'] = genUUID($Identifier,$building,$room);
        // $this->data['sd_uuid'] = genUUID($Identifier,$building,$room);
        // $this->data['qr_building'] = $building;
        // $this->data['qr_room'] = $room;
        // $this->data['qr_ident'] = $Identifier;
        // $this->qrfiles = array();
        // $this->urls = array();
        // $this->qrurls = array();
    }
    ////////////////////////////////////////////////////////////////////////////
    function urlArgs($sep = "&"){
        $urlElements = array();
        $urlElements['sd_uuid']  = $this->data['qr_uuid'];
        $urlElements['sd_stage'] = $this->data['sd_stage'];

        return http_build_query($urlElements,'','&',PHP_QUERY_RFC3986);
    }
    ////////////////////////////////////////////////////////////////////////////
    function genURL($script,$mode,$sep = "&"){
        return "{$this->baseurl}/{$script}?sd_mode={$mode}&" . $this->urlArgs($sep);
    }
    ////////////////////////////////////////////////////////////////////////////
    function generateQRs(){
        // This was originally designed for INGRESS/EGRESS modes, but evolution has
        // moved away from that, leaving loop in for time being though.
        // eventually we should be able to remove Mode from the QR code itself
        // hoping to ultimately have JUST the uuid field.
        foreach( $this->modes as $mode ){
            $this->qrfiles[$mode] = '../var/qrs/' . $this->key . '-' . $mode . '.png';
            //$this->data['sd_stage'] = 'INIT';

            // Not sure I really need both of these, need to scope that out
            // $this->urls[$mode] = $this->genURL("Enter.php",$mode,"&amp;");
            $this->qrurls[$mode] = $this->genURL("Enter.php",$mode);
            QRcode::png($this->genURL("Enter.php",$mode),$this->qrfiles[$mode]);
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    function html(){
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
            $b .= '      <a href="' . $this->qrurls[$mode] . '">';
            $b .= '      ' . '  ' . $netid . '<br>' . NL;
            $b .= '      ' . $this->data['qr_building'] . '&nbsp;&nbsp;'  . $this->data['qr_room'] .'<br>' . NL;
            $b .= '      ' . $this->data['qr_detail'] .'<br>' . NL;

            $b .= '</a>' . '<br>' . NL;
            $b .= '      </div>' . NL;
            $b .= '  </div><!-- end container for single QR (either ingress or egress) -->' . NL;
        }
        $b .= '</div><!-- end container for QR set -->' . NL;
        return $b;
    }
}
////////////////////////////////////////////////////////////////////////////////
// This class is designed to manage a single traQR QR code
////////////////////////////////////////////////////////////////////////////////
class traQRcode {
    public $baseurl = BASEURL;
    public $modes = array('BIDIR');

    ////////////////////////////////////////////////////////////////////////////
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
    ////////////////////////////////////////////////////////////////////////////
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
    ////////////////////////////////////////////////////////////////////////////
    function genURL($script,$mode,$sep = "&"){
        return "{$this->baseurl}/{$script}?sd_mode={$mode}&" . $this->urlArgs($sep);
    }
    ////////////////////////////////////////////////////////////////////////////
    // Want to deprecate this out...  Don't want to actually create qr_uuid here
    // OR do insert into db here....
    ////////////////////////////////////////////////////////////////////////////
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
    ////////////////////////////////////////////////////////////////////////////
    function qrImageCreate(){
        foreach( $this->modes as $mode ){
            $this->qrfiles[$mode] = '../var/qrs/' . $this->key . '-' . $mode . '.png';
            $this->data['sd_stage'] = 'INIT';  // pretty sure this is NOT needed

            // Not sure I really need both of these, need to scope that out
            $this->urls[$mode] = $this->genURL("Enter.php",$mode,"&amp;");
            $this->qrurls[$mode] = $this->genURL("Enter.php",$mode);

            QRcode::png($this->genURL("Enter.php",$mode),$this->qrfiles[$mode]);
        }
    }
    ////////////////////////////////////////////////////////////////////////////
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
