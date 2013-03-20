<?php
/*****************************************************
*
*  Simple document download by PATH
*
*****************************************************/
require_once "../Alfresco_CMIS_API.php";

if(count($argv)<5)die("  PARAMETER(s) MISSING!\n  USAGE: php documentDownload.php repoUrl username password documentPath\n  EXAMPLE: php documentDownload http://localhost:8080/alfresco/cmisatom admin password /Sites/test/documentLibrary/document.pdf\n\n");
$repoUrl=$argv[1];
$username=$argv[2];
$password=$argv[3];
$path=$argv[4];

//Load document
$document= new CMISalfObject($repoUrl,$username,$password,null,null,$path);

//prnt document properties and aspects
print_r($document->properties);
print_r($document->aspects);

$document->download();
//now document should be in your script folder


?>
