<?php
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
class traQRpdo extends pdoCore {
    function __construct($dsn){
        parent::__construct($dsn);
        //print "Done with constructor<br>\n";
        $this->qrDbFields = array();
        //$this->qrDbFields = array('Mode','Identifier','Building','Room');
        $this->qrScanFields = array();   // list of keys found during the input check process

        // $this->qrScanFields = $this->qrDbFields;
        // $this->qrScanFields[] = 'Variant';
        // $this->qrScanFields[] = 'sd_stage';
        //array('Mode','Identifier','Building','Room','Variant','sd_stage');
    }
    ////////////////////////////////////////////////////////////////////////////
    function generateEmailAddresses(){
        $list = $this->fetchListNew("SELECT DISTINCT(qr_ident) FROM viewAll WHERE qr_ident != '' AND qr_ident like '%@%ucsb.edu%';");
        print_pre($list,"rowinfo");
        print implode(", ",$list);
    }
    ////////////////////////////////////////////////////////////////////////////
    function initData(){
        $this->data['sd_epoch'] = time();  // not sure we will even need this
        $this->data['sd_ds'] = date('Y-m-d');
        $this->data['sd_its'] = date('Ymd-His');
        $this->data['sd_iepoch'] = date('U');
        $this->data['sd_ets'] = date('Ymd-His');
        $this->data['sd_eepoch'] = date('U');
        $this->data['sd_valid'] = TRUE;
        $this->data['sd_ip'] = $_SERVER['REMOTE_ADDR'];
    }
    ////////////////////////////////////////////////////////////////////////////
    function initDB(){
        if ( TRUE ) {
            $q = "DROP TABLE IF EXISTS";
        }
        //print "traQRpdo::initDB();<br>\n";
        $this->tablename = 'scanData';
        $q = "CREATE TABLE IF NOT EXISTS properties (
        rowid       INTEGER PRIMARY KEY,
        key         TEXT,
        val         TEXT,
        UNIQUE(key) ON CONFLICT REPLACE
        )";

        $q = "CREATE TABLE IF NOT EXISTS scanData (
        sd_id       INTEGER PRIMARY KEY,                         -- alias for rowid
        sd_uuid     TEXT,                                        -- MD5 encoding of Identifier, Building and Room
        sd_mode     TEXT,                                        -- INGRESS or EGRESS - this may be redundant soon
        sd_status   TEXT,                                        -- Various Status strings
        -- Identifier  TEXT,                                        -- Identifier of person that scanned QR code
        -- Building    TEXT,                                        -- Building
        -- Room        TEXT,                                        -- Room # in Building
        sd_ds       TEXT,                                        -- Datestamp YYYY-MM-DD
        -- aCMZ        TEXT,                                        -- Air handling contamination management zone (CMZ)
        -- pCMZ        TEXT,                                        -- Physical contamination management zone (CMZ)
        sd_its      TEXT DEFAULT '',                             -- INGRESS timestamp
        sd_iepoch   TIMESTAMP DEFAULT (strftime('%s','now')),    -- INGRESS epoch (ctime) value
        sd_ets      TEXT DEFAULT '',                             -- EGRESS timestamp
        sd_eepoch   TIMESTAMP DEFAULT (strftime('%s','now')),    -- EGRESS epoch (ctime) value
        sd_stay     INTEGER,                                     -- seconds of how long the users stay was
        sd_hrstay   TEXT,                                        -- human readable form for length of stay HH:MM?  H.DDDD
        sd_flags    TEXT,                                        -- Flags - Not sure how I want to use this yet, extra field for now
        sd_extra    TEXT,                                        -- extra unused field reclaimed from previous schema
        sd_ip       TEXT                                         -- IP submission came from
        );";
        $this->exec($q);

        // This is to record
        $q = "CREATE TABLE IF NOT EXISTS qrInfo (
        qr_id         INTEGER PRIMARY KEY,                       -- alias for rowid
        qr_ident      TEXT,                                      -- identifier
        qr_building   TEXT,                                      -- Building
        qr_room       TEXT,                                      -- Room # in Building
        qr_uuid       TEXT,                                      -- UUID for ident/building/room
        qr_epoch      TIMESTAMP DEFAULT (strftime('%s','now')),  -- date this entry was made
        qr_extra      TEXT,                                      -- extra field for possible use later
        UNIQUE(qr_uuid,qr_ident,qr_building,qr_room) ON CONFLICT IGNORE
        );";
        //print_pre($q,"query: $q");
        $this->exec($q);

        // This is to record
        $q = "CREATE TABLE IF NOT EXISTS idInfo (
        id_id          INTEGER PRIMARY KEY,
        id_ident       TEXT,                                     -- identifier
        id_name_first  TEXT,                                     -- first name
        id_name_last   TEXT,                                     -- last name
        id_phone       TEXT,                                     -- phone number
        id_email       TEXT,                                     -- email address
        id_UCSBNetID   TEXT,                                     -- ucsbnetid (if available)
        id_extra       TEXT,                                     -- extra text field for possible use later
        UNIQUE(id_ident) ON CONFLICT IGNORE
        );";
        //print_pre($q,"query: $q");
        $this->exec($q);

        // This is to record
        $q = "CREATE TABLE IF NOT EXISTS cmzInfo (
        cm_id          INTEGER PRIMARY KEY,
        cm_building    TEXT,                                     -- Building
        cm_room        TEXT,                                     -- Room
        cm_aCMZ        TEXT,                                     -- Air Handling Contamination Management Zone
        cm_pCMZ        TEXT,                                     -- Physical     Contamination Management Zone
        cm_extra       TEXT,                                     -- extra text field for possible use later
        UNIQUE(cm_building,cm_room) ON CONFLICT IGNORE
        );";
        //print_pre($q,"query: $q");
        $this->exec($q);

        $q = "CREATE VIEW IF NOT EXISTS viewAll AS SELECT
              *,
              qrInfo.*,
              idInfo.*,
              qr_building AS Building,
              qr_room AS Room,
              id_name_first AS 'First',
              id_name_last AS Last
        FROM scanData
        LEFT JOIN qrInfo ON sd_uuid = qr_uuid
        LEFT JOIN idInfo ON qr_ident = id_ident
        ;";
        $this->exec($q);

        return true;
    }
    ////////////////////////////////////////////////////////////////////////////////
    function loadGetData(){
        // Would love to modularize these a bit and "register" their handling
        // that may be an option, the filter_inputs and preg_replace could likely be done pretty cleanly
        // but need to figure out a way to handle the selection list entries (Mode, Variant,sd_stage)
        // for now, use this to build some of the lists
        //print_pre($_GET,__METHOD__ . ": GET Vars at beginning");

        $f = 'sd_eepoch';
        if(isset($_GET['sd_eepoch'])){
            // while this looks at eepoch field, we are setting valid field to FALSE if it fails the check
            $diff = ($this->data[$f] - filter_input(INPUT_GET,$f,FILTER_VALIDATE_INT));
            // If set we want to compare to whats in the data from this load (which should be more recent...)
            if ( $diff < 0 || $diff > INVALIDATE_CONFIRM_SECONDS ) $this->data['sd_valid'] = FALSE;
        }

        $f = 'sd_mode';
        if( isset($_GET[$f])){
            if     ( $_GET[$f] == 'EGRESS'  ) $this->data[$f] = 'EGRESS';
            elseif ( $_GET[$f] == 'INGRESS' ) $this->data[$f] = 'INGRESS';
            else                              $this->data[$f] = 'NULL';
        }
        $this->qrScanFields[] = $f;
        $this->qrDbFields[] = $f;

        $f = 'sd_uuid';
        $this->data[$f] = preg_replace('/[^a-zA-Z0-9]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING)));
        $this->qrScanFields[] = $f;
        $this->qrDbFields[] = $f;
        $this->data['qr_uuid'] = $this->data[$f];

        $f = 'sd_stage';
        if( isset($_GET[$f])){
            if     ( $_GET[$f] == 'REVIEW'  )    $this->data[$f] = 'REVIEW';
            elseif ( $_GET[$f] == 'INIT' )       $this->data[$f] = 'INIT';
            elseif ( $_GET[$f] == 'DONE' )       $this->data[$f] = 'DONE';
            elseif ( $_GET[$f] == 'CONFIRMED' )  $this->data[$f] = 'DONE';
            elseif ( $_GET[$f] == 'COMPLETE' )   $this->data[$f] = 'DONE';
            elseif ( $_GET[$f] == 'START' )      $this->data[$f] = 'START';
            else                                 $this->data[$f] = 'NULL';
        }
        else $this->data[$f] = 'NOTSET';
        $this->qrScanFields[] = $f;

        // this is used by ReportDay and is not used in the db or by the Entry points
        $f = 'sd_date';
        $this->data[$f] = preg_replace('/[^0-9-]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING)));

        //print_pre($this->data,__METHOD__ . ": this->data at end");
    }
    ////////////////////////////////////////////////////////////////////////////
    // This routine is called on each pass into the Enter.php script, the GET
    // vars determine what happens from there.
    ////////////////////////////////////////////////////////////////////////////
    function submitDataForProcessing(){
        $this->loadGetData();

        if(! isset($this->data['sd_stage'])){
            print "sd_stage not set in GET<br>\n";
            print_pre($_GET,"GET data");
            return;
        }

        /**
        Need to review the naming and such here to have cleaner logic...
        **/
        switch ($this->data['sd_stage']){
            case 'INIT':
                $this->dataToDbReview();
                break;
            case 'REVIEW':
                $this->dataToDbConfirmed();  // this actually submits data
                break;
            default:
                print "sd_stage value ({$this->data['sd_stage']}) Unknown<br>\n";
                // do nothing,
                break;
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    function uuidIsValid($uuid){
        $founduuid = $this->fetchValNew("SELECT qr_uuid FROM qrInfo WHERE qr_uuid = ?",array($uuid));
        return ($uuid === $founduuid);
    }
    ////////////////////////////////////////////////////////////////////////////
    function dataToDbReview(){
        // print_pre($_GET,__METHOD__ . ": GET vars at beginning");
        // print_pre($this->data,__METHOD__ . ": data vars at beginning");

        $b = '';
        $dbFields = $this->qrDbFields;  // initialize our local dbFields from the object list
        $dbFields[] = 'ds';
        // In this form we will ignore INGRESS/EGRESS, just do a search for existing
        // entries and output what we think is best, confirmation that goes
        //print "Variant2 - Form to choose ingress/egress confirmation buttons - sd_mode (INGRESS/EGRESS ignored)<br>\n";
        $this->data['sd_mode'] = 'SCRIPT_WILL_SELECT';

        // First off we want to validate that this UUID was previously entered
        if (! $this->uuidIsValid($this->data['sd_uuid']) ){
            print $this->scanConfirmationMessages("Invalid UUID",'invalid-entry','INVALID',array($this->data['sd_uuid']));
            return;
        }

        // this should work since we made it past validation.
        // could use this as validation instead though
        $foundqr = $this->getKeyedHash('qr_uuid',"SELECT * FROM qrInfo WHERE qr_uuid = ?;",array($this->data['sd_uuid']));
        //print_pre($foundqr,"searched qrInfo");
        if (isset($foundqr[$this->data['sd_uuid']])){
            $qrInfo = $foundqr[$this->data['sd_uuid']];
        }

        // if (! $this->uuidIsValid($this->data['sd_uuid']) ){
        //     //print "YIKES!  Invalid UUID {$this->data['sd_uuid']}<br>\n";
        //     $b .= "<a class=\"invalid-entry\" href=\"./EntryCompleted.php?info=INVALID\">";
        //     $b .= "INVALID UUID<br>";
        //     $b .= "{$this->data['sd_uuid']}<br>";
        //     $b .= "Click anywhere on this field to EXIT<br>";
        //     $b .= "</a>\n";
        //     print $b;
        //     return;
        // }

        /**
        // see if we have any existing records for Day/Identifier/Building/Room combo...
        // This will need to change as we will need to do a join, most likely via a view
        **/
        $whereData = array(
            'sd_uuid'     => $this->data['sd_uuid'],
            'sd_ds'       => $this->data['sd_ds'],
            // 'Building'   => $this->data['Building'],
            // 'Room'       => $this->data['Room'],
        );

        // get rowid of the FIRST location matching entry, this allows us to update ONLY the first matching entry instead of all
        $wd = $this->generateAndedWhereClause($whereData);
        $userLocationDateMatch = $this->getKeyedHash('sd_id',"SELECT * FROM scanData " . $wd['qstr'] . " ORDER BY sd_iepoch;",$wd['data']);

        /**
        A lot of this logic can be simplified as we are not flagging intermediate entries anymore, we are choosing the ingress/egress automatcially
        **/
        if( count($userLocationDateMatch) == 0){
            // this is a new INGRESS for the day
            $this->data['sd_mode'] = 'INGRESS';
        }
        else{
            // get last sd_id
            foreach($userLocationDateMatch as $uldm){
                $lastqid = $uldm['sd_id'];
            }
            if ($userLocationDateMatch[$lastqid]['sd_mode'] == 'INGRESS'){
                $this->data['sd_mode'] = 'EGRESS';
            }
            elseif($userLocationDateMatch[$lastqid]['sd_mode'] == 'EGRESS'){
                $this->data['sd_mode'] = 'INGRESS';
                $this->data['sd_ets'] = '';
            }
            else {
                // something went wrong
                print "<p>Something has gone wrong, we should not get here</p>\n";
            }
        }

        // Need to build link now....
        // pass in $this->data,keys to use)
        $this->data['sd_stage'] = 'REVIEW';
        $getstr = http_build_query($this->data);


        // Want to add some information to this button, so user can see bldg/room info
        // at the moment, all I have is the uuid, pretty useless, but hey...
        $b .= "<a class=\"confirm-entry\" href=\"./Enter.php?" . $getstr . "\">";
        $b .= "Confirm " . $this->data['sd_mode'] . " Data<br>";
        $b .= $qrInfo['qr_ident'] . "<br>\n";
        $b .= $qrInfo['qr_building'] . "<br>\n";
        $b .= $qrInfo['qr_room'] . "<br>\n";
        $b .= "</a>\n";


        $b .= "<hr>\n";

        $b .= "<a class=\"skip-entry\" href=\"./EntryCompleted.php?info=SKIPPED\">";
        $b .= "Skip " . $this->data['sd_mode'] . " Confirmation";
        $b .= "</a>\n";

        print $b;

        //print_pre($this->data,"Confirmation data");
    }
    ////////////////////////////////////////////////////////////////////////////
    // With the confirm button, this is where the data is actually written out
    ////////////////////////////////////////////////////////////////////////////
    function dataToDbConfirmed(){
        // print_pre($this->data,__METHOD__ . ": this->data at start of method");

        $dbFields = $this->qrDbFields;  // initialize our local dbFields from the object list
        $dbFields[] = 'sd_ds';
        // User has signed off that this data is correct, just do entry
        // $b = '';
        // $b .= "<p>User has confirmed the entry - Just need to update/insert data as appropriate</p>";
        // print $b;

        // First off we want to validate that this UUID was previously entered
        if (! $this->uuidIsValid($this->data['sd_uuid']) ){
            print $this->scanConfirmationMessages("Invalid UUID",'invalid-entry','INVALID',array($this->data['sd_uuid']));
            return;
        }

        if (! $this->data['sd_valid']){
            print $this->scanConfirmationTable("Too much time between scan and confirmation","problematic",'TIMEOUT',$dbFields,$this->data);
            return;
        }

        if ($this->data['sd_mode'] == "INGRESS" ){
            $dbFields[] = 'sd_its';
            $dbFields[] = 'sd_iepoch';
            $dbFields[] = 'sd_status';
            $dbFields[] = 'sd_ip';
            //$dbFields[] = 'sd_uuid';
            $this->data['sd_status'] = 'UNPAIR-IN';

            $qd = $this->insertQueryData($this->tablename, $this->data,$dbFields);
            //print_pre($dbFields,"dbFields");
            //print_pre($qd['data'],"Data for query string: " . $qd['qstr']);
            $this->q($qd['qstr'],$qd['data']);
            print $this->scanConfirmationTable("Successful Ingress Scan+Confirm","success",'DONE',$dbFields,$this->data);
        }
        elseif($this->data['sd_mode'] == "EGRESS" ){
            $dbFields[] = 'sd_ets';
            $dbFields[] = 'sd_eepoch';   // replace the value already in db
            $dbFields[] = 'sd_status';
            $dbFields[] = 'sd_stay';
            //$dbFields[] = 'sd_uuid';
            $this->data['sd_status'] = 'PAIRED';
            $whereData = array(
                'sd_ds'     => $this->data['sd_ds'],
                'sd_uuid'   => $this->data['sd_uuid'],
                'sd_ets'    => '',
            );

            // get rowid of the FIRST location matching entry, this allows us to update ONLY the first matching entry instead of all
            $wd = $this->generateAndedWhereClause($whereData);
            // $singleHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY iepoch LIMIT 1;",$wd['data']);

            $ingressHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY sd_iepoch;",$wd['data']);

            /**
            A lot of this logic can be simplified as we are toggling between the two modes
            Need to scope more heavily and test
            **/
            $updatedEntries = 0;
            foreach($ingressHash as &$ih){
                $whereData['sd_id'] = $ih['sd_id'];
                $this->data['sd_stay'] = $this->data['sd_eepoch'] - $ih['sd_iepoch'];
                $qd = $this->updateQueryData($this->tablename, $this->data,$dbFields,$whereData);
                $pdos = $this->q($qd['qstr'],$qd['data']);
                $affected = $pdos->rowCount();
                $updatedEntries += $affected;
                //print "update: $affected, updated Total: $updatedEntries<br>";

                // So, only the first one gets the PAIRED value, all subsequent
                $this->data['sd_status'] = 'EXTRA-IN';
            }

            //print "Rows Affected == $affected<br>";
            if($updatedEntries == 0){
                // We need to remove the 'stay' entry from dbFields...
                foreach($dbFields as $k => $v){
                    if ($v == 'sd_stay') break;
                }
                unset($dbFields[$k]);

                //print "No matching INGRESS record found, inserting unmatched EGRESS<br>";
                $this->data['sd_status'] = "UNPAIR-OUT";
                $qd = $this->insertQueryData($this->tablename, $this->data,$dbFields);
                //print_pre($dbFields,"dbFields");
                //print_pre($qd['data'],"Data for query string: " . $qd['qstr']);
                $pdos = $this->q($qd['qstr'],$qd['data']);
                $affected = $pdos->rowCount();

                print $this->scanConfirmationTable("Egress Entry before Ingress - Problematic ($affected)","problematic",'EGRESS_BEFORE_INGRESS',$dbFields,$this->data);
            }
            elseif($updatedEntries == 1) {
                //print "SUCCESS!<br>";
                print $this->scanConfirmationTable("Successful Egress Scan+Confirm","success",'DONE',$dbFields,$this->data);
            }
            else {
                // because of fetching the rowid of the first match above coupled with the LIMIT 1
                // this case shouldn't be able to happen now.
                print $this->scanConfirmationTable("SR Matched multiple ($updatedEntries) INGRESS entries for QR scanned data","problematic",'MULTIPLE',$dbFields,$this->data);
                //print "More than 1 matching INGRESS record found indicating some sort of issue<br>";
            }
        }

        //print "<a href=\"./Enter.php?sd_stage=DONE\">Click DONE with data entry</a><br>\n";

        // print_pre($dbFields,"dbFields");
        // print_pre($this->data,"data");
    }
    ////////////////////////////////////////////////////////////////////////////
    /**
    This is deprecated now, feeding this to a separate, non-mobile page
    **/
    function dataEntryComplete(){
        $b = '';
        $b .= "<p>Thanks for using the scanner!</p>
        <p>Please, stay safe:<br>
        &nbsp;&nbsp;Wash Hands thoroughly and frequently<br>
        &nbsp;&nbsp;Avoid touching your face with unwashed hands<br>
        &nbsp;&nbsp;Maintain Social Distance when possible<br>
        &nbsp;&nbsp;Wear a mask when unable to Social Distance<br>
        </p>
        <img src=\"./media/traQR-safety.png\"><br>
        <a href=\"./\">Exit to Main page</a><br>
        ";
        print $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function reportDataByUser(){
        $this->q("UPDATE scanData SET sd_stay=(sd_eepoch - sd_iepoch) WHERE sd_status = 'PAIRED';");
        $this->q("UPDATE scanData SET sd_status='TESTING' WHERE (sd_status = 'PAIRED' AND sd_stay < 60);");
        //$this->q("UPDATE scanData SET hrstay=(stay/3600)||':'||((stay%3600)/60) WHERE (Status = 'PAIRED' AND stay < 60);");
        //$this->q("UPDATE scanData SET Status='TESTING' where (Status = 'TEST' AND stay < 60);");

        $b = '';

        $dss = $this->fetchListNew("SELECT DISTINCT(sd_ds) FROM scanData ORDER BY sd_ds DESC;");
        if ( $dss === FALSE){
            $b .= '<p>Error on query: ' . $q . '</p>' . NL ;
        }
        foreach($dss as $ds){
            $b .= $this->reportDataForDS($ds);
        }

        $b .="<p><strong>Legend</strong></p><br>
        PAIR = Paired INGRESS/EGRESS that match in User/Building/Room<br>
        SW = Still Working ? (Basically an INGRESS with no paired EGRESS)<br>
        EbI  = EGRESS before INGRESS<br>
        2xI = 2 INGRESS records in a row<br>
        MM[which][suffix] = MissMatch record, which corresponds to the first character of the field (U(CSBNetID),B(uilding), R(oom)) followed by a suffix that will match the corresponding row before or after<br>
        ";
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function reportDataByDay(){
        $this->q("UPDATE scanData SET sd_stay=(sd_eepoch - sd_iepoch) WHERE sd_status = 'PAIRED';");
        $this->q("UPDATE scanData SET sd_status='TESTING' WHERE (sd_status = 'PAIRED' AND sd_stay < 60);");
        //$this->q("UPDATE scanData SET hrstay=(stay/3600)||':'||((stay%3600)/60) WHERE (Status = 'PAIRED' AND stay < 60);");
        //$this->q("UPDATE scanData SET Status='TESTING' where (Status = 'TEST' AND stay < 60);");

        $b = '';

        $dss = $this->fetchListNew("SELECT DISTINCT sd_ds FROM scanData ORDER BY sd_ds DESC;");
        if ( $dss === FALSE){
            $b .= '<p>Error on query: ' . $q . '</p>' . NL ;
        }
        foreach($dss as $ds){
            $b .= $this->reportDataByDayForDS($ds);
        }

        $b .="<p><strong>Legend</strong></p><br>
        PAIR = Paired INGRESS/EGRESS that match in User/Building/Room<br>
        SW = Still Working ? (Basically an INGRESS with no paired EGRESS)<br>
        EbI  = EGRESS before INGRESS<br>
        2xI = 2 INGRESS records in a row<br>
        MM[which][suffix] = MissMatch record, which corresponds to the first character of the field (U(CSBNetID),B(uilding), R(oom)) followed by a suffix that will match the corresponding row before or after<br>
        ";
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function reportDataByDayForDS($ds){
        // should maybe do a regex check on it
        if ( $ds == '' ) return '';
        //print "ds: $ds<br>\n";
        list($y,$m,$d) = explode('-',"$ds");
        $hrds = date('l, F j, Y',mktime(0,0,0,$m,$d,$y));
        $enhance = ($ds == date('Y-m-d') ) ? " (Today)" : "" ;
        $b = '';
        $b .= '<div class="an-data-ds-container">' . NL;
        $b .= '<h3>Report by Person for datestamp: ' . $ds . ' - ' . $hrds . $enhance . '</h3>' . NL;

        $dayHash = $this->getKeyedHash('sd_id',"SELECT * FROM viewAll WHERE sd_ds = ? ORDER BY id_ident;",array($ds));
        foreach($dayHash as &$h){
            $h['flags'] = '';
            $h['flags'] = '';
            $h['.td-sd_status']   = '%%VALUE%%';
            $h['.td-Building'] = '%%VALUE%%';
            if ($h['sd_status'] == 'PAIRED' || $h['sd_status'] == 'TESTING') $h['sd_hrstay'] = $this->seconds2hr($h['sd_stay']);
        }
        $flds = array('sd_id','id_ident','Building','Room','sd_mode','sd_status','sd_ds','sd_its','sd_iepoch','sd_ets','sd_eepoch','sd_stay','sd_hrstay','sd_flags');
        $b .= $this->genericDisplayTable($dayHash,$flds);
        $b .= '</div>' . NL;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function seconds2hr($secs){
        return sprintf('%d:%02d:%02d',($secs/3600),(($secs%3600)/60),($secs%60));
    }
    ////////////////////////////////////////////////////////////////////////////
    function reportDataForDS($ds){
        // should maybe do a regex check on it
        if ( $ds == '' ) return '';
        //print "ds: $ds<br>\n";
        list($y,$m,$d) = explode('-',"$ds");
        $hrds = date('l, F j, Y',mktime(0,0,0,$m,$d,$y));
        $enhance = ($ds == date('Y-m-d') ) ? " (Today)" : "" ;
        $b = '';
        $b .= '<div class="an-data-ds-container">' . NL;
        $b .= '<h3>Report by Person for datestamp: ' . $ds . ' - ' . $hrds . $enhance . '</h3>' . NL;
        $ids = $this->fetchListNew("SELECT DISTINCT qr_ident FROM viewAll WHERE sd_ds = ?;",array($ds));
        if ( $ids === FALSE){
            $b .= '<p>Error on query: ' . $q . '</p>' . NL ;
        }
        print_r($ids,"Distinct id");
        foreach($ids as $id){
            $b .= $this->reportDataForDSID($ds,$id);
        }

        $b .= '</div>' . NL;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    // This is a check for a given day and given user
    ////////////////////////////////////////////////////////////////////////////
    function reportDataForDSID($ds,$id){
        $today = date('Y-m-d');
        $b = '';
        $b .= '<div class="an-data-dsid-container">' . NL;
        $b .= '<strong>' . $id . '&nbsp;' . $ds . '</strong>' . NL;

        $hash = $this->getKeyedHash('sd_id',"SELECT * FROM viewAll WHERE (sd_ds = ? and qr_ident = ?) ORDER BY sd_iepoch;",array($ds,$id));

        foreach($hash as &$h){
            $h['flags'] = '';
            $h['flags'] = '';
            $h['.td-sd_tatus']   = '%%VALUE%%';
            $h['.td-Building'] = '%%VALUE%%';
            if ($h['sd_status'] == 'PAIRED' || $h['sd_status'] == 'TESTING') $h['sd_hrstay'] = $this->seconds2hr($h['sd_stay']);
        }

        $flds = array('sd_id','qr_ident','Building','Room','sd_mode','sd_status','sd_ds','sd_its','sd_iepoch','sd_ets','sd_eepoch','sd_stay','sd_hrstay','sd_flags');
        $b .= $this->genericDisplayTable($hash,$flds);

        $b .= '</div>' . NL;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function formPostButton($label,$class,$name,$value){
        $b = '';
        $b .= "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\">
        <button class=\"$class\" type=\"submit\" name=\"$name\" value=\"$value\">$label</button>
        </form>";
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    // table is the tablename to delete a row from
    // rowid is the field that contains the rowid which will be used to pick a specific row
    ////////////////////////////////////////////////////////////////////////////
    function rowEdit($table,$rowkey,$eFields = array()){
        $b = '';
        //print_pre($_POST,"POST Vars");
        if( isset($_POST['EDIT_ROW'])) {
            $rowToEdit = filter_input(INPUT_POST,'EDIT_ROW',FILTER_VALIDATE_INT);
            //print_pre($_POST,"POST Form Submission filetered var is: $rowToDelete");
            // Ideally should check the return and verify the deletion
            if (isset($_POST['CONFIRMED'])){
                $b .= "<div class=\"delete-completed\">Edit of record $rowToEdit completed</div>\n";
                //print_pre($_POST,"POST Form Submission filetered var is: $rowToEdit");
                $vals = array();
                $flds = array();
                foreach($eFields as $f){
                    if(array_key_exists($f,$_POST)){
                        $val = preg_replace('/[^a-zA-Z0-9_@+\. ]/','',trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));
                        //if ( $val != '' ){
                        $flds[] = "$f = ?";
                        $vals[] = $val;
                        //}
                    }
                }
                $vals[] = $rowToEdit;
                $fldsStr = implode(",",$flds);
                $qstr = "UPDATE $table SET $fldsStr WHERE $rowkey = ?;";
                //print_pre($qstr,"Update Query String");
                //print_pre($vals,"Update Query String values");
                $this->q("$qstr",$vals);
                //$this->q("DELETE FROM $table WHERE $rowid = ?;",array($rowToEdit));
                return $b;
            }
            if (isset($_POST['CANCEL'])){
                $b .= "<div class=\"delete-cancelled\">Edit of record $rowToEdit cancelled</div>\n";
                return $b;
            }

            $eh = $this->getKeyedHash($rowkey,"SELECT * FROM $table WHERE $rowkey = ?;",array($rowToEdit));
            //print_pre($eh,"edit hash");
            if ( isset($eh[$rowToEdit])) $h = $eh[$rowToEdit];
            else                         return $b;
            //print_pre($h,"edit single hash");
            //print_pre($eFields,"editable Fields");


            // This is the confirming form for the top of the page
            $b .= "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\">";
            foreach($eFields as $f){
                //print "eField: $f<br>\n";
                 if( array_key_exists($f,$h)) {
                     //print "Found entry in hash for: $f";
                     $b .= "<input type=\"text\" name=\"$f\" value=\"$h[$f]\" size=\"16\" placeholder=\"$f\"></input>\n";
                 }
            }
            $b .= "<br>\n";
            $b .= "<input type=\"hidden\" name=\"EDIT_ROW\" value=\"$rowToEdit\"></input>";
            $b .= "<input class=\"confirm-submit\" type=\"submit\" name=\"CONFIRMED\" value=\"Confirm Edit of row $rowToEdit\"></input>";
            $b .= "</form>";

            $b .= "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\">";
            $b .= "<input type=\"hidden\" name=\"EDIT_ROW\" value=\"$rowToEdit\"></input>";
            $b .= "<input class=\"confirm-cancel\" type=\"submit\" name=\"CANCEL\" value=\"Cancel Edit of row $rowToEdit\"></input>";
            $b .= "</form>";
        }
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    // table is the tablename to delete a row from
    // rowid is the field that contains the rowid which will be used to pick a specific row
    ////////////////////////////////////////////////////////////////////////////
    function rowDeletion($table,$rowid){
        $b = '';
        //print_pre($_POST,"POST Vars");
        if( isset($_POST['DELETE_ROW'])) {
            $rowToDelete = filter_input(INPUT_POST,'DELETE_ROW',FILTER_VALIDATE_INT);
            //print_pre($_POST,"POST Form Submission filetered var is: $rowToDelete");
            // Ideally should check the return and verify the deletion
            if (isset($_POST['CONFIRMED'])){
                $b .= "<div class=\"delete-completed\">Deletion of record $rowToDelete completed</div>\n";
                $this->q("DELETE FROM $table WHERE $rowid = ?;",array($rowToDelete));
                return $b;
            }
            if (isset($_POST['CANCEL'])){
                $b .= "<div class=\"delete-cancelled\">Deletion of record $rowToDelete cancelled</div>\n";
                return $b;
            }

            // This is the confirming form for the top of the page
            $b .= "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\">";
            $b .= "<input type=\"hidden\" name=\"DELETE_ROW\" value=\"$rowToDelete\"></input>";
            $b .= "<input class=\"confirm-submit\" type=\"submit\" name=\"CONFIRMED\" value=\"Confirm Deletion of row $rowToDelete\"></input>";
            $b .= "</form>";
            $b .= "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\">";
            $b .= "<input type=\"hidden\" name=\"DELETE_ROW\" value=\"$rowToDelete\"></input>";
            $b .= "<input class=\"confirm-cancel\" type=\"submit\" name=\"CANCEL\" value=\"Cancel Deletion of row $rowToDelete\"></input>";
            $b .= "</form>";
        }
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function displayIdInfo(){
        $table = 'idInfo';
        $rowkey = 'id_id';
        $b = '';
        $b .= $this->rowDeletion($table,$rowkey);
        $b .= $this->rowEdit($table,$rowkey,array('id_name_first','id_name_last','id_phone','id_email','id_UCSBNetID','id_extra'));
        $hash = $this->getKeyedHash($rowkey,"SELECT * FROM $table;");
        foreach($hash as &$h){
            // building a regen form for each row is gonna be more involved than for the qrInfo.
            // here we need to run a query to get all (up to MAX_BUILDING_ROOM_COMBOS) entries for a given identifier.

            $regenHash = $this->getKeyedHash('qr_uuid',"SELECT * FROM qrInfo WHERE qr_ident = ? LIMIT ?;",array($h['id_ident'],MAX_BUILDING_ROOM_COMBOS));
            //print_pre($regenHash,"Regen Hash for user: ".$h['id_ident']);
            $h['regen'] = "<form action=\"/Admin/GenQR.php\" method=\"post\">
            <input type=\"hidden\" name=\"Identifier\" value=\"{$h['id_ident']}\"></input>\n";
            $num = 1;
            foreach($regenHash as $rh){
                $h['regen'] .= "<input type=\"hidden\" name=\"Building$num\" value=\"{$rh['qr_building']}\"></input>\n";
                $h['regen'] .= "<input type=\"hidden\" name=\"Room$num\" value=\"{$rh['qr_room']}\"></input>\n";
                $num++;
            }
            $h['regen'] .= "<button class=\"regen-button\" type=\"submit\">Regen QR</button></form>";



            $h['delete'] = $this->formPostButton('Delete','delete-button','DELETE_ROW',$h[$rowkey]);
            //$h['regen'] = $this->formPostButton('Regen QR','regen-button','REGEN_QR_ROW',$h[$rowkey]);
            $h['edit'] = $this->formPostButton('Edit','edit-button','EDIT_ROW',$h[$rowkey]);

            $h['locs'] = count($regenHash);

        }
        $flds = array('id_id','id_ident','id_name_first','id_name_last','id_phone','id_email','id_UCSBNetID','id_extra','locs','delete','edit','regen');
        $b .= "<div class=\"generic-display-table\"><!-- begin generic-display-table -->\n";
        $b .= "<h3>Data displayed is primarily from table: $table</h3>\n";
        $b .= $this->genericDisplayTable($hash,$flds);
        $b .= "</div><!-- end generic-display-table -->\n";
        print $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function displayQrInfo(){
        $table = 'qrInfo';
        $rowkey = 'qr_id';
        $b = '';
        $b .= $this->rowDeletion($table,$rowkey);

        $hash = $this->getKeyedHash($rowkey,"SELECT * FROM $table;");
        foreach($hash as &$h){
            $h['delete'] = $this->formPostButton('Delete','delete-button','DELETE_ROW',$h[$rowkey]);
            //$h['delete'] = "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\"><button type=\"submit\" name=\"DELETE_ROW\" value=\"{$h['qr_id']}\">Delete</button></form>";
            $h['regen'] = "<form action=\"/Admin/GenQR.php\" method=\"post\">
            <input type=\"hidden\" name=\"Identifier\" value=\"{$h['qr_ident']}\"></input>
            <input type=\"hidden\" name=\"Building1\" value=\"{$h['qr_building']}\"></input>
            <input type=\"hidden\" name=\"Room1\" value=\"{$h['qr_room']}\"></input>
            <button class=\"regen-button\" type=\"submit\">Regen QR</button></form>";
        }
        $flds = array('qr_id','qr_uuid','qr_ident','qr_building','qr_room','delete','regen');
        $b .= "<div class=\"generic-display-table\"><!-- begin generic-display-table -->\n";
        $b .= "<h3>Data displayed is primarily from table: $table</h3>\n";
        $b .= $this->genericDisplayTable($hash,$flds);
        $b .= "</div><!-- end generic-display-table -->\n";
        print $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    // because this uses md5 with provided info strings, the codes will be
    // reproducable.  I feel like this is perfect for what we are trying to do here.
    // If we want them to be less producable, we could add time string
    // into them.  To get truly unique, we would need to look at some of the crypto
    // generators.  But in either of these last two possibilities, the uuid becomes
    // non-reproducable (unless we save the epoch of the original creation).
    // ////////////////////////////////////////////////////////////////////////////
    // function generateUUIDfromQRInfo(){
    //     $hash = $this->getKeyedHash('qr_id',"SELECT * FROM qrInfo;");
    //     foreach($hash as &$h){
    //         if( TRUE ){
    //             $h['qr_uuid'] = genUUID($h['qr_ident'],$h['qr_building'],$h['qr_room']);
    //             $q = "UPDATE qrInfo SET qr_uuid = ? WHERE qr_id = ?;";
    //             $this->q($q,array($h['qr_uuid'],$h['qr_id']));
    //         }
    //     }
    // }
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    function reportAll(){
        $b = '';

        $flds = array('sd_id','LastName','qr_building','qr_room','sd_mode','sd_status','sd_ds','sd_its','sd_iepoch','sd_ets','sd_eepoch','sd_ip','sd_flags');

        // Fields array here needs to align with the db fields/view
        $flds = array('sd_id','sd_uuid','qr_ident','First','Last','Building','Room','sd_mode','sd_status','sd_ds','sd_its','sd_iepoch','sd_ets','sd_eepoch','sd_ip','sd_flags');

        // $this->q("CREATE TEMP VIEW IF NOT EXISTS viewAll AS SELECT
        //       *,
        //       qrInfo.*,
        //       idInfo.*,
        //       qr_building AS Building,
        //       qr_room AS Room,
        //       id_name_first AS 'First',
        //       id_name_last AS Last
        //     FROM scanData
        //     LEFT JOIN qrInfo ON sd_uuid = qr_uuid
        //     LEFT JOIN idInfo ON qr_ident = id_ident
        //     ORDER BY sd_iepoch DESC;
        // ");
        //$hash = $this->getKeyedHash('sd_id',"SELECT *,qr_building as Building,qr_room as Room,id_name_first AS 'First',id_name_last AS Last FROM scanData LEFT JOIN qrInfo ON sd_uuid = qr_uuid LEFT JOIN idInfo ON qr_ident = id_ident ORDER BY sd_iepoch DESC;");
        $hash = $this->getKeyedHash('sd_id',"SELECT * FROM viewAll ORDER BY sd_iepoch DESC;");
        foreach($hash as &$h){
            $h['flags'] = '';
            $h['.td-sd_status'] = '%%VALUE%%';
            $h['.td-Building'] = '%%VALUE%%';
        }
        $b .= $this->genericDisplayTable($hash,$flds);

        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function mergeRecords($from,$into){
        $b = '';
        $b .= "
        <p>Attempting merge of row $from into $into (The rows in question are hardwired at the moment for dev/testing)</p>
        <p>Checking to verify that both rows exist and that they meet certain criteria:</p>
        <ul>
        <li>Both rows should have the same <em>Identifier</em></li>
        <li>Both rows should have the same <em>datestamp (ds)</em></li>
        <li>Both rows should have the same <em>Building</em></li>
        <li>Both rows should have the same <em>Room</em></li>
        <li>The FROM row should have <em>Status</em>: UNPAIR-OUT AND <em>ingress timestamp (its)</em> should be empty</li>
        <li>The TO row should have <em>Status</em>: UNPAIR-IN AND <em>egress timestamp (ets)</em> should be empty</li>
        </ul>
        <p>Once the above criteria has been checked, the following will be done:</p>
        <ul>
        <li>FROM fields: eepoch and ets will be used to update the corresponding fields in the to record</li>
        <li><em>Status</em> field of TO record will updated to PAIRED</li>
        <li>FROM record will be deleted</li>
        </ul>

        ";
        $fromHash = $this->getKeyedHash('sd_id',"SELECT * FROM scanData WHERE sd_id = ?;",array($from));
        $intoHash = $this->getKeyedHash('sd_id',"SELECT * FROM scanData WHERE sd_id = ?;",array($into));

        $criteriaFailed = FALSE;
        if (count($fromHash) == 1) {
            $b .= "<span class=\"success\">FROM record $from exists!</span><br>";
            $fh = $fromHash[$from];
        }
        else {
            $b .= "<span class=\"failure\">FROM record $from does NOT exist or had multiple matches (shouldnt happen)</span><br>";
            $criteriaFailed = TRUE;
        }

        if (count($intoHash) == 1) {
            $b .= "<span class=\"success\">FROM record $into exists!</span><br>";
            $ih = $intoHash[$into];

        }
        else {
            $b .= "<span class=\"failure\">INTO record $into does NOT exist or had multiple matches (shouldnt happen)</span><br>";
            $criteriaFailed = TRUE;
        }

        // We need to start referencing the collected data if available
        // so if one or both are missing we have to skip this.
        if (! $criteriaFailed ){
            $b .= "Comparing FROM and INTO fields: ";
            foreach( array('Identifier','ds','Building','Room') as $f){
                if ( $fh[$f] != $ih[$f]){
                    $b .= "<span class=\"failure\">$f</span>,&nbsp;";
                    $criteriaFailed = TRUE;
                }
                else {
                    $b .= "<span class=\"success\">$f</span>,&nbsp;";
                }
            }

            if ( $fh['Status'] == 'UNPAIR-OUT' ) $b .= "<span class=\"success\">FROM: Status == UNPAIR-OUT</span>,&nbsp;";
            else                                 $b .= "<span class=\"failure\">FROM: Status != UNPAIR-OUT</span>,&nbsp;";
            if ( $ih['Status'] == 'UNPAIR-IN' )  $b .= "<span class=\"success\">INTO: Status == UNPAIR-IN</span>";
            else                                 $b .= "<span class=\"failure\">INTO: Status != UNPAIR-IN</span>";
        }

        if( $criteriaFailed ){
            $b .= "<p>Looks like some matching criteria failed above ... Sorry<br>";
        }
        else {
            $b .= "<p>Looks like all matching criteria succeeded above ... We can proceed<br>";
            $q = "UPDATE scanData SET ets='{$fh['ets']}',eepoch={$fh['eepoch']},Status='PAIRED' WHERE sd_id = $into;<br>";
            $b .= "query: $q";
            $q = "DELETE from scanData WHERE sd_id = $from;<br>";
            $b .= "query: $q";
            //$q = "UPDATE scanData SET ets='{$fh['ets']}',eepoch={$fh['eepoch']} WHERE sd_id = $into;";
        }


        return $b;
    }
    function clearTestEntries(){
        $testEntries = $this->getKeyedHash('sd_id',"SELECT * FROM viewAll WHERE qr_ident like '%@test.ucsb.edu';");
        $testEntriesCount = count($testEntries);
        foreach($testEntries as $te){
            $this->q("DELETE FROM scanData WHERE sd_id = ?;",array($te['sd_id']));
            print "Deleted rowid {$te['sd_id']}<br>\n";
        }
    }
    function scanConfirmationTable($confirmationMessage,$confirmationClass,$infoCode = 'SUCCESS',$dbFields = array(),$data = array()){
        $b = '';
        $b .= "<a class=\"entry-confirmation-link\" href=\"EntryCompleted.php?info=DONE\">";
        $b .= "<div class=\"entry-confirmation $confirmationClass\">\n";
        $b .= "<strong class=\"confirmation\">$confirmationMessage:</strong><br>\n";
        $b .= "<table class=\"confirmation\">\n";
        foreach( $dbFields as $f){
            if( $f == 'ip' ) continue;
            if( isset($data[$f]))   $b .= "<tr><td ><strong>" . $f . ":</strong></td><td><em>" . $data[$f] . "</em></td></tr>\n";
        }
        $b .= "</table>\n";
        $b .= "<p class=\"confirmation-finish\"><strong>Click Anywhere in Block To Finish</strong></p>\n";
        $b .= "</div>\n";
        $b .= "</a>\n";
        //print_pre($data,"scanConfirmationData");
        return $b;
    }
    function scanConfirmationMessages($confirmationMessage,$confirmationClass,$infoCode = 'SUCCESS',$msgLines = array()){
        $b = '';
        $b .= "<a class=\"entry-confirmation-link\" href=\"EntryCompleted.php?info=$infoCode\">";
        $b .= "<div class=\"entry-confirmation $confirmationClass\">\n";
        $b .= "<strong class=\"confirmation\">$confirmationMessage</strong><br>\n";
        foreach( $msgLines as $k => $v){
            $b .= ( is_numeric($k)) ? "<strong>" . $v . "</strong>" : "<strong>" . $k . ":</strong><em>" . $v . "</em>";
            $b .= "<br>\n";
        }
        $b .= "<p class=\"confirmation-finish\"><strong>Click Anywhere in Block To Finish</strong></p>\n";
        $b .= "</div>\n";
        $b .= "</a>\n";
        //print_pre($data,"scanConfirmationTableData");
        return $b;

    }

    // $b .= "<a class=\"invalid-entry\" href=\"./EntryCompleted.php?info=INVALID\">";
    // $b .= "INVALID UUID<br>";
    // $b .= "{$this->data['sd_uuid']}<br>";
    // $b .= "Click anywhere on this field to EXIT<br>";
    // $b .= "</a>\n";

}

?>
