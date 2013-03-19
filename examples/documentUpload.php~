<?php

require_once "../Alfresco_CMIS_API.php";

if(count($argv)<6)die("  PARAMETER(s) MISSING!\n  USAGE: php folderList.php repoUrl username password folderpath filepath\n  EXAMPLE: php folderList.php http://localhost:8080/alfresco/cmisatom admin password / TEXTFILE.txt\n\n");
$repoUrl=$argv[1];
$username=$argv[2];
$password=$argv[3];
$folderPath=$argv[4];
$fileName=$argv[5];

//Load folder object
$folder= new CMISalfObject($repoUrl,$username,$password,null,null,$folderPath);
//Load contained objects
$folder->listContent();


//Check if the supplied path is a valid folder object (should be a cmis:folder type)
if($folder->properties['cmis:baseTypeId']<>'cmis:folder')die("Not a valid PATH for folder\n\n");

echo "\n====================================\n";
echo "folder Contained objects BEFORE uploading:\n\n";
foreach($folder->containedObjects as $obj){
	echo $obj->properties['cmis:name']."  (".$obj->properties['cmis:baseTypeId'].")\n";
}


//check if file exists
if(!is_file($fileName))die("File not found!!\n");

//upload
$folder->upload($fileName);
//relist folder contained objects
$folder->listContent();
echo "\n====================================\n";
echo "folder Contained objects AFTER uploading:\n\n";
foreach($folder->containedObjects as $obj){
	echo $obj->properties['cmis:name']."  (".$obj->properties['cmis:baseTypeId'].")\n";
}


?>
