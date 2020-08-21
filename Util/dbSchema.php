<?php
    require_once(__DIR__ . '/../inc/all.inc.php');
    defineRelPath(__DIR__);

    $hd = new traqrDoc("DB Schema");
    $hd->htmlBeg();

    if ( authorized() ){
        $ce = new traQRpdo(getDSN());
        $ce->schema();
        print "<hr>\n";
        $ce->schemaHelper();
        print "<hr>\n";

        $b = '';
        $b .= "<p>Favorite Method for updating a table schema at this time is:</p>
        <ul>
            <li>Update schema for table in InitDB</li>
            <li>Via CLI:
                <ul>
                    <li>cd run; make bk;     # make a backup of sqlite file</li>
                    <li>sqlite3 &lt;sqliteFile&gt;  # enter sqlite interactive </li>
                    <li>ALTER TABLE &lt;tablename> RENAME TO &lt;tablename&gt;Prev</li>
                    <li>.quit</li>
                </ul>
            </li>
            <li>Run a Report page (anything that will trigger InitDB)</li>
            <li>Navigate to dbSchema page</li>
            <li>Copy appropriate helper line (list of fields) on dbSchema page</li>
            <li>Via CLI:
                <ul>
                    <li>INSERT INTO &lt;tablename&gt; SELECT (list of field names with extra '&lt;DEFAULT_VALUE&gt;', inserted at correct location) FROM &lt;tablename&gt;Prev</li>
                    <li>SELECT * FROM &lt;tablename&gt;;</li>
                    <li>.quit</li>
                </ul>
            </li>

        </ul>
        ";
        print $b;
    }
    else print authFail();

    $hd->htmlEnd();
?>
