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
        // $this->qrScanFields[] = 'Stage';
        //array('Mode','Identifier','Building','Room','Variant','Stage');
    }
    ////////////////////////////////////////////////////////////////////////////
    function generateEmailAddresses(){
        $list = $this->fetchListNew("SELECT DISTINCT(Identifier) FROM scanData WHERE Identifier != '' AND Identifier like '%@%ucsb.edu%';");
        print_pre($list,"rowinfo");
        print implode(", ",$list);
    }
    ////////////////////////////////////////////////////////////////////////////
    function initData(){
        $this->data['epoch'] = time();  // not sure we will even need this
        $this->data['ds'] = date('Y-m-d');
        $this->data['its'] = date('Ymd-His');
        $this->data['iepoch'] = date('U');
        $this->data['ets'] = date('Ymd-His');
        $this->data['eepoch'] = date('U');
        $this->data['valid'] = TRUE;
        $this->data['ip'] = $_SERVER['REMOTE_ADDR'];
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
            sd_id       INTEGER PRIMARY KEY,  -- alias for rowid
            sd_uuid     TEXT,                 -- MD5 encoding of Identifier, Building and Room
            Mode        TEXT,                 -- INGRESS or EGRESS
            Status      TEXT,                 -- Various Status strings
            Identifier  TEXT,                 -- Identifier of person that scanned QR code
            Building    TEXT,                 -- Building
            Room        TEXT,                 -- Room # in Building
            ds          TEXT,                 -- Datestamp YYYY-MM-DD
            aCMZ        TEXT,                 -- Air handling contamination management zone (CMZ)
            pCMZ        TEXT,                 -- Physical contamination management zone (CMZ)
            its         TEXT DEFAULT '',      -- INGRESS timestamp
            iepoch      TIMESTAMP DEFAULT (strftime('%s','now')),  -- INGRESS epoch (ctime) value
            ets         TEXT DEFAULT '',      -- EGRESS timestamp
            eepoch      TIMESTAMP DEFAULT (strftime('%s','now')),  -- EGRESS epoch (ctime) value
            stay        INTEGER,              -- seconds of how long the users stay was
            hrstay      TEXT,                 -- human readable form for length of stay HH:MM?  H.DDDD
            flags       TEXT,                 -- Flags - Not sure how I want to use this yet, extra field for now
            extra       TEXT,                 -- extra unused field reclaimed from previous schema
            ip          TEXT                  -- IP submission came from
        );";
        $this->exec($q);

        // This is to record
        $q = "CREATE TABLE IF NOT EXISTS qrInfo (
            qi_id         INTEGER PRIMARY KEY,                       -- alias for rowid
            qi_ident      TEXT,                                      -- identifier
            qi_building   TEXT,                                      -- Building
            qi_room       TEXT,                                      -- Room # in Building
            qi_uuid       TEXT,                                      -- UUID for ident/building/room
            qi_epoch      TIMESTAMP DEFAULT (strftime('%s','now')),  -- date this entry was made
            qi_extra      TEXT,                                      -- extra field for possible use later
            UNIQUE(qi_ident,qi_building,qi_room) ON CONFLICT REPLACE
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
            id_extra       TEXT,                                     -- extra text field for possible user later
            UNIQUE(id_ident) ON CONFLICT REPLACE
        );";
        //print_pre($q,"query: $q");
        $this->exec($q);

        return true;
    }
    ////////////////////////////////////////////////////////////////////////////////
    function loadGetData(){
        // Would love to modularize these a bit and "register" their handling
        // that may be an option, the filter_inputs and preg_replace could likely be done pretty cleanly
        // but need to figure out a way to handle the selection list entries (Mode, Variant,Stage)
        // for now, use this to build some of the lists
        //print_pre($_GET,__METHOD__ . ": GET Vars at beginning");

        $f = 'eepoch';
        if(isset($_GET['eepoch'])){
            // while this looks at eepoch field, we are setting valid field to FALSE if it fails the check
            $diff = ($this->data[$f] - filter_input(INPUT_GET,$f,FILTER_VALIDATE_INT));
            // If set we want to compare to whats in the data from this load (which should be more recent...)
            if ( $diff < 0 || $diff > $GLOBALS['InvalidateConfirmSeconds']) $this->data['valid'] = FALSE;
        }

        $f = 'Mode';
        if( isset($_GET[$f])){
            if     ( $_GET[$f] == 'EGRESS'  ) $this->data[$f] = 'EGRESS';
            elseif ( $_GET[$f] == 'INGRESS' ) $this->data[$f] = 'INGRESS';
            else                              $this->data[$f] = 'NULL';
        }
        $this->qrScanFields[] = $f;
        $this->qrDbFields[] = $f;

        $f = 'Identifier';
        // the conditional below is ONLY in case someone has an old UCSBNetID QR
        // if the conditional is not used, then identifier is likely to get unset
        $this->data[$f] = preg_replace('/[^a-zA-Z0-9_@+\. ]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING)));
        $this->qrScanFields[] = $f;
        $this->qrDbFields[] = $f;

        // leave in support for UCSBNetID field for the moment
        $f = 'UCSBNetID';
        $this->data[$f] = preg_replace('/[^a-zA-Z0-9_@+\. ]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING)));
        // $this->qrScanFields[] = $f;
        //$this->qrDbFields[] = 'Identifier';
        if( array_key_exists($f,$this->data) && $this->data[$f] != '' && $this->data['Identifier'] == '') {
            $this->data['Identifier'] = $this->data[$f];   // aliasing this for now to catch older QR scans
        }
        // code and db no longer support this, so just nuke for time being.
        unset($this->data['UCSBNetID']);

        // $f = 'Identifier';
        // // the conditional below is ONLY in case someone has an old UCSBNetID QR
        // // if the conditional is not used, then identifier is likely to get unset
        // if ( isset($this->data[$f])) $this->data[$f] = preg_replace('/[^a-zA-Z0-9_@+\. ]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING)));
        // $this->qrScanFields[] = $f;
        // $this->qrDbFields[] = $f;

        $flags = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK;
        $f = 'Building';
        $this->data[$f] = preg_replace('/[^a-zA-Z0-9#+ ]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING,$flags)));
        $this->qrScanFields[] = $f;
        $this->qrDbFields[] = $f;

        $f = 'Room';
        $this->data[$f] = preg_replace('/[^a-zA-Z0-9#+ ]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING,$flags)));
        $this->qrScanFields[] = $f;
        $this->qrDbFields[] = $f;

        $f = 'Variant';
        if( isset($_GET[$f])){
            if     ( $_GET[$f] == 'VARIANT1'  ) $this->data[$f] = 'VARIANT1';
            elseif ( $_GET[$f] == 'VARIANT2' )  $this->data[$f] = 'VARIANT2';
            else                                $this->data[$f] = 'DIRECT';
        }
        else {
            $this->data[$f] = 'DIRECT';
        }
        $this->qrScanFields[] = $f;

        $f = 'Stage';
        if( isset($_GET[$f])){
            if     ( $_GET[$f] == 'CONFIRM'  )   $this->data[$f] = 'CONFIRM';
            elseif ( $_GET[$f] == 'DONE' )       $this->data[$f] = 'DONE';
            elseif ( $_GET[$f] == 'CONFIRMED' )  $this->data[$f] = 'DONE';
            elseif ( $_GET[$f] == 'COMPLETE' )   $this->data[$f] = 'DONE';
            elseif ( $_GET[$f] == 'START' )      $this->data[$f] = 'START';
            else                                 $this->data[$f] = 'NULL';
        }
        else $this->data[$f] = 'NOTSET';
        $this->qrScanFields[] = $f;

        // this is used by ReportDay and is not used in the db or by the Entry points
        $f = 'Date';
        $this->data[$f] = preg_replace('/[^0-9-]/','',trim(filter_input(INPUT_GET,$f,FILTER_SANITIZE_STRING,$flags)));

        //print_pre($this->data,__METHOD__ . ": this->data at end");
    }
    ////////////////////////////////////////////////////////////////////////////
    function dataToDb(){
        $this->loadGetData();
        if(! isset($this->data['Stage'])){
            print "Stage not set in GET<br>\n";
            print_pre($_GET,"GET data");
        }

        switch ($this->data['Stage']){
            case 'DONE':
                $this->dataEntryComplete();
                return;
                break;
            case 'CONFIRM':
                $this->dataToDbConfirmed();
                return;
                break;
            default:
                // do nothing,
                break;
        }

        if(! isset($this->data['Variant'])){
            print "Variant not set in GET<br>\n";
            print_pre($_GET,"GET data");
        }

        switch ($this->data['Variant']){
            case 'DIRECT':
                $this->dataToDbVar2();
                // $this->dataToDbOrig();
                break;
            case 'VARIANT1';
                $this->dataToDbVar1();
                break;
            case 'VARIANT2';
                $this->dataToDbVar2();
                break;
            default:
                print "uncaught Variant<br>\n";
                break;
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    function dataToDbVar1(){
        print "Variant1 - Form with confirmation button<br>\n";
    }
    ////////////////////////////////////////////////////////////////////////////
    function dataToDbVar2(){
        //print_pre($_GET,__METHOD__ . ": GET vars at beginning");
        //print_pre($this->data,__METHOD__ . ": data vars at beginning");

        $dbFields = $this->qrDbFields;  // initialize our local dbFields from the object list
        $dbFields[] = 'ds';
        // In this form we will ignore INGRESS/EGRESS, just do a search for existing
        // entries and output what we think is best, confirmation that goes
        //print "Variant2 - Form to choose ingress/egress confirmation buttons - Mode (INGRESS/EGRESS ignored)<br>\n";
        $this->data['Mode'] = 'SCRIPT_WILL_SELECT';

        // see if we have any existing records for Day/Identifier/Building/Room combo...
        $whereData = array(
            'Identifier' => $this->data['Identifier'],
            'ds'         => $this->data['ds'],
            'Building'   => $this->data['Building'],
            'Room'       => $this->data['Room'],
        );

        // get rowid of the FIRST location matching entry, this allows us to update ONLY the first matching entry instead of all
        $wd = $this->generateAndedWhereClause($whereData);
        $userLocationDateMatch = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY iepoch;",$wd['data']);

        if( count($userLocationDateMatch) == 0){
            // this is a new INGRESS for the day
            $this->data['Mode'] = 'INGRESS';
        }
        else{
            // get last sd_id
            foreach($userLocationDateMatch as $uldm){
                $lastqid = $uldm['sd_id'];
            }
            if ($userLocationDateMatch[$lastqid]['Mode'] == 'INGRESS'){
                $this->data['Mode'] = 'EGRESS';
            }
            elseif($userLocationDateMatch[$lastqid]['Mode'] == 'EGRESS'){
                $this->data['Mode'] = 'INGRESS';
                $this->data['ets'] = '';
            }
            else {
                // something went wrong
                print "<p>Something has gone wrong, we should not get here</p>\n";
            }
        }

        // Need to build link now....
        // pass in $this->data,keys to use)
        $this->data['Stage'] = 'CONFIRM';
        $getstr = http_build_query($this->data);


        print "<a class=\"confirm-entry\" href=\"./Enter.php?" . $getstr . "\">";
        print "Confirm<br>" . $this->data['Mode'] . "<br>Data";
        print "</a>\n";


        print "<hr>\n";

        print "<a class=\"skip-entry\" href=\"./Enter.php?Stage=DONE\">";
        print "Skip " . $this->data['Mode'] . " Confirmation";
        print "</a>\n";

        //print_pre($this->data,"Confirmation data");
    }
    ////////////////////////////////////////////////////////////////////////////
    // With the confirm button, this is where the data is actually written out
    ////////////////////////////////////////////////////////////////////////////
    function dataToDbConfirmed(){
        //print_pre($this->data,__METHOD__ . ": this->data at start of method");
        $dbFields = $this->qrDbFields;  // initialize our local dbFields from the object list
        $dbFields[] = 'ds';
        // User has signed off that this data is correct, just do entry
        // $b = '';
        // $b .= "<p>User has confirmed the entry - Just need to update/insert data as appropriate</p>";
        // print $b;

        if (! $this->data['valid']){
            print $this->scanConfirmation("Too much time between scan and confirmation","problematic",$dbFields,$this->data);
        }
        elseif ($this->data['Mode'] == "INGRESS" ){
            $dbFields[] = 'its';
            $dbFields[] = 'iepoch';
            $dbFields[] = 'Status';
            $dbFields[] = 'ip';
            //$dbFields[] = 'sd_uuid';
            $this->data['Status'] = 'UNPAIR-IN';

            $qd = $this->insertQueryData($this->tablename, $this->data,$dbFields);
            //print_pre($dbFields,"dbFields");
            //print_pre($qd['data'],"Data for query string: " . $qd['qstr']);
            $this->q($qd['qstr'],$qd['data']);
            print $this->scanConfirmation("Successful Ingress Scan+Confirm","success",$dbFields,$this->data);
        }
        elseif($this->data['Mode'] == "EGRESS" ){
            $dbFields[] = 'ets';
            $dbFields[] = 'eepoch';   // replace the value already in db
            $dbFields[] = 'Status';
            $dbFields[] = 'stay';
            //$dbFields[] = 'sd_uuid';
            $this->data['Status'] = 'PAIRED';
            $whereData = array(
                'Identifier' => $this->data['Identifier'],
                'ds'         => $this->data['ds'],
                'Building'   => $this->data['Building'],
                'Room'       => $this->data['Room'],
                'ets'        => '',
            );

            // get rowid of the FIRST location matching entry, this allows us to update ONLY the first matching entry instead of all
            $wd = $this->generateAndedWhereClause($whereData);
            // $singleHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY iepoch LIMIT 1;",$wd['data']);

            $ingressHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY iepoch;",$wd['data']);

            $updatedEntries = 0;
            foreach($ingressHash as &$ih){
                $whereData['sd_id'] = $ih['sd_id'];
                $this->data['stay'] = $this->data['eepoch'] - $ih['iepoch'];
                $qd = $this->updateQueryData($this->tablename, $this->data,$dbFields,$whereData);
                $pdos = $this->q($qd['qstr'],$qd['data']);
                $affected = $pdos->rowCount();
                $updatedEntries += $affected;
                //print "update: $affected, updated Total: $updatedEntries<br>";

                // So, only the first one gets the PAIRED value, all subsequent
                $this->data['Status'] = 'EXTRA-IN';
            }

            //print "Rows Affected == $affected<br>";
            if($updatedEntries == 0){
                // We need to remove the 'stay' entry from dbFields...
                foreach($dbFields as $k => $v){
                    if ($v == 'stay') break;
                }
                unset($dbFields[$k]);

                //print "No matching INGRESS record found, inserting unmatched EGRESS<br>";
                $this->data['Status'] = "UNPAIR-OUT";
                $qd = $this->insertQueryData($this->tablename, $this->data,$dbFields);
                //print_pre($dbFields,"dbFields");
                //print_pre($qd['data'],"Data for query string: " . $qd['qstr']);
                $pdos = $this->q($qd['qstr'],$qd['data']);
                $affected = $pdos->rowCount();

                print $this->scanConfirmation("Egress Entry before Ingress - Problematic ($affected)","problematic",$dbFields,$this->data);
            }
            elseif($updatedEntries == 1) {
                //print "SUCCESS!<br>";
                print $this->scanConfirmation("Successful Egress Scan+Confirm","success",$dbFields,$this->data);
            }
            else {
                // because of fetching the rowid of the first match above coupled with the LIMIT 1
                // this case shouldn't be able to happen now.
                print $this->scanConfirmation("SR Matched multiple ($updatedEntries) INGRESS entries for QR scanned data","problematic",$dbFields,$this->data);
                //print "More than 1 matching INGRESS record found indicating some sort of issue<br>";
            }
        }

        //print "<a href=\"./Enter.php?Stage=DONE\">Click DONE with data entry</a><br>\n";

        // print_pre($dbFields,"dbFields");
        // print_pre($this->data,"data");
    }
    ////////////////////////////////////////////////////////////////////////////
    function dataEntryComplete(){
        $b = '';
        $b .= "<p>Thanks for using the scanner!</p>
        <p>Please, stay safe:<br>
        &nbsp;&nbsp;Wash Hands thoroughly and frequently<br>
        &nbsp;&nbsp;Avoid touching your face with unwashed hands<br>
        &nbsp;&nbsp;Maintain Social Distance when possible<br>
        &nbsp;&nbsp;Wear a mask when unable to Social Distance<br>
        </p>
        <img src=\"./media/mask_sick_take_care_of_your_health_flu_health_emoji_expression_face_emoticon_fever_ill_influenza_lifestyle_treatment_sneeze_epidemic_cold_temperature-512.png\"><br>
        <a href=\"./\">Exit to Main page</a><br>
        ";
        print $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function dataToDbOrig(){
        $dbFields = $this->qrDbFields;  // initialize our local dbFields from the object list
        $dbFields[] = 'ds';

        $inputFailure = FALSE;
        if (! isset($this->data['Mode']) || ! ($this->data['Mode'] == 'INGRESS' || $this->data['Mode'] == 'EGRESS'))  { $inputFailure = TRUE; $which = 'Mode'; }
        //if (! ($this->data['Mode'] == 'INGRESS' || $this->data['Mode'] == 'EGRESS')) { $inputFailure = TRUE; $which = 'ModeType'; }
        if (! isset($this->data['Building']) || $this->data['Building'] == ''){ $inputFailure = TRUE; $which = 'Building'; }
        if (! isset($this->data['Room']) || $this->data['Room'] == '') {$inputFailure = TRUE; $which = 'Room'; }
        if (! isset($this->data['Identifier']) || $this->data['Identifier'] == '') {$inputFailure = TRUE; $which = 'netid'; }
        if ($inputFailure){
            print $this->scanConfirmation("Entry Failed (Data Issues ($which))","failure",$dbFields,$this->data);
            return;
        }

        $mode = $this->data['Mode'];
        if ($mode == "INGRESS" ){
            // Additional INGRESS data fields
            $dbFields[] = 'its';
            $dbFields[] = 'iepoch';
            $dbFields[] = 'Status';
            $this->data['Status'] = 'UNPAIR-IN';
            $qd = $this->insertQueryData($this->tablename, $this->data,$dbFields);
            //print_pre($dbFields,"dbFields");
            //print_pre($qd['data'],"Data for query string: " . $qd['qstr']);
            $this->q($qd['qstr'],$qd['data']);
            print $this->scanConfirmation("SR Successful Ingress entry for QR scanned data","success",$dbFields,$this->data);
        }
        elseif($mode == "EGRESS" ){
            // Egress entry will attempt to update a matching INGRESS record...
            // Additional EGRESS data fields
            $dbFields[] = 'ets';
            $dbFields[] = 'eepoch';   // replace the value already in db
            $dbFields[] = 'Status';
            $dbFields[] = 'stay';
            $this->data['Status'] = 'PAIRED';
            $whereData = array(
                'Identifier' => $this->data['Identifier'],
                'ds'        => $this->data['ds'],
                'Building'  => $this->data['Building'],
                'Room'      => $this->data['Room'],
                'ets'       => '',
            );

            // get rowid of the FIRST location matching entry, this allows us to update ONLY the first matching entry instead of all
            $wd = $this->generateAndedWhereClause($whereData);
            // $singleHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY iepoch LIMIT 1;",$wd['data']);

            $ingressHash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename " . $wd['qstr'] . " ORDER BY iepoch;",$wd['data']);

            $updatedEntries = 0;
            foreach($ingressHash as &$ih){
                $whereData['sd_id'] = $ih['sd_id'];
                $this->data['stay'] = $this->data['eepoch'] - $ih['iepoch'];
                $qd = $this->updateQueryData($this->tablename, $this->data,$dbFields,$whereData);
                $pdos = $this->q($qd['qstr'],$qd['data']);
                $affected = $pdos->rowCount();
                $updatedEntries += $affected;
                //print "update: $affected, updated Total: $updatedEntries<br>";

                // So, only the first one gets the PAIRED value, all subsequent
                $this->data['Status'] = 'EXTRA-IN';
            }

            //print "Rows Affected == $affected<br>";
            if($updatedEntries == 0){
                // We need to remove the 'stay' entry from dbFields...
                foreach($dbFields as $k => $v){
                    if ($v == 'stay') break;
                }
                unset($dbFields[$k]);

                //print "No matching INGRESS record found, inserting unmatched EGRESS<br>";
                $this->data['Status'] = "UNPAIR-OUT";
                $qd = $this->insertQueryData($this->tablename, $this->data,$dbFields);
                //print_pre($dbFields,"dbFields");
                //print_pre($qd['data'],"Data for query string: " . $qd['qstr']);
                $pdos = $this->q($qd['qstr'],$qd['data']);
                $affected = $pdos->rowCount();

                print $this->scanConfirmation("SR Egress Entry before Ingress - Problematic ($affected)","problematic",$dbFields,$this->data);
            }
            elseif($updatedEntries == 1) {
                //print "SUCCESS!<br>";
                print $this->scanConfirmation("SR Successful matched Egress entry for QR scanned data","success",$dbFields,$this->data);
            }
            else {
                // because of fetching the rowid of the first match above coupled with the LIMIT 1
                // this case shouldn't be able to happen now.
                print $this->scanConfirmation("SR Matched multiple ($updatedEntries) INGRESS entries for QR scanned data","problematic",$dbFields,$this->data);
                //print "More than 1 matching INGRESS record found indicating some sort of issue<br>";
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////////
    function analyzeData(){
        $this->q("UPDATE scanData SET stay=(eepoch - iepoch) WHERE Status = 'PAIRED';");
        $this->q("UPDATE scanData SET Status='TESTING' WHERE (Status = 'PAIRED' AND stay < 60);");
        //$this->q("UPDATE scanData SET hrstay=(stay/3600)||':'||((stay%3600)/60) WHERE (Status = 'PAIRED' AND stay < 60);");
        //$this->q("UPDATE scanData SET Status='TESTING' where (Status = 'TEST' AND stay < 60);");

        $b = '';

        $dss = $this->fetchListNew("SELECT DISTINCT(ds) FROM $this->tablename ORDER BY ds DESC;");
        if ( $dss === FALSE){
            $b .= '<p>Error on query: ' . $q . '</p>' . NL ;
        }
        foreach($dss as $ds){
            $b .= $this->analyzeDataForDS($ds);
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
    function analyzeDataByDay(){
        $this->q("UPDATE scanData SET stay=(eepoch - iepoch) WHERE Status = 'PAIRED';");
        $this->q("UPDATE scanData SET Status='TESTING' WHERE (Status = 'PAIRED' AND stay < 60);");
        //$this->q("UPDATE scanData SET hrstay=(stay/3600)||':'||((stay%3600)/60) WHERE (Status = 'PAIRED' AND stay < 60);");
        //$this->q("UPDATE scanData SET Status='TESTING' where (Status = 'TEST' AND stay < 60);");

        $b = '';

        $dss = $this->fetchListNew("SELECT DISTINCT(ds) FROM $this->tablename ORDER BY ds DESC;");
        if ( $dss === FALSE){
            $b .= '<p>Error on query: ' . $q . '</p>' . NL ;
        }
        foreach($dss as $ds){
            $b .= $this->analyzeDataByDayForDS($ds);
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
    function analyzeDataByDayForDS($ds){
        // should maybe do a regex check on it
        if ( $ds == '' ) return '';
        //print "ds: $ds<br>\n";
        list($y,$m,$d) = explode('-',"$ds");
        $hrds = date('l, F j, Y',mktime(0,0,0,$m,$d,$y));
        $enhance = ($ds == date('Y-m-d') ) ? " (Today)" : "" ;
        $b = '';
        $b .= '<div class="an-data-ds-container">' . NL;
        $b .= '<h3>Report by Person for datestamp: ' . $ds . ' - ' . $hrds . $enhance . '</h3>' . NL;

        $dayHash = $this->getKeyedHash('sd_id',"SELECT * FROM scanData WHERE ds = ? ORDER BY Identifier;",array($ds));
        foreach($dayHash as &$h){
            $h['flags'] = '';
            $h['flags'] = '';
            $h['.td-Status']   = '%%VALUE%%';
            $h['.td-Building'] = '%%VALUE%%';
            if ($h['Status'] == 'PAIRED') $h['hrstay'] = sprintf('%d:%02d:%02d',($h['stay']/3600),(($h['stay']%3600)/60),($h['stay']%60));
            if ($h['Status'] == 'TESTING') $h['hrstay'] = sprintf('%d:%02d:%02d',($h['stay']/3600),(($h['stay']%3600)/60),($h['stay']%60));

        }
        $flds = array('sd_id','Identifier','Building','Room','Mode','Status','ds','its','iepoch','ets','eepoch','stay','hrstay','flags');
        $b .= $this->genericDisplayTable($dayHash,$flds);


        // $ids = $this->fetchListNew("SELECT DISTINCT(Identifier) FROM $this->tablename WHERE ds = ?;",array($ds));
        // if ( $ids === FALSE){
        //     $b .= '<p>Error on query: ' . $q . '</p>' . NL ;
        // }
        // print_r($ids,"Distinct id");
        // foreach($ids as $id){
        //     $b .= $this->analyzeDataForDSID($ds,$id);
        // }

        $b .= '</div>' . NL;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function analyzeDataForDS($ds){
        // should maybe do a regex check on it
        if ( $ds == '' ) return '';
        //print "ds: $ds<br>\n";
        list($y,$m,$d) = explode('-',"$ds");
        $hrds = date('l, F j, Y',mktime(0,0,0,$m,$d,$y));
        $enhance = ($ds == date('Y-m-d') ) ? " (Today)" : "" ;
        $b = '';
        $b .= '<div class="an-data-ds-container">' . NL;
        $b .= '<h3>Report by Person for datestamp: ' . $ds . ' - ' . $hrds . $enhance . '</h3>' . NL;
        $ids = $this->fetchListNew("SELECT DISTINCT(Identifier) FROM $this->tablename WHERE ds = ?;",array($ds));
        if ( $ids === FALSE){
            $b .= '<p>Error on query: ' . $q . '</p>' . NL ;
        }
        print_r($ids,"Distinct id");
        foreach($ids as $id){
            $b .= $this->analyzeDataForDSID($ds,$id);
        }

        $b .= '</div>' . NL;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    // This is a check for a given day and given user
    ////////////////////////////////////////////////////////////////////////////
    function analyzeDataForDSID($ds,$id){
        $today = date('Y-m-d');
        $b = '';
        $b .= '<div class="an-data-dsid-container">' . NL;
        $b .= '<strong>' . $id . '&nbsp;' . $ds . '</strong>' . NL;

        $hash = $this->getKeyedHash('sd_id',"SELECT * FROM $this->tablename WHERE (ds = ? and Identifier = ?) ORDER BY iepoch;",array($ds,$id));

        $prevh = array();
        $prevh['Mode'] = '';
        $prevh['Building'] = '';
        $prevh['Room'] = '';
        $len = count($hash);
        $cntr = 0;
        $firstpass = TRUE;

        /*
        If a given person has more rows than distinct locations (Building/Room)  Then we need to check for overlaps
        Overlaps should be flagged in db.
        Process will be as follows:
        loop over ds
          distinct user - paired only
            check for overlaps
              if overlap, update Status (?) - overlap is begining falls between beg and end of another entry OR ending falls between beg and end of another entry.
        */

        foreach($hash as &$h){
            $h['flags'] = '';
            $h['flags'] = '';
            $h['.td-Status']   = '%%VALUE%%';
            $h['.td-Building'] = '%%VALUE%%';
            if ($h['Status'] == 'PAIRED') $h['hrstay'] = sprintf('%d:%02d:%02d',($h['stay']/3600),(($h['stay']%3600)/60),($h['stay']%60));
            if ($h['Status'] == 'TESTING') $h['hrstay'] = sprintf('%d:%02d:%02d',($h['stay']/3600),(($h['stay']%3600)/60),($h['stay']%60));
            //$h['.tr'] = $h['Status'];
        }

        $flds = array('sd_id','Identifier','Building','Room','Mode','Status','ds','its','iepoch','ets','eepoch','stay','hrstay','flags');
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
            //$b .= "<input type=\"hidden\" name=\"CONFIRMED\" value=\"YES\"></input>";
            $b .= "<input class=\"confirm-submit\" type=\"submit\" name=\"CONFIRMED\" value=\"Confirm Edit of row $rowToEdit\"></input>";
            $b .= "</form>";

            $b .= "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\">";
            $b .= "<input type=\"hidden\" name=\"EDIT_ROW\" value=\"$rowToEdit\"></input>";
            //$b .= "<input type=\"hidden\" name=\"CONFIRMED\" value=\"YES\"></input>";
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
            //$b .= "<input type=\"hidden\" name=\"CONFIRMED\" value=\"YES\"></input>";
            $b .= "<input class=\"confirm-submit\" type=\"submit\" name=\"CONFIRMED\" value=\"Confirm Deletion of row $rowToDelete\"></input>";
            $b .= "</form>";
            $b .= "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\">";
            $b .= "<input type=\"hidden\" name=\"DELETE_ROW\" value=\"$rowToDelete\"></input>";
            //$b .= "<input type=\"hidden\" name=\"CONFIRMED\" value=\"YES\"></input>";
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

            $regenHash = $this->getKeyedHash('qi_uuid',"SELECT * FROM qrInfo WHERE qi_ident = ? LIMIT ?;",array($h['id_ident'],MAX_BUILDING_ROOM_COMBOS));
            //print_pre($regenHash,"Regen Hash for user: ".$h['id_ident']);
            $h['regen'] = "<form action=\"/Admin/GenQR.php\" method=\"post\">
            <input type=\"hidden\" name=\"Identifier\" value=\"{$h['id_ident']}\"></input>\n";
            $num = 1;
            foreach($regenHash as $rh){
                $h['regen'] .= "<input type=\"hidden\" name=\"Building$num\" value=\"{$rh['qi_building']}\"></input>\n";
                $h['regen'] .= "<input type=\"hidden\" name=\"Room$num\" value=\"{$rh['qi_room']}\"></input>\n";
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
        $rowkey = 'qi_id';
        $b = '';
        $b .= $this->rowDeletion($table,$rowkey);

        $hash = $this->getKeyedHash($rowkey,"SELECT * FROM $table;");
        foreach($hash as &$h){
            $h['delete'] = $this->formPostButton('Delete','delete-button','DELETE_ROW',$h[$rowkey]);
            //$h['delete'] = "<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\"><button type=\"submit\" name=\"DELETE_ROW\" value=\"{$h['qi_id']}\">Delete</button></form>";
            $h['regen'] = "<form action=\"/Admin/GenQR.php\" method=\"post\">
            <input type=\"hidden\" name=\"Identifier\" value=\"{$h['qi_ident']}\"></input>
            <input type=\"hidden\" name=\"Building1\" value=\"{$h['qi_building']}\"></input>
            <input type=\"hidden\" name=\"Room1\" value=\"{$h['qi_room']}\"></input>
            <button class=\"regen-button\" type=\"submit\">Regen QR</button></form>";
        }
        $flds = array('qi_id','qi_uuid','qi_ident','qi_building','qi_room','delete','regen');
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
    //     $hash = $this->getKeyedHash('qi_id',"SELECT * FROM qrInfo;");
    //     foreach($hash as &$h){
    //         if( TRUE ){
    //             $h['qi_uuid'] = genUUID($h['qi_ident'],$h['qi_building'],$h['qi_room']);
    //             $q = "UPDATE qrInfo SET qi_uuid = ? WHERE qi_id = ?;";
    //             $this->q($q,array($h['qi_uuid'],$h['qi_id']));
    //         }
    //     }
    // }
    ////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////
    function reportAll(){
        $b = '';

        $flds = array('sd_id','Identifier','FirstName','LastName','Building','Room','Mode','Status','ds','its','iepoch','ets','eepoch','ip','flags');
        $hash = $this->getKeyedHash('sd_id',"SELECT *,id_name_first as FirstName,id_name_last as LastName FROM $this->tablename INNER JOIN idInfo ON Identifier = id_ident ORDER BY iepoch DESC;");
        foreach($hash as &$h){
            $h['flags'] = '';
            $h['.td-Status']   = '%%VALUE%%';
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
        $testEntries = $this->getKeyedHash('sd_id',"SELECT * FROM scanData WHERE Identifier like '%@test.ucsb.edu';");
        $testEntriesCount = count($testEntries);
        $this->q("DELETE FROM scanData WHERE Identifier like '%@test.ucsb.edu';");
        print_pre($testEntries,"Deleted ($testEntriesCount) Test Entries as follows:");

    }
}

?>
