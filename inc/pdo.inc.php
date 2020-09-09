<?php
////////////////////////////////////////////////////////////////////////////////
class pdoCore extends PDO {
    function __construct($dsn){
        parent::__construct($dsn);
        $this->result = NULL;
        $this->dbInError = FALSE;
        $this->errBuf = '';
        $this->errPrefix = array('fail' => 'FAIL');
        $this->dsn = $dsn;
        list($type,$file) = explode(":",$this->dsn);
        $this->pdoType = $type;
        $this->pdoFile = $file;
        $this->data = array();
        $this->uri = $_SERVER['REQUEST_URI'];

        $this->initData();
        $this->initDB();
    }
    ////////////////////////////////////////////////////////////////////////////
    function initData(){
        // stub
    }
    ////////////////////////////////////////////////////////////////////////////
    function initDB(){
        // stub
    }
    ////////////////////////////////////////////////////////////////////////////
    /**
    sqlite_master fields are:
        Type     : Type is Index or Table.
        name     : Index or table name
        tbl_name : Name of the table
        rootpage : rootpage, internal to SQLite
        sql      : The SQL statement, which would create this table. Example:
    **/
    ////////////////////////////////////////////////////////////////////////////
    function schema($returnBuf = FALSE){
        $scd = array();
        $b = '';
        $b .= "<p>Displaying Schema for $this->dsn</p>\n";
        $b .= "<pre>\n";
        //$thash = $this->getKeyedHash('name',"SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%';");

        $thash = $this->getKeyedHash('name',"SELECT * FROM sqlite_master WHERE name NOT LIKE 'sqlite_%';");
        $use_old = FALSE;
        foreach($thash as $th){
            $b .= $th['sql'] . "\n";
        }
        $b .= "</pre>\n";

        if ( $returnBuf ) return $b;
        print $b;
    }
    function schemaHelper($returnBuf = FALSE){
        $scd = array();

        $thash = $this->getKeyedHash('name',"SELECT * FROM sqlite_master WHERE name NOT LIKE 'sqlite_%';");
        foreach($thash as $th){
            if ($th['type'] == 'table'){
                $scd[$th['name']] = array();
                $scd[$th['name']]['elem'] = array();
                $scd[$th['name']]['string'] = '';

                $chash = $this->getKeyedHash('name',"PRAGMA table_info({$th['name']});");
                foreach($chash as $ch){
                    $scd[$th['name']]['elem'][] = $ch['name'];
                }
            }
        }

        $h = '';
        $h .= "<p>Helper strings for updating schemas with existing data</p>\n";
        $h .= "<pre>\n";
        foreach($scd as $k => $sc){
            $sc['string'] = implode(',',$sc['elem']);
            $h .= $k . ' ' . $sc['string'] . "\n";
        }
        $h .= "</pre>\n";

        if ( $returnBuf ) return $b;
        print $h;
    }
    ////////////////////////////////////////////////////////////////////////////
    // this is an exact copy of the global print_pre found in printUtils.php
    ////////////////////////////////////////////////////////////////////////////
    function print_pre($v,$label = "",$returnme = false){
        $b = "";
        if ( $label != "" ){
            $b .= "<hr><font color=blue>$label</font>\n";
        }
        $b .= "<pre style='margin: 0; padding: 0;'>";

        $b .= print_r($v,true);

        $b .= "</pre>";
        if ( $label != "" ){
            $b .= "<font color=blue>$label</font><hr>\n";
        }

        if( ! $returnme ) print $b;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////
    function q($qstr,$values = null){
        if ( ! $this->initDB() ) {
            return false;
        }
        $error = "";

        //dprint("executing query on {$this->db} db",0,0,"$qstr");
        // original method before refined the prepare/execute options
        //$this->pdos = $this->dbh->query("$qstr");

        if( is_object($this->result)){
            //dprint("having to closeCursor on non-empty result before query",1,0,"");
            //$this->print_pre($this->result,"remaining result");
            $this->result->closeCursor();
        }

        if( is_string($qstr) && is_null($values)){
            //print "running straight query<br />";
            if( ($this->pdos = $this->query("$qstr")) === false ){
                $error .= "straight query failed: <br />\n";
            }
        }
        else if (  is_string($qstr) && ! is_null($values) ){
            if( is_array($values)){
                if( ($this->pdos = $this->prepare($qstr)) === false ){
                    $error .= "prepare failed";
                }
                else {
                    if( $this->pdos->execute($values) === false ){
                        $error .= "execute failed";
                    }
                }
            }
            else {
                $error .= "2nd argument should be an array or null<br />";
                // error condition
            }
        }
        else if ( is_object($qstr) && method_exists($qstr,'bindParam')){ // This means its a PDOStatement
            $this->pdos = $qstr;
            if( is_null($values)){  // Assume prepared PDOStatement, just execute
                //print "executing with no values<br />\n";
                $qstr->execute();
            }
            else if ( is_array($values)){
                //print "executing with values<br />\n";
                //$this->print_pre($values,"executing with values");
                if( $qstr->execute($values) === true ){
                }
                else {
                    $this->print_pre($qstr->errorInfo(),"error info",true);
                }
            }
            else {
                $error .= "2nd argument to getKeyedHash is not null or array<br />\n";
            }
        }
        else {
            $error .= "1st argument to getKeyedHash is not string or PDOStatement Object<br />\n";
        }

        // have had some issues with detecting errors, checking for null kind of worked, but
        // doesn't align perfectly with the docs
        if ($this->pdos == null  || $error != "" ) {
        //if ($error != "") {
                //$error = $result->errorCode();
                //dprint(__FUNCTION__,0,0,"$error:");
                print "error on query error: $error, query: $qstr, debug_backtrace follows:";
                $dbt = debug_backtrace();
                $ei = $this->errorInfo();
                $this->print_pre($ei);
                $this->print_pre($qstr);
                $this->print_pre($dbt);
                die("fatal db query error<br>\n");
        }
        else {
        }
        return($this->pdos);
    }
    ////////////////////////////////////////////////////////////////////////////
    // OK, so getKeyedHash now handles a couple possible usages:
    //   key and basic query string
    //   key and string for a prepare and an array of values for an execute
    //   key and a PDOStatement object and an array of values for an execute or null if prebound
    ////////////////////////////////////////////////////////////////////////////
    function getKeyedHash($key = "",$qstr = "", $values = null ){
        $hash = array();  // initialize output hash
        // nice idea, but InitDB() may not actually get called till the fetch all
        //if( $this->dbCheckError(__FUNCTION__)) return $hash;
        //$this->print_pre($values,"values passed into getKeyedHash() for qstr: $qstr");
        if( ($pdos = $this->q($qstr,$values)) === false){
            $this->errBuf .= $this->errPrefix['fail'] . "Cannot complete query <span class=\"b\">$qstr</span> in " . __METHOD__ . "<br />\n";
            return $hash;
        }
        $r = $pdos->fetchAll(PDO::FETCH_ASSOC);
        //$this->print_pre($r,"results from fetchAll()");
        if(! is_array($key)){
            if( preg_match("/,/",$key)) $key = explode(",",$key);
        }
        if( $key == "" ) return $r;
        foreach($r as $row){
            //print "getKeyedHash(): processing row<br>\n";
            // if key is an array, create a compound key for the hash
            if( is_array($key)) {
                $ckey_elem = array();
                foreach( $key as $k){
                    //print "key: $k<br>\n";
                    $ckey_elem[] = $row[$k];
                }
                $ckey = implode(",",$ckey_elem);
            }
            else $ckey = $row[$key];
            $hash[$ckey] = $row;
        }
        return $hash;
    }
    ////////////////////////////////////////////////////////////////////////////////
    // newer version of above function that relies on the user assembling, but
    // more cleanly supports the newer prepare/execute query routine
    ////////////////////////////////////////////////////////////////////////////////
    function fetchListNew($qstr,$values = null){
        $result = $this->q("$qstr",$values);
        $r = $result->fetchAll(PDO::FETCH_COLUMN);
        return $r;
    }
    ////////////////////////////////////////////////////////////////////////////////
    function fetchValNew($qstr,$values = null){
        $result = $this->q("$qstr",$values);
        $r = $result->fetchAll(PDO::FETCH_COLUMN);
        return (isset($r[0])) ? $r[0] : '';
    }
    ////////////////////////////////////////////////////////////////////////////////
    // Where clause generated from a single variable - a hashed array
    ////////////////////////////////////////////////////////////////////////////////
    function generateAndedWhereClause($where = array()){
        # generate where clause
        $data = array();
        if (count($where) > 0){
            $kelem = array();
            $velem = array();
            foreach($where as $k => $v){
                $kelem[] = "$k=?";
                $data[] = "$v";
            }
            $whereClause = " WHERE (" . implode(" AND ",$kelem) . ')';
        }
        else {
            $whereClause = "";
        }
        return array('qstr' => $whereClause, 'data' => $data);
    }
    ////////////////////////////////////////////////////////////////////////////////
    function updateQueryData($table,$hash,$dbFields,$where = array()){
        $out = array('qstr' => 'UPDATE ' . $table . ' SET ', 'data' => array());
        $data = array();
        $kelem = array();
        $velem = array();
        foreach($dbFields as $k){
            $kelem[] = "$k=?";
            $velem[] = '?';
            $data[] .= "$hash[$k]";
        }
        $out['qstr'] .= implode(', ',$kelem);
        $wd = $this->generateAndedWhereClause($where);

        $out['qstr'] .= $wd['qstr'] . ';';
        $out['data'] = array_merge($data,$wd['data']);

        // Need to return query string AND data array
        return $out;
    }
    ////////////////////////////////////////////////////////////////////////////////
    function insertQueryData($table,$hash,$dbFields){
        $out = array('qstr' => 'INSERT INTO ' . $table . ' (', 'data' => array());
        $kelem = array();
        $velem = array();
        foreach($dbFields as $k){
            $kelem[] = "$k";
            $velem[] = '?';
            $out['data'][] .= "$hash[$k]";
        }
        $out['qstr'] .= implode(',',$kelem) . ') VALUES (' . implode(',',$velem) . ');';
        return $out;
    }
    function buildTrClassString($prefix,$h,$static = ''){
        $classElements = array();
        $classElements[] = $static;
        $fieldClassKey = $prefix;    // use a static class if provided
        if (isset($h[$fieldClassKey])){     // loop over any space seperated values
            foreach(explode(' ', $h[$fieldClassKey]) as $className){
                // VALUE means nothing in this context...
                $classElements[] = ( $className == '%%VALUE%%' ) ? '' : $h[$fieldClassKey] ;
            }
        }
        $classStr = implode(' ',$classElements);
        return $classStr;
    }
    ////////////////////////////////////////////////////////////////////////////////
    // Want to provide a means for multiple classes to be passed in for any given
    // td, just have to figure out the way to do that (space separated?, csv? array?)
    // and would possibly like to have some sort of macro replacment...
    ////////////////////////////////////////////////////////////////////////////////
    function buildTdClassString($prefix,$h,$f,$static = ''){
        $classElements = array();
        $classElements[] = $static;      // use a static class if provided
        $classElements[] = $f;           // use the field name
        $fieldClassKey = $prefix . $f;    // look for any class info embedded in the data hash
        if (isset($h[$fieldClassKey])){  // loop over any space seperated values
            foreach(explode(' ', $h[$fieldClassKey]) as $className){
                $classElements[] = ( $className == '%%VALUE%%' ) ? $h[$f] : $h[$fieldClassKey] ;
            }
        }
        $classStr = implode(' ',$classElements);
        return $classStr;
    }
    ////////////////////////////////////////////////////////////////////////////////
    function genericDisplayTable($hash,$displayFields = array()){
        $b = '';
        $b .= '<table class="qr-data-table">' . NL;
        $b .= '<tr class="qr-data-table-header-row">' . NL;
        foreach($displayFields as $f){
            $fd = "<form method=\"post\"><button type=\"submit\" class=\"sort-by\" name=\"sort-by\" value=\"$f\">";
            $fd .= $f . '</button>' . NL;
            $b .= "<th class=\"qr-data-table-td $f\">$fd</th>\n";
        }
        $b .= '</tr>' . NL;
        $last = array();
        $rowcntr = 0;
        foreach($hash as $r){
            $rowcntr++;
            $modclass = "mod" . ($rowcntr % 5);
            $status = 'NA';
            $classStr = $this->buildTrClassString('.tr',$r,'qr-data-table-row ' . $modclass);
            //$rowclass = 'qr-data-table-row';
            //    $rowclass .= ( isset($r['@row-class']) ) ? " ".$r['@row-class'] : '' ;
            $b .= "<tr class=\"$classStr\">" . NL;
            // print_pre($r,"Result Row");
            foreach($displayFields as $f){
                $classStr = $this->buildTdClassString('.td-',$r,$f,'qr-data-table-td');
                $v = ( isset($r[$f]) ) ? $r[$f] : ''  ;
                $b .= "<td class=\"$classStr\">$v</td>\n";
            }
            $b .= '</tr>' . NL;
            $last = $r;
        }
        $b .= '</table>' . NL;
        return $b;
    }
    ////////////////////////////////////////////////////////////////////////////////
    // Where clause generated from a single variable - a hashed array
    ////////////////////////////////////////////////////////////////////////////////
    function andedWhere($valHash = array(),$whereKeys = null){
        $out = array('qstr' => '','data' => array());
        if ($whereKeys == null) return $out;
        elseif (is_string($whereKeys)) $keys = array($whereKeys);
        elseif (is_array($whereKeys)) $keys = $whereKeys;
        else return $out;

        if ( ! is_array($valHash)) return array('qstr' => '','data' => '');;

        # generate where clause
        //$data = array();
        if (count($keys) > 0){
            $kelem = array();
            $velem = array();
            foreach($keys as $k){
                $kelem[] = "$k=?";
                $out['data'][] = $valHash[$k];
            }
            $out['qstr'] = " WHERE (" . implode(" AND ",$kelem) . ')';
        }
        else {
            $out['qstr'] = "";
        }
        return $out;
    }
    ////////////////////////////////////////////////////////////////////////////
    // Should we require that the where values be in the data?
    ////////////////////////////////////////////////////////////////////////////
    function update($table,$data = array(),$keys = null,$whereKeys = array()){
        if ( ! is_array($whereKeys)) return false;
        if ( $keys == null ) $keys = array_keys($data);

        $varr = array();
        //$harr = array();
        //$uarr = array();
        foreach($keys as $key){
            $varr[] = $data[$key];
            $harr[] = "$key=?";
            //$uarr[] = "$key=excluded.$key";
        }

        // Need to build WHERE clause, if whereKey and whereVal are arrays we assume AND
        $whereA = $this->andedWhere($data,$whereKeys);
        foreach($whereA['data'] as $v) $varr[] = $v;

        $kstr = implode(',',$keys);
        $hstr = implode(',',$harr);
        //$ustr = implode(',',$uarr);
        $this->q("UPDATE  $table SET $hstr {$whereA['qstr']};",$varr);

    }
    ////////////////////////////////////////////////////////////////////////////
    function insert($table,$data = array(),$keys = null){
        if ( $keys == null ) $keys = array_keys($data);

        $varr = array();
        $harr = array();
        //$uarr = array();
        foreach($keys as $key){
            $varr[] = $data[$key];
            $harr[] = '?';
            //$uarr[] = "$key=excluded.$key";
        }
        $kstr = implode(',',$keys);
        $hstr = implode(',',$harr);
        //$ustr = implode(',',$uarr);
        $this->q("INSERT INTO $table ($kstr) VALUES ($hstr);",$varr);
    }
    ////////////////////////////////////////////////////////////////////////////
    // Upsert is non-standard sql adopted by postgres and sqlite
    // Basically if an insert violated a UNIQUE constraint it turns into an
    // UPDATE on the row that violates the constraint, so it allows an UPDATE
    // instead of a REPLACE (which increments rowid)
    // The only issue to sort out is what order the arguments should be in...
    ////////////////////////////////////////////////////////////////////////////
    function upsert($table,$conflictFields = array(),$data = array(),$keys = null){
        if ( $keys == null ) $keys = array_keys($data);
        if (is_string($conflictFields)) $conflictFields = array($conflictFields);

        $cstr = implode(',',$conflictFields);
        $varr = array();
        $harr = array();
        $uarr = array();
        foreach($keys as $key){
            $varr[] = $data[$key];
            $harr[] = '?';
            $uarr[] = "$key=excluded.$key";
        }
        $kstr = implode(',',$keys);
        $hstr = implode(',',$harr);
        $ustr = implode(',',$uarr);
        $this->q("INSERT INTO $table ($kstr) VALUES ($hstr) ON CONFLICT($cstr) DO UPDATE SET $ustr;",$varr);
    }
}
?>
