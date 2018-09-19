<!doctype html>
<body>
 <?php
 session_start();  
 echo "Downloading accession numbers for specified inputs.\n";
 ?>
 <p>You can upload all the accession numbers </p><a href="https://www.ncbi.nlm.nih.gov/sites/batchentrez">Here</a>
 <?php
$count = 0;
$file_append = 1;
$file = fopen($_SESSION["host_pass"] . "_" . $file_append . ".txt", "w");

foreach($_SESSION["acc_nums"] as $val){
	$count++;
	if($count % 5000 == 0){
		$file_append++;
		fclose($file);
		$file = fopen($_SESSION["host_pass"] . "_" . $file_append . ".txt", "w");
	}
	$val = $val . "\n";
	fwrite($file, $val);
}
fclose($file);

$zip = new ZipArchive;
if($zip->open($_SESSION["host_pass"] . ".zip", ZipArchive::CREATE) === True){
	for($i=1; $i<=$file_append; $i++){
		$zip->addFile($_SESSION["host_pass"] . "_" . $i . ".txt");
	}
	$zip->close();
}

$to_download = $_SESSION["host_pass"] . ".zip";
$zip_name = basename($to_download);
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=$zip_name");
header("Content-Length: " . filesize($to_download));
header("Pragma: no-cache");
header("Expires: 0");
ob_clean();
flush();
readfile($to_download);
unlink($to_download);
for($i=1; $i<=$file_append; $i++){
		unlink($_SESSION["host_pass"] . "_" . $i . ".txt");
	}
session_unset();
//exit;
 ?>
 </body>
 
 
 
 
 
 
 
 
 
 
 
 
 
 