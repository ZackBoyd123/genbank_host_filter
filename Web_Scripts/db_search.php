
<?php
/* Global query var. 
 * We generate an array of queries later so we make this global.
 */
global $query; 
// Start a session
session_start();
/*
 * Get the values submitted into the text boxes on the previous page. 
 * Deal with these later on in the script.
 */
if(isset($_POST['submitted'])){
	$Organism=$_POST['organism'];
	$HostTaxid=$_POST['hosttaxid'];
	$Hostname=$_POST['hostname'];
	$_SESSION["host_pass"] = $Hostname;

}
  
// Database connection
$dbcon = mysqli_connect("localhost","gbfilter_user","MypW!23","gb_filter");
if (mysqli_connect_errno()){
		echo "Failed to connect to MYSQL:   " . mysqli_connect_error();
} else {
    	
}
// If user specified a taxid, grab its name from the database to name files
if(is_null($HostTaxid)){
	$query = " SELECT `name_txt` FROM `Taxonomy_name` WHERE `tax_id` = $HostTaxid LIMIT 1";
	$result = mysqli_query($dbcon, $query);
	while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
		$_SESSION["host_pass"] = $row['name_txt'];
	}
}
/* 
 *	WORKING WITH MULTIPLE SQL QUERIES BASED ON TEXT BOXES
 *	This should make it easier to deal with SQL queries for different things.
 */
 
// If the user has specified both a tax id and a host name, exit script. 
if ($HostTaxid != NULL && $Hostname != NULL){
	die("Just specify host tax id or host name, no need for both");
}

/* If the user has specified just a virus, set a boolean to true. 
 * Work with this in second php block
 */
if ($Organism != NULL && ($HostTaxid == NULL && $Hostname == NULL)){
	$just_virus = True;	
}

/*
 * If there is information in the virus box, do the recursive sql 
 * function to get all of its children etc.
 */
if ($Organism != NULL){
	// Get the parent taxid of users input text. Must be identical to name.dmp.
	$query = "SELECT `tax_id` FROM `Taxonomy_name` WHERE `name_txt` = \"$Organism\" LIMIT 1";
	
	// The executed SQL query from above.
	$result= mysqli_query($dbcon,$query) or die("no result");
	
	// Number of rows variable.
	$numrows = mysqli_num_rows($result);
	if($numrows == 0){
		echo "The virus you specifed wasn't found in the database";
		die;
	}
	
	// The host taxid to search in the parent field.
	//$virus_values = array();
	
	while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
		$virus_tip = $row['tax_id'];	
	}	
	
	// Execute recurisve function.


}
$_SESSION["virus_pass"] = $virus_tip;
/*
 * If there is any information in either one of the host tax id or host name box
 * execute the following code.
 */
if ($Hostname != NULL || $HostTaxid != NULL) {
	// If there is no information in the virus box set a boolean to true
	// deal with it in the second php block. 
	if ($Organism == NULL){
		$just_hosts = True;
	}
	
	/*
	 * Both the taxid and host name cant be set so if the hostname is set 
	 * do this code, else do the hosttaxid code.
	 */
	if ($Hostname != NULL){
		// Query to get the host_taxid and parent_tax_id from the hostname the 
		//user has input in the text box.
		$query="SELECT `tax_id` FROM `Taxonomy_name` WHERE `name_txt` = \"$Hostname\" LIMIT 1";

	}
	elseif ($HostTaxid != NULL){
		$query="SELECT `tax_id` FROM `Taxonomy_name` WHERE `tax_id` = \"$HostTaxid\" LIMIT 1";

	}

	// The executed SQL query from above.
	$result= mysqli_query($dbcon,$query) or die("no result");
	
	// Number of rows variable.
	$numrows = mysqli_num_rows($result);
	if($numrows == 0){
		echo "The host you specified wasn't found in the database.";
		die;
	}


	
	while($row = mysqli_fetch_array($result,MYSQLI_ASSOC)){
		$tip = $row['tax_id'];	
	}
	
	
}

/*
* End of first php block.
*/

?>


<?php
//	FUNCTION START
// generate a html krona header
function krona_header(){
	$html = "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">
	<head>
		<meta charset=\"utf=8\"/>
		<link rel=\"shortcut icon\" href=\"http://krona.sourceforge.net/img/favicon.ico\"/>
		<script id=\"notfound\">window.onload=function(){document.body.innerHTML=\"Could not get resources from \\\"http://krona.sourceforge.net\\\".\"}</script>
  		<script type=\"text/javascript\" src=\"krona_hack/krona2.js\"></script>
 	</head>
 		<body>
  			<img id=\"hiddenImage\" src=\"http://krona.sourceforge.net/img/hidden.png\" style=\"display:none\"/>
  			<img id=\"loadingImage\" src=\"http://krona.sourceforge.net/img/loading.gif\" style=\"display:none\"/>
  			<noscript>Javascript must be enabled to view this page.</noscript>
  			<div style=\"display:none\">
  	<krona collapse=\"false\">
  		<attributes magnitude=\"sequences\">
  			<attribute display=\"Sequences\">sequences</attribute>
  			<attribute display=\"Percent\">pct</attribute>
  		</attributes>\n";
  	echo $html;
}

/* A function to process the tip node of a taxid. Slightly different from the other code, as need
 * to select from different tables / columns etc
 */
function top_node($id, $boo){
	// Database connection
	global $dbcon;
	// Taxid gathered from input box on index
	global $virus_tip;

 	// Get the name as reported by names.dmp. Makes the krona plot a bit nicer to look at. 
	$name_query = " SELECT `name_txt` FROM `Taxonomy_name` WHERE `tax_id` = $id LIMIT 1";
	$name_result = mysqli_query($dbcon, $name_query);
	$name = $name_result->fetch_row()[0];
	// If we're dealing with viruses only select host taxids which have a viral taxid which
	// corresponds to the one the user is interested in. 
	if($boo == "virus"){
		$seqs_query = "SELECT `accession_number`, `host_taxid` FROM `parasite_lineage`
		INNER JOIN ( SELECT `child_tax_id` FROM `virus_lineage` WHERE `parent_tax_id` = $virus_tip ) v
		ON v.`child_tax_id` = `parasite_lineage`.`taxonomy_id`
		WHERE `host_taxid` = $id";
	// Otherwise select all the rows with relevant host taxids
	} else {
		$seqs_query = " SELECT `accession_number`, `host_taxid` FROM `parasite_lineage` WHERE `host_taxid` = $id";
	}
	// Grab the number of rows which we're interested in.
	$seqs_result = mysqli_query($dbcon, $seqs_query);
	$seqs = mysqli_num_rows($seqs_result);
	//Put all the accession numbers in an array and return it. (memory issues)
	$accs = array();
	while($row = mysqli_fetch_array($seqs_result, MYSQLI_ASSOC)){
		array_push($accs, $row['accession_number']);
	}
	if($seqs == 0){
		echo $name . "\t was found in the database, but there are no entries for this host + a virus"; 
		die;
	}
	krona_header();
	// Print format for the krona plot. 
	echo "<node name=\"" . $name . "\">\n<sequences><val>" . $seqs . "</val></sequences>\n";
	return $accs;
}

/* 
 * As we are unable to preserve heirarchy when pre computing we still need to do a little bit of recursion. 
 * This function performs a little recursion and prints all the krona information required. 
 * No longer need to recursively calculate number of rows as it's pre-computed.
 */
function host_recurse($id, $boo, $return_arr = array()){
	// DB connection and taxid relating to virus.
	global $dbcon;
	global $virus_tip;
	// Get child nodes of the input taxid
	$list_query = " SELECT `tax_id` FROM `node` WHERE `parent_tax_id` = $id ";
	$list_result = mysqli_query($dbcon, $list_query);
	while($row = mysqli_fetch_array($list_result, MYSQLI_ASSOC)){
		$value = $row['tax_id'];
		// Get the english name for krona
		$name_query = " SELECT `name_txt` FROM `Taxonomy_name` WHERE `tax_id` = $value LIMIT 1";
		$name_result = mysqli_query($dbcon, $name_query);
		$name = $name_result->fetch_row()[0];
		// Get tot number of seqs associated with each id
		// Only use rows wich have relevant viral taxids if a virus is specified.
		if($boo == "virus"){
			$seq_query = "SELECT `host_taxid` FROM `parasite_lineage`
			INNER JOIN ( SELECT `child_tax_id` FROM `virus_lineage` WHERE `parent_tax_id` = $virus_tip ) v
			ON v.`child_tax_id` = `parasite_lineage`.`taxonomy_id`
			WHERE `host_taxid` = $value";
		} else {
			$seq_query = " SELECT `host_taxid` FROM `parasite_lineage` WHERE `host_taxid` = $value";
		}
		$seq_result = mysqli_query($dbcon, $seq_query);
		$tot_seqs = mysqli_num_rows($seq_result);
		//Print the krona to screen. Ignore any entries which don't have a corresponding row in table.
		if($tot_seqs > 0){
			echo "<node href=\"https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=" . $value . "\" name=\"" . $name . "\">\n<sequences><val>" . $tot_seqs . "</val></sequences>\n";
			// Do different recursions based on host or virus.
			if($boo == "host"){
				host_recurse($value, "host");
			} else {
				host_recurse($value, "virus");
			}
			// Close off krona tags.
			echo "</node>\n";
		} // else {
// 			echo "<node name=\"" . $name . "\">\n<sequences><val>0</val></sequences></node>";
// 		}
		
	}

}

/*
 * If just the host name or host tax id is given do this code. 
 */
if ($just_hosts == 1){
	
	$_SESSION["acc_nums"] = top_node($tip, "host");
	$_SESSION["just_host"] = "true";
	host_recurse($tip, "host");
	echo "</node>";

	

/*
 * If just virus is given do this. 
 * Probably want to delete this code later as it's not neccessary. 
 */
} elseif ($just_virus == 1){
	die("Select a host aswell");


    
    
/*
 * If both the host name and virus has been supplied do this code. 
 */    
} else {
	$_SESSION["acc_nums"] = top_node($tip, "virus");
	host_recurse($tip, "virus");
	echo "</node>";
	
}


$dbcon->close();
/*
End of second PHP block
*/
?>

</krona>
</body>
</html>
















