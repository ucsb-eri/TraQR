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

        // for now, every time this script runs lets attempt to create an auto-daily backup.
        // overhead on the attempt is pretty low until I figure out a better way to regulate that.
        // will likely end up implementing backups via local crons
        //$this->dbBackup(true);
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
        $this->data['sd_iip'] = $_SERVER['REMOTE_ADDR'];
        $this->data['sd_eip'] = $_SERVER['REMOTE_ADDR'];
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
        sd_id        INTEGER PRIMARY KEY,                         -- alias for rowid
        sd_uuid      TEXT,                                        -- MD5 encoding of Identifier, Building and Room
        sd_mode      TEXT,                                        -- INGRESS or EGRESS - this may be redundant soon
        sd_status    TEXT,                                        -- Various Status strings
        sd_ds        TEXT,                                        -- Datestamp YYYY-MM-DD
        sd_its       TEXT DEFAULT '',                             -- INGRESS timestamp
        sd_iepoch    TIMESTAMP DEFAULT (strftime('%s','now')),    -- INGRESS epoch (ctime) value
        sd_ets       TEXT DEFAULT '',                             -- EGRESS timestamp
        sd_eepoch    TIMESTAMP DEFAULT (strftime('%s','now')),    -- EGRESS epoch (ctime) value
        sd_stay      INTEGER,                                     -- seconds of how long the users stay was
        sd_hrstay    TEXT,                                        -- human readable form for length of stay HH:MM?  H.DDDD
        sd_flags     TEXT,                                        -- Flags - Not sure how I want to use this yet, extra field for now
        sd_extra     TEXT,                                        -- extra unused field reclaimed from previous schema
        sd_iip       TEXT,                                        -- IP address on INGRESS
        sd_eip       TEXT                                         -- IP address on EGRESS
        );";
        $this->exec($q);

        // This is to record
        $q = "CREATE TABLE IF NOT EXISTS qrInfo (
        qr_id         INTEGER PRIMARY KEY,                       -- alias for rowid
        qr_ident      TEXT,                                      -- identifier
        qr_building   TEXT,                                      -- Building
        qr_room       TEXT,                                      -- Room # in Building (or cluster name)
        qr_uuid       TEXT,                                      -- UUID for ident/building/room
        qr_epoch      TIMESTAMP DEFAULT (strftime('%s','now')),  -- date this entry was made
        qr_detail     TEXT,                                      -- room or cluster detail
        qr_extra      TEXT,                                      -- extra field for possible use later
        UNIQUE(qr_uuid) ON CONFLICT IGNORE
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
        id_dept        TEXT,                                     -- department
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

        $q = "CREATE TABLE IF NOT EXISTS auth (
        au_id          INTEGER PRIMARY KEY,
        au_user        TEXT,                                     -- user
        au_hash        TEXT,                                     -- encoded pass hash (from password_hash)
        au_role        TEXT,                                     -- role
        au_extra       TEXT,                                     -- extra text field for possible use later
        UNIQUE(au_user) ON CONFLICT IGNORE
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
        /**
Used the following to pull qrInfo and idInfo over into newer system.
echo "SELECT qi_uuid,qi_ident,qi_building,qi_room FROM qrInfo WHERE qi_ident like '%@%ucsb.edu';" | sqlite3 covidqr.sqlite3 | sed -e "s/|/','/g" -e "s/^/('/" -e "s/$/');/" -e 's/^/INSERT INTO qrInfo (qr_uuid,qr_ident,qr_building,qr_room) VALUES /'
echo "SELECT id_ident,id_name_first,id_name_last,id_phone,id_email,id_UCSBNetID,id_extra FROM idInfo WHERE id_ident like '%@%ucsb.edu';" | sqlite3 covidqr.sqlite3 | sed -e "s/|/','/g" -e "s/^/('/" -e "s/$/');/" -e 's/^/INSERT INTO idInfo (id_ident,id_name_first,id_name_last,id_phone,id_email,id_UCSBNetID,id_extra) VALUES /'
        **/
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
    // var st_stage determines what happens from there.
    ////////////////////////////////////////////////////////////////////////////
    function submitDataForProcessing(){
        $this->loadGetData();

        if(! isset($this->data['sd_stage'])){
            print "sd_stage not set in GET<br>\n";
            print_pre($_GET,"GET data");
            return;
        }

        /**
        Should review logic/naming in this area to improve readability
        **/
        switch ($this->data['sd_stage']){
            case 'INIT':
                // this is coming from the initial scan or link click to be reviewed
                $this->dataToDbReview();
                break;
            case 'REVIEW':
                // this happens AFTER the step above has been reviewed and confirmed
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
        // could use this to validate instead if we wanted, but the modularity of the other (which is reused) has value
        $foundqr = $this->getKeyedHash('qr_uuid',"SELECT * FROM qrInfo WHERE qr_uuid = ?;",array($this->data['sd_uuid']));
        //print_pre($foundqr,"searched qrInfo");
        if (isset($foundqr[$this->data['sd_uuid']])){
            // this info is used in the confirm button below
            $qrInfo = $foundqr[$this->data['sd_uuid']];
        }

        // need to build a where clause to match datestamp and the UUID which is Identifier, Building and Room encoded as one
        $whereData = array(
            'sd_uuid'     => $this->data['sd_uuid'],
            'sd_ds'       => $this->data['sd_ds'],
        );

        // get rowid of the FIRST location matching entry, this allows us to update ONLY the first matching entry instead of all
        $wd = $this->generateAndedWhereClause($whereData);
        $userLocationDateMatch = $this->getKeyedHash('sd_id',"SELECT * FROM scanData " . $wd['qstr'] . " ORDER BY sd_iepoch;",$wd['data']);

        // This logic controls whether this scan becomes an INGRESS or an EGRESS
        // which is then attached to the confirmation form
        if( count($userLocationDateMatch) == 0){
            $this->data['sd_mode'] = 'INGRESS';
        }
        else{
            // so there is at least one matching record, the ORDER BY will make the last one the most recent
            // determine the last matching set
            foreach($userLocationDateMatch as $uldm) $lastqid = $uldm['sd_id'];
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

        // Build our links now pass in $this->data,keys to use)
        $this->data['sd_stage'] = 'REVIEW';
        $getstr = http_build_query($this->data);
        $url = "./Enter.php?" . $getstr ;

        // Want to add some information to this button, so user can see bldg/room info
        // at the moment, all I have is the uuid, pretty useless, but hey...

        $b .=  $this->scanConfirm($url,$qrInfo['qr_ident'],$qrInfo['qr_building'],$qrInfo['qr_room']);

        $b .= "<hr>\n";
        $b .=  $this->scanCancel();

        print $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function scanConfirm($url,$ident,$building,$room){
        $b = '';
        $b .= "<a class=\"big-button confirm-entry\" href=\"$url\">";
        $b .= "Confirm " . $this->data['sd_mode'] . " for<br>";
        $b .=  "$ident<br>\n";
        $b .=  "$building $room<br>\n";
        $b .= "</a>\n";
        return $b;
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
            // Need to provide a link to restart the process instead of just bailing
//            print $this->scanConfirmationTable("Too much time between scan and confirmation","problematic",'TIMEOUT',$dbFields,$this->data);
            print $this->scanProcessRestart();
            print $this->scanCancel();
            return;
        }

        if ($this->data['sd_mode'] == "INGRESS" ){
            $dbFields[] = 'sd_its';
            $dbFields[] = 'sd_iepoch';
            $dbFields[] = 'sd_status';
            $dbFields[] = 'sd_iip';
            unset($this->data['sd_eip']);
            $this->data['sd_status'] = 'UNPAIR-IN';

            $qd = $this->insertQueryData($this->tablename, $this->data,$dbFields);
            //print_pre($dbFields,"dbFields");
            //print_pre($qd['data'],"Data for query string: " . $qd['qstr']);
            $this->q($qd['qstr'],$qd['data']);
            print $this->scanConfirmationTable("Successful Ingress Scan+Confirm","success",'DONE',$dbFields,$this->data);
        }
        elseif($this->data['sd_mode'] == "EGRESS" ){
            // EGRESS is more complicated than INGRESS since we need to match a
            // previous record
            $dbFields[] = 'sd_ets';
            $dbFields[] = 'sd_eepoch';   // replace the value already in db
            $dbFields[] = 'sd_status';
            $dbFields[] = 'sd_stay';
            $dbFields[] = 'sd_eip';
            unset($this->data['sd_iip']);
            $whereData = array(
                'sd_ds'     => $this->data['sd_ds'],
                'sd_uuid'   => $this->data['sd_uuid'],
                'sd_ets'    => '',
            );

            // Generate where clause to collect INGRESS entries that match in Date, Identifier, Building and Room
            $wd = $this->generateAndedWhereClause($whereData);
            $ingressHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY sd_iepoch;",$wd['data']);
            // previously we selected only thet LAST INGRESS to work with, simpler, but leaves the others in limbo
            // $singleHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY iepoch LIMIT 1;",$wd['data']);


            /**
            There may be opportunities to simplify this logic since the system now determines the mode
            Need to scope more thoroughly and test
            **/
            $this->data['sd_status'] = 'PAIRED';
            $updatedEntries = 0;
            foreach($ingressHash as &$ih){
                $whereData['sd_id'] = $ih['sd_id'];
                $this->data['sd_stay'] = $this->data['sd_eepoch'] - $ih['sd_iepoch'];
                $qd = $this->updateQueryData($this->tablename, $this->data,$dbFields,$whereData);
                $pdos = $this->q($qd['qstr'],$qd['data']);
                $affected = $pdos->rowCount();
                $updatedEntries += $affected;

                // Once we have updated the FIRST entry to match, we switch the status to EXTRA-IN
                $this->data['sd_status'] = 'EXTRA-IN';
            }

            //print "Rows Affected == $affected<br>";
            if($updatedEntries == 0){
                // We need to remove the 'stay' entry from dbFields...
                if(($key = array_search('sd_stay',$dbFields)) !== FALSE ){
                    unset($dbFields[$key]);
                }
                // foreach($dbFields as $k => $v){
                //     if ($v == 'sd_stay') break;
                // }
                // unset($dbFields[$k]);

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
                print $this->scanConfirmationTable("Matched multiple ($updatedEntries) INGRESS entries for QR scanned data","problematic",'MULTIPLE',$dbFields,$this->data);
                //print "More than 1 matching INGRESS record found indicating some sort of issue<br>";
            }
        }
        // print_pre($dbFields,"dbFields");
        // print_pre($this->data,"data");
    }
    ////////////////////////////////////////////////////////////////////////////
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
        $table = 'scanData';
        $rowkey = 'sd_id';
        // should maybe do a regex check on it
        if ( $ds == '' ) return '';
        //print "ds: $ds<br>\n";
        list($y,$m,$d) = explode('-',"$ds");
        $hrds = date('l, F j, Y',mktime(0,0,0,$m,$d,$y));
        $enhance = ($ds == date('Y-m-d') ) ? " (Today)" : "" ;
        $b = '';
        $b .= '<div class="an-data-ds-container">' . NL;
        $b .= '<h3>Report by Person for datestamp: ' . $ds . ' - ' . $hrds . $enhance . '</h3>' . NL;

        $flds = array('sd_id','id_ident','Building','Room','sd_mode','sd_status','sd_ds','sd_its','sd_iepoch','sd_ets','sd_eepoch','sd_stay','sd_hrstay','sd_flags');

        $b .= $this->columnSortBy($table);
        $b .= $this->rowDeletion($table,$rowkey);   // table needs to be scanData for this...
        $orderField = $this->orderField($table,$flds,'sd_iepoch');
        $orderBy = $this->orderByClause($table,$flds,'sd_iepoch','DESC');

        $dayHash = $this->getKeyedHash('sd_id',"SELECT * FROM viewAll WHERE sd_ds = ? $orderBy;",array($ds));
        foreach($dayHash as &$h){
            $h['flags'] = '';
            $h['flags'] = '';
            $h['.td-sd_status']   = '%%VALUE%%';
            $h['.td-Building'] = '%%VALUE%%';
            if ($h['sd_status'] == 'PAIRED' || $h['sd_status'] == 'TESTING') $h['sd_hrstay'] = seconds2hr($h['sd_stay']);
        }
        $b .= $this->genericDisplayTable($dayHash,$flds,$orderField);
        $b .= '</div>' . NL;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    function reportAll(){
        $table = 'viewAll';  // the viewAll might work here, have to play with it
        $table = 'scanData';
        $rowkey = 'sd_id';
        // Fields array here needs to align with the db fields/view
        $flds = array('sd_id','sd_uuid','qr_ident','First','Last','Building','Room','sd_mode','sd_status','sd_ds','sd_its','sd_iepoch','sd_ets','sd_eepoch','sd_iip','sd_eip','sd_flags');

        $b = '';
        $b .= $this->columnSortBy($table);
        $b .= $this->rowDeletion($table,$rowkey);   // table needs to be scanData for this...
        $orderField = $this->orderField($table,$flds,'sd_iepoch');
        $orderBy = $this->orderByClause($table,$flds,'sd_iepoch','DESC');

        //$hash = $this->getKeyedHash('sd_id',"SELECT *,qr_building as Building,qr_room as Room,id_name_first AS 'First',id_name_last AS Last FROM scanData LEFT JOIN qrInfo ON sd_uuid = qr_uuid LEFT JOIN idInfo ON qr_ident = id_ident ORDER BY sd_iepoch DESC;");
        $hash = $this->getKeyedHash('sd_id',"SELECT * FROM viewAll $orderBy;");
        $linecntr = 0;
        foreach($hash as &$h){
            $linecntr++;
            $h['#'] = $linecntr;
            $h['flags'] = '';
            $h['.td-sd_status'] = '%%VALUE%%';
            $h['.td-Building'] = '%%VALUE%%';
            $h['.td-#'] = 'rowcnt';
        }

        if (authorized('TRAQR','root')){
            array_push($flds,'delete');
            foreach($hash as &$h){
                $h['delete'] = $this->formPostButton('Delete','delete-button','DELETE_ROW',$h[$rowkey]);
            }
        }
        array_unshift($flds,'#');
        array_push($flds,'#');
        $b .= $this->genericDisplayTable($hash,$flds,$orderField);

        return $b;
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
            if ($h['sd_status'] == 'PAIRED' || $h['sd_status'] == 'TESTING') $h['sd_hrstay'] = seconds2hr($h['sd_stay']);
        }

        $flds = array('sd_id','qr_ident','Building','Room','sd_mode','sd_status','sd_ds','sd_its','sd_iepoch','sd_ets','sd_eepoch','sd_stay','sd_hrstay' /* ,'sd_flags' */);
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
    function rowEdit($table,$rowkey,$eFields = array(),$displayOnlyFields = array()){
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
            foreach($displayOnlyFields as $f){
                $b .= "&nbsp;" . $h[$f] . "&nbsp;";
            }
            foreach($eFields as $f){
                //print "eField: $f<br>\n";
                 if( array_key_exists($f,$h)) {
                     //print "Found entry in hash for: $f";
                     $b .= "<input class=\"edit-row\" type=\"text\" name=\"$f\" value=\"$h[$f]\" size=\"16\" placeholder=\"$f\"></input>\n";
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
    function formSelect($form,$name,$values,$currVal){
        $b = '';
        $b .= "<select name=\"$name\" id=\"$name\" form=\"$form\">\n";
        foreach($values as $v){
            $selected = ($v == $currVal) ? "selected" : "";
            $b .= "<option value=\"$v\" $selected>$v</option>\n";
        }
        $b .= "</select>\n";
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function newUser(){
        global $authFlags;
        $b = '';
        // process form info
        if( array_key_exists('au_user',$_POST) && array_key_exists('password',$_POST) && array_key_exists('au_role',$_POST)){
            // get md5 value of password string
            $f = 'au_role';
            $filtered = preg_replace('/[^a-z]/','',trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));
            $this->data[$f] = ( array_key_exists($filtered,$authFlags)) ? $filtered : 'none';

            $f = 'au_user';
            $this->data[$f] = preg_replace('/[^a-zA-Z0-9]/','',trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));

            $f = 'password';
            $uepw = str_replace('+','.',trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));
            // print "POSTVAL: {$_POST[$f]}, UEPW: $uepw<br>\n";

            $this->data['au_hash'] = password_hash($uepw,PASSWORD_DEFAULT);
            // print "crypt salt params: $param<br>\n";
            // print "crypt value:       {$this->data['au_hash']}<br>\n";

            //$this->data['au_md5'] = md5(trim(filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING)));

            // need to insert data
            $b .= "Inserting new user<br>";
            $this->upsert('auth','au_user',$this->data,array('au_user','au_hash','au_role'));
            //$this->q("INSERT OR REPLACE INTO auth (au_user,au_hash,au_role) VALUES (?,?,?);",array($this->data['au_user'],$this->data['au_hash'],$this->data['au_role']));
        }

        // print_pre($_POST,__METHOD__ . ": POST Vars");
        // print_pre($this->data,__METHOD__ . ": data Vars");
        $currVals = array();
        foreach(array('au_role','au_user') as $f){
            $currVals[$f] = ( isset($this->data[$f]) ) ? $this->data[$f] : '';
        }

        // create form
        $b .= "<form name=\"New_User\" id=\"New_User\" method=\"post\">\n";
        $b .= "<input type=\"text\" name=\"au_user\" placeholder=\"User\" value=\"{$currVals['au_user']}\" size=\"24\">\n";
        $b .= "<input type=\"password\" name=\"password\" placeholder=\"Password\" value=\"\" size=\"24\">\n";
        $b .= $this->formSelect('New_User','au_role',array_keys($authFlags),$currVals['au_role']);
        $b .= "<input type=\"submit\" name=\"NEW_USER\"  value=\"Add User\">\n";
        $b .= "(can also be used to update existing user already in table below)\n";
        $b .= "</form>\n";
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function authManagement(){
        $table = 'auth';
        $rowkey = 'au_id';
        $b = '';
        if( authorized('TRAQR','root')){
            $b .= $this->newUser();
            $b .= $this->rowDeletion($table,$rowkey);
            $b .= $this->rowEdit($table,$rowkey,array('au_role'));
        }
        $hash = $this->getKeyedHash($rowkey,"SELECT * FROM $table;");
        $flds = array('au_id','au_user','au_hash','au_role');
        if( authorized('TRAQR','root')) {
            array_push($flds,'edit');
            array_push($flds,'delete');
            foreach($hash as &$h){
                $h['delete'] = $this->formPostButton('Delete','delete-button','DELETE_ROW',$h[$rowkey]);
                $h['edit'] = $this->formPostButton('Edit','edit-button','EDIT_ROW',$h[$rowkey]);
            }
        }
        $b .= "<div class=\"generic-display-table\"><!-- begin generic-display-table -->\n";
        $b .= "<h3>Data displayed is primarily from table: $table</h3>\n";
        $b .= $this->genericDisplayTable($hash,$flds);
        $b .= "</div><!-- end generic-display-table -->\n";
        print $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function columnSortBy($table){
        if (array_key_exists('sort-by',$_POST)){
            //print_pre($_POST,__METHOD__ . ': Sort By Post');
            $sesssortkey = $table . '.' . 'sort-by-key';
            $sesssortdir = $table . '.' . 'sort-by-dir';
            $_SESSION[$sesssortkey] = filter_input(INPUT_POST,'sort-by',FILTER_SANITIZE_STRING);
            // if sesssortdir exists, toggle it.
            $_SESSION[$sesssortdir] = (array_key_exists($sesssortdir,$_SESSION)) ? (($_SESSION[$sesssortdir] == 'ASC') ? 'DESC' : 'ASC') : 'ASC';
            //print_pre($_SESSION,__METHOD__ . ': SESSION vars');
        }
        // set session variables for any sorting preference
        // session variable should be encoded with table and field
    }
    ////////////////////////////////////////////////////////////////////////////
    function orderByClause($table,$flds,$defkey = '',$defdir = ''){
        $b = '';
        $sesssortkey = $table . '.' . 'sort-by-key';
        $sesssortdir = $table . '.' . 'sort-by-dir';
        if( array_key_exists($sesssortkey,$_SESSION) && in_array($_SESSION[$sesssortkey],$flds)) $b .= "ORDER BY {$_SESSION[$sesssortkey]}";
        if( $b != '' && array_key_exists($sesssortdir,$_SESSION)) $b .= " {$_SESSION[$sesssortdir]}";

        if ($b == '' && $defkey != ''){
            $b .= 'ORDER BY $default';
            $b .= ( $defdir != '') ? ' ' . $defdir : '';
        }

        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    // $defdir not used here
    ////////////////////////////////////////////////////////////////////////////
    function orderField($table,$flds,$default = '',$defdir = ''){
        $sesssortkey = $table . '.' . 'sort-by-key';
        if( array_key_exists($sesssortkey,$_SESSION) && in_array($_SESSION[$sesssortkey],$flds)) return "{$_SESSION[$sesssortkey]}";
        return $default;
    }
    ////////////////////////////////////////////////////////////////////////////
    function displayIdInfo(){
        $table = 'idInfo';
        $rowkey = 'id_id';
        $flds = array('id_id','id_ident','id_name_first','id_name_last','id_phone','id_email','id_UCSBNetID','id_dept','id_extra');
        $b = '';
        $b .= $this->columnSortBy($table);
        $b .= $this->rowDeletion($table,$rowkey);
        $b .= $this->rowEdit($table,$rowkey,array('id_name_first','id_name_last','id_phone','id_email','id_UCSBNetID','id_dept','id_extra'),array('id_id','id_ident'));
        $orderBy = $this->orderByClause($table,$flds);
        $orderField = $this->orderField($table,$flds);
        $hash = $this->getKeyedHash($rowkey,"SELECT * FROM $table $orderBy;");
        $linecntr = 0;
        foreach($hash as &$h){
            $linecntr++;
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


            if( authorized('TRAQR','root')) {
                $h['delete'] = $this->formPostButton('Delete','delete-button','DELETE_ROW',$h[$rowkey]);
                //$h['regen'] = $this->formPostButton('Regen QR','regen-button','REGEN_QR_ROW',$h[$rowkey]);
                $h['edit'] = $this->formPostButton('Edit','edit-button','EDIT_ROW',$h[$rowkey]);
            }

            $h['locs'] = count($regenHash);
            $h['#'] = $linecntr;
            $h['.td-#'] = 'rowcnt';

        }
        $b .= "<div class=\"generic-display-table\"><!-- begin generic-display-table -->\n";
        $b .= "<h3>Data displayed is primarily from table: $table</h3>\n";

        // Add in any synthesized or extra fields not related to the db
        array_unshift($flds,'#');
        array_push($flds,'locs');
        if( authorized('TRAQR','root')) {
            array_push($flds,'delete','edit');
        }
        array_push($flds,'regen','#');
        $b .= $this->genericDisplayTable($hash,$flds,$orderField);
        $b .= "</div><!-- end generic-display-table -->\n";
        print $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function displayQrInfo(){
        $table = 'qrInfo';
        $rowkey = 'qr_id';
        $flds = array('qr_id','qr_uuid','qr_ident','qr_building','qr_room','qr_detail');
        $b = '';
        $b .= $this->columnSortBy($table);
        $b .= $this->rowDeletion($table,$rowkey);

        $orderBy = $this->orderByClause($table,$flds);
        $orderField = $this->orderField($table,$flds);
        $hash = $this->getKeyedHash($rowkey,"SELECT * FROM $table $orderBy;");
        $hash = $this->getKeyedHash($rowkey,"SELECT * FROM $table $orderBy;");
        $line = 1;
        foreach($hash as &$h){
            $h['#'] = $line++;
        }
        if ( authorized('TRAQR','root')){
            array_push($flds,'delete','regen');
            foreach($hash as &$h){
                $h['delete'] = $this->formPostButton('Delete','delete-button','DELETE_ROW',$h[$rowkey]);
                //$h['delete'] = "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\"><button type=\"submit\" name=\"DELETE_ROW\" value=\"{$h['qr_id']}\">Delete</button></form>";
                $h['regen'] = "<form action=\"/Admin/GenQR.php\" method=\"post\">
                <input type=\"hidden\" name=\"Identifier\" value=\"{$h['qr_ident']}\"></input>
                <input type=\"hidden\" name=\"Building1\" value=\"{$h['qr_building']}\"></input>
                <input type=\"hidden\" name=\"Room1\" value=\"{$h['qr_room']}\"></input>
                <button class=\"regen-button\" type=\"submit\">Regen QR</button></form>";
            }
        }
        array_unshift($flds,'#');
        array_push($flds,'#');
        $b .= "<div class=\"generic-display-table\"><!-- begin generic-display-table -->\n";
        $b .= "<h3>Data displayed is primarily from table: $table</h3>\n";
        $b .= $this->genericDisplayTable($hash,$flds,$orderField);
        $b .= "</div><!-- end generic-display-table -->\n";
        print $b;
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
        $b .= "<a class=\"entry-confirmation-link\" href=\"Safety.php?info=DONE\">";
        $b .= "<div class=\"big-button entry-confirmation $confirmationClass\">\n";
        $b .= "<strong class=\"confirmation\">$confirmationMessage for</strong><br>\n";
        // Want to get id_ident and maybe building and room, maybe not needed
        // $b .= "<strong class=\"confirmation\">$confirmationMessage for</strong><br>\n";
        // $b .= "<strong class=\"confirmation\">$confirmationMessage for</strong><br>\n";
        // $b .= "<table class=\"confirmation\">\n";
        // foreach( $dbFields as $f){
        //     if( $f == 'ip' ) continue;
        //     if( isset($data[$f]))   $b .= "<tr><td ><strong>" . $f . ":</strong></td><td><em>" . $data[$f] . "</em></td></tr>\n";
        // }
        // $b .= "</table>\n";
        $b .= "<p class=\"confirmation-finish\"><strong>Click Anywhere in Block To Complete</strong></p>\n";
        $b .= "</div>\n";
        $b .= "</a>\n";
        //print_pre($data,"scanConfirmationData");
        return $b;
    }
    function scanCancel(){
        $b = '';
        $b .= "<a class=\"big-button skip-entry\" href=\"./Safety.php?info=SKIPPED\">";
        $b .= "Skip " . $this->data['sd_mode'] . " Confirmation";
        $b .= "</a>\n";
        return $b;
    }
    function scanProcessRestart(){
        // https://traqr.eri.ucsb.edu/Enter.php?sd_mode=BIDIR&sd_uuid=8c195d8439c2bbdcd9aae0a4b7fd82cb&sd_stage=INIT
        $b = '';
        $b .= "<a class=\"entry-confirmation-link\" href=\"Enter.php?sd_mode=BIDIR&sd_uuid={$this->data['sd_uuid']}&sd_stage=INIT\">";
        $b .= "<div class=\"big-button entry-confirmation problematic\">\n";
        $b .= "<strong class=\"confirmation\">TIMEOUT: too much time between scan and confirmation</strong><br>\n";
        $b .= "<p class=\"confirmation-finish\"><strong>Click Anywhere in block to<br>";
        $b .= "Restart Entry/Confirmation Process<br>";
        //$b .= "{$this->data['sd_building']} {$this->data['sd_room']}";
        $b .= "</strong></p>\n";
        $b .= "</div>\n";
        $b .= "</a>\n";
        //print_pre($data,"scanConfirmationTableData");
        return $b;
    }
    function scanConfirmationMessages($confirmationMessage,$confirmationClass,$infoCode = 'SUCCESS',$msgLines = array()){
        $b = '';
        $b .= "<a class=\"entry-confirmation-link\" href=\"Safety.php?info=$infoCode\">";
        $b .= "<div class=\"big-button entry-confirmation $confirmationClass\">\n";
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
    // Manual backups can be taken no more frequently that once a minute,
    // auto backups can be run daily.
    // Want to compress the output, so having to modify code a bit!!
    function dbBackup($autoDaily = false){
        $b = '';
        $ds = date('Ymd');
        $ts = date('Ymd-Hi');
        $bksuff = ($autoDaily) ? "tab-$ds" : "tmb-$ts";
        $bkfile =  REL . BKDIR . "traqr.sqlite3-$bksuff.gz";
        $b .= "<p>Attempting to Backup SQLite db {$this->pdoFile} to: $bkfile<br>\n";

        if( file_exists($bkfile)) {
            $b .= "<p>A backupfile already exists for: $bkfile ... skipping</p>\n";
            return $b;
        }
        if( ($gzb = gzencode(file_get_contents($this->pdoFile))) === false){
            $b .= "<p>gzencode failed!  Possibly a permissions error???</p>";
        }
        else {
            $fp = gzopen($bkfile,'w9');
            gzwrite($fp,$gzb);
            gzclose($fp);
        }
        // if( copy($this->pdoFile,$bkfile) === false){
        //     $b .= "<p>Copy Failed!  Possibly a permissions error.</p>";
        // }
        // else {
        //     if( gzCompressFile($bkfile) === false){
        //         $b .= "<p>Compression Failed!  Not sure why.</p>\n";
        //     }
        // }
        return $b;
    }
    function importFileForm(){

    }
}

?>
