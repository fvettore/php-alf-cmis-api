<?php
/*****************************************************
*
*  Simple folder listing by PATH
*
*****************************************************/
require_once "../Alfresco_CMIS_API.php";

if(count($argv)<5)die("  PARAMETER(s) MISSING!\n  USAGE: php folderList.php repoUrl username password path\n  EXAMPLE: php folderList.php http://localhost:8080/alfresco/cmisatom admin password /\n\n");
$repoUrl=$argv[1];
$username=$argv[2];
$password=$argv[3];
$path=$argv[4];

//Load folder
$folder= new CMISalfObject($repoUrl,$username,$password,null,null,$path);
if(!$folder->loaded) die("\nSORRY! cannot open folder!\nThe last HTTP request returned the following status: ".$folder->lastHttpStatus."\n\n");

$folder->listContent();

//uncomment the following for printing DATA STRUCT of all contained objects
//print_r($folder->containedObjects);

echo "====================================\n";
echo "Contained objects:\n\n";
foreach($folder->containedObjects as $obj){
	echo $obj->properties['cmis:name']."  (".$obj->properties['cmis:baseTypeId'].")\n";
}


?>
