<?php
require_once(__DIR__ . '/htmlDoc.inc.php');

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
class traqrDoc extends htmlDoc {
     function __construct($title){
         parent::__construct($title);
     }
     function navHTML(){
         $b = '';
         $b .= "<header>\n";
         if ( $this->heading != '') $b .= "<h1>$this->heading</h1>\n";
         $b .= "</header>\n";
         $b .= "<nav>\n";
         $b .= $this->navItem('Index.php','Home');
         $b .= $this->navItem('Admin/GenQR.php','QR Gen');
         if ( authorized()){
             $b .= $this->navItem('Admin/ReportAll.php','All Data');
             $b .= $this->navItem('Admin/ReportDaily.php','Daily Data');
             $b .= $this->navItem('Admin/ReportDailyByIdent.php','Daily Data (byIdent)');
             $b .= $this->navItem('util/Index.php','Util/Dev/Tools');
             $b .= $this->navItem('Admin/Index.php','Admin');
         }
         $b .= "</nav>\n";
         $b .= "<hr>\n";
         return $b;
     }
     function whatevs(){
         $b = '<ul>
   <li class="nav"><a href="#home">Home</a></li>
   <liclass="nav"><a href="#news">News</a></li>
   <li class="dropdown nav">
     <a href="javascript:void(0)" class="dropbtn">Dropdown</a>
     <div class="dropdown-content">
       <a href="#">Link 1</a>
       <a href="#">Link 2</a>
       <a href="#">Link 3</a>
     </div>
   </li>
 </ul>';

     }
     function contentIndex(){
         $b = '';
         $b .= "
         <p>
         This site is a quick proof of concept for generating custom QR codes for Individuals specific to Building and Room.
         <br>
         The idea is to collect the data in a form that is easier to harvest than what we have seen so far in other methods.
         </p>
         <p>
         The Generate Codes link below allows the user to specify up to 7 combinations of Building/Room to produce both INGRESS and EGRESS QR codes for.
         <br>
         Use the nav menu at the top of the page to navigate to the various options and operations.
         </p>
         <p>
         Administrators (checked via IP) will see additional options in the navmenu and be able to look at the logged data.
         </p>
         <p>
         The URL in the QR code is a form submission script that is also part of this site.
         <br>
         When the QR code is scanned and the resulting site visited the form creates a sqlite3 db entry from that information with appropriate timestamps.
         </p>
         ";

         $todo = "
         <p>
         <strong>To Do</strong>
         <ul>
         <li>General
           <ul>
             <li>Authenticate to SSO?</li>
           </ul>
         </li>
         <li>QR code generation
           <ul>
           <li>Input checking on room number(numerical with optionally one trailing alpha)</li>
           <li class=\"done\">Switch to UCSBNetID based (aaron_martin@ucsb.edu)</li>
           </ul>
         </li>
         <li>Data Scanning
           <ul>
             <li class=\"done\">Improve Display (you have been logged)</li>
             <li>Check for existence of user to flash NOT approved (?)</li>
           </ul>
         </li>
         <li>Data Display
           <ul>
             <li>Sortable</li>
             <li class=\"done\">Some data checking (Egress with no ingress, ingress with no egress, who is in the building)</li>
           </ul>
         </li>
         <li>Data Management
           <ul>
             <li>Table with Mapping from UCSBNetID to FirstName, LastName, PhoneNumber (?)</li>
             <li class=\"done\">Some data checking (Egress with no ingress, ingress with no egress, who is in the building)</li>
           </ul>
         </li>
         </ul>
         </p>
         ";

         if (authorized()){
             $b .= "$todo";
         }

         return $b;
     }
}
?>
