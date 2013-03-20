<?php
/*****************************************************
*
*  Simple object deletion by PATH
*
*****************************************************/
require_once "../Alfresco_CMIS_API.php";

if(count($argv)<5)die("  PARAMETER(s) MISSING!\n  USAGE: php objectDelete.php repoUrl username password path\n  EXAMPLE: php objecdDelete.php http://localhost:8080/alfresco/cmisatom admin password /\n\n");
$repoUrl=$argv[1];
$username=$argv[2];
$password=$argv[3];
$path=$argv[4];

//Load Object
$repoObject= new CMISalfObject($repoUrl,$username,$password,null,null,$path);
if(!$repoObject->loaded) die("\nSORRY! cannot load object!\nThe last HTTP request returned the following status: ".$repoObject->lastHttpStatus."\n\n");
else echo "LOADED object with ID=".$repoObject->objId."\n";

//DELETE it
$OK=$repoObject->delete();
if(!$OK) die("\nSORRY! cannot delete object!\nThe last HTTP request returned the following status: ".$repoObject->lastHttpStatus."\n\n");
else echo "\nObject DELETED!\n\n";

?>
