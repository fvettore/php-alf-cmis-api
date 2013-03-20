<?php
/*****************************************************
*
*  Simple document file upload in a folder
*  with basic error handling
*
*****************************************************/
require_once "../Alfresco_CMIS_API.php";

if(count($argv)<6)die("  PARAMETER(s) MISSING!\n  USAGE: php documentUpload.php repoUrl username password folderpath filepath\n  EXAMPLE: php documnetUpload.php http://localhost:8080/alfresco/cmisatom admin password / TEXTFILE.txt\n\n");
$repoUrl=$argv[1];
$username=$argv[2];
$password=$argv[3];
$folderPath=$argv[4];
$fileName=$argv[5];


//Load folder object
$folder= new CMISalfObject($repoUrl,$username,$password,null,null,$folderPath);
if(!$folder->loaded) die("\nSORRY! cannot open folder!\nThe last HTTP request returned the following status: ".$folder->lastHttpStatus."\n\n");

//Load contained objects
$folder->listContent();


//Check if the supplied path is a valid folder object (should be a cmis:folder type)
if($folder->properties['cmis:baseTypeId']<>'cmis:folder')die("Not a valid FOLDER\n\n");

echo "\n====================================\n";
echo "folder Contained objects BEFORE uploading:\n\n";
foreach($folder->containedObjects as $obj){
	echo $obj->properties['cmis:name']."  (".$obj->properties['cmis:baseTypeId'].")\n";
}

//PAY ATTENTION! file MUST be in your script folder!
//check if file exists
if(!is_file($fileName))die("File not found!!\n");

//upload
$newDocId=$folder->upload($fileName);

//the above returns FALSE if object cannot be loaded
if(!$newDocId)echo "\n\nSORRY! cannot upload file!\nThe last HTTP request returned the following status: ".$folder->lastHttpStatus."\n\n";
else echo "UPLOADED new doc with ID=$newDocId\n\n";

//relist folder contained objects
$folder->listContent();
echo "\n====================================\n";
echo "folder Contained objects AFTER uploading:\n\n";
foreach($folder->containedObjects as $obj){
	echo $obj->properties['cmis:name']."  (".$obj->properties['cmis:baseTypeId'].")\n";
}


?>
