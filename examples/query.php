<?php
/*****************************************************
*
*  Simple QUERY
*
*****************************************************/
require_once "../Alfresco_CMIS_API.php";

if(count($argv)<5)die("  PARAMETER(s) MISSING!\n  USAGE: php query.php repoUrl username password query\n  EXAMPLE: php folderList.php http://localhost:8080/alfresco/cmisatom admin password \"select * from cmis:document\"\n\n");
$repoUrl=$argv[1];
$username=$argv[2];
$password=$argv[3];
$query=$argv[4];


//Load folder
$repo= new CMISalfObject($repoUrl,$username,$password,null,null,"/");
if(!$repo->loaded) die("\nSORRY! cannot open repo!\nThe last HTTP request returned the following status: ".$repo->lastHttpStatus."\n\n");

$repo->maxItems=5;
$result=$repo->query($query);


//DUMPING RESULT
while($line=$result->fetch_array()){
	print_r($line);
}

echo "Total number of rows: ".$result->num_rows."\n";

?>
