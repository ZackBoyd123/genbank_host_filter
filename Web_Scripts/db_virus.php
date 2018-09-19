<!doctype html>
<body>
<?php
session_start();
// print_r($_SESSION["acc_nums"]);

// Database connection
$dbcon = mysqli_connect("localhost","root","root","filter_lineages");
if (mysqli_connect_errno()){
		echo "Failed to connect to MYSQL:   " . mysqli_connect_error();
} else {

}
/*
 * Repeat of the function in db_search
 */
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

/* 
 * Similar to the function in db search with some changes. 
 * As we calculated the number of seqs associated with a tip, we only need
 * to do a count(array_passed) to get the total number of viral sequences.
 */
function top_node($id){
	// Database connection
	global $dbcon;
	// Taxid gathered from input box on index
	global $virus_tip;

 	// Get the name as reported by names.dmp. Makes the krona plot a bit nicer to look at. 
	$name_query = " SELECT `name_txt` FROM `Taxonomy_name` WHERE `tax_id` = $id LIMIT 1";
	$name_result = mysqli_query($dbcon, $name_query);
	$name = $name_result->fetch_row()[0];
	
	// Total number of viral sequences.
	$seqs = count($_SESSION["acc_nums"]);
	if($seqs == 0){
		echo $tip . "\t" . $name . "\t was found in the database, but there are no entries for this host + a virus"; 
		die;
	}
	krona_header();
	// Print format for the krona plot. 
	echo "<node name=\"" . $name . "\">\n<sequences><val>" . $seqs . "</val></sequences>\n";
}

//Sort of sketchy creating a temp table with a foreach insert of all the acc_nums we have
// from previous php
mysqli_query($dbcon, "DROP TABLE IF EXISTS `#virus_tax`");
mysqli_query($dbcon, "CREATE TABLE `#virus_tax`( `acc_num` VARCHAR(255) NOT NULL, PRIMARY KEY `acc_num`(`acc_num`))");
array_unique($_SESSION["acc_nums"]);
foreach($_SESSION["acc_nums"] as $val){
	$query = "INSERT INTO `#virus_tax`(`acc_num`) VALUES (\"$val\")";
	mysqli_query($dbcon, $query);
}


function host_recurse($id){
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
		
		
		$seq_query = "SELECT `host_taxid` FROM `parasite_lineage_virus`
		INNER JOIN ( SELECT `acc_num` FROM `#virus_tax` ) v
		ON v.`acc_num` = `parasite_lineage_virus`.`accession_number`
		WHERE `taxonomy_id` = $value";
		
		
		$seq_result = mysqli_query($dbcon, $seq_query);
		$tot_seqs = mysqli_num_rows($seq_result);
		//Print the krona to screen. Ignore any entries which don't have a corresponding row in table.
		if($tot_seqs > 0){
			echo "<node href=\"https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=" . $value . "\" name=\"" . $name . "\">\n<sequences><val>" . $tot_seqs . "</val></sequences>\n";
			host_recurse($value);	
			// Close off krona tags.
			echo "</node>\n";
		} // else {
// 			echo "<node name=\"" . $name . "\">\n<sequences><val>0</val></sequences></node>"; 		
// 		}
	}

}



if(isset($_SESSION["virus_pass"])){
	top_node($_SESSION["virus_pass"]);
	host_recurse($_SESSION["virus_pass"]);
	
} else {
	top_node(10239);
	host_recurse(10239);
}



?>
</body>