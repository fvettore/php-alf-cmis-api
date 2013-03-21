<?php
/********************************************************************************
* 2013 by Fabrizio Vettore - fabrizio (at) vettore.org
*
* Simple command line script to migrate a folder from
* an ALFRESCO repo1 to a repo2.
* Tested with Alf3.x->Alf4.x
*
* It recursively navigates folder and subfoders to retrives objects
* It has limitate sync capabilities:
* transfers and updates only when needed (missing and updated objects).
*
* You can download the needed Alfresco_CMIS_API.php from
* http://code.google.com/p/php-alf-cmis-api/
* 
* THIS script is for demo purpose only and it is absolutely FREE
* USE it at your own risk!
* CAN be useful to copy documents between 2 repo but it doesn't take care of a
* lot of things like permissions, tags, revisioning etc. 
* (you have to adjust them by yourself)
* Moreover you will loose info on the original creator.
*
* I Know there are better migration tecniques, so take a look at them before using
* this uncomplete migration script in a production environment.
*
*********************************************************************************/+
require_once("../CMIS/newapi/Alfresco_CMIS_API.php");

$repo1="http://alfresco.cifarelli.loc:8080/alfresco/service/cmis";
$repo2="http://alfresconew.cifarelli.loc:8080/alfresco/cmisatom";
$user1="admin";
$user2="admin";
$password1="ImSecret";
$password2="ImSecret";
//the aboves are quite self-explaining......

//Origin an destination PATHs
$percorso1="/Sites/procedure/documentLibrary";
$percorso2="/Siti/procedure/documentLibrary";


migrateFolder($percorso1);

function migrateFolder($path){
	echo "\n=================================\n";
	echo "\nENTERING FOLDER $path\n";
	echo "\n=================================\n";
	global $repo1,$repo2,$user1,$user2,$password1,$password2,$percorso1,$percorso2;
	$folder=new CMISalfObject($repo1,$user1,$password1,null,null,$path);
	if(!$folder->loaded) die("\nSORRY! cannot open folder!\nThe last HTTP request returned the following status: ".$folder->lastHttpStatus."\n\n");
	$folder->listContent();

	foreach($folder->containedObjects as $obj){
		echo "------------------------------------\n";
		echo "### ".$obj->properties['cmis:name']." (".$obj->properties['cmis:baseTypeId'].")\n";
			$relpath=str_replace($percorso1,"",$folder->properties['cmis:path'])."/".$obj->properties['cmis:name'];
			echo "### RELATIVE PATH: $relpath \n\n ";

			//check if folder is present on repo2
			$newobj=new CMISalfObject($repo2,$user2,$password2,null,null,$percorso2.$relpath);
			if($newobj->loaded){//OBJECT is already present
				//CHECK FOR LAST MODIFICATION
				$repo1LastMod=$obj->properties['cmis:lastModificationDate'];				
				$repo2LastMod=$newobj->properties['cmis:lastModificationDate'];
				if(strcmp($repo1LastMod,$repo2LastMod)>0){
					//PAY ATTENTION to the time zone!!! It is not checked
					//PAY ATTENTION: working with documents NOT with folders! (cannot delete a nonempty folder)
					echo "$repo1LastMod -> $repo2LastMod\n";
					echo "DOCUMENT RECENTLY MODIFIED, REPLACING IT!\n";
					$newobj->delete();
					$newobj->loaded=FALSE;//force objet reloaading see following lines....
				}
			}
			if(!$newobj->loaded){//object not present on REPO2
				//CREATE THE OBJECT
				echo "\n NOT PRESENT on REPO2!!!\n";
				$folderOnRepo2=str_replace($obj->properties['cmis:name'],"",$percorso2.$relpath);
				echo " CREATE IN $folderOnRepo2\n";
				//get the PARENT folder on REPO2
				$newObjectContainer=new CMISalfObject($repo2,$user2,$password2,null,null,$folderOnRepo2);
				if(!$newObjectContainer->loaded)die("\n\nFATAL: cannot find parent opn repo2\n\n");
				//IT is a FOLDER
				if($obj->properties['cmis:baseTypeId']=="cmis:folder"){
					//it is a folder: create it!
					$newObjectId=$newObjectContainer->createFolder($obj->properties['cmis:name']);
					$newObject=new CMISalfObject($repo2,$user2,$password2,$newObjectId);
				}
				//IT is a document
				else {	
					//download file from repo1	
					$repo1Obj=new CMISalfObject($repo1,$user1,$password1,$obj->properties['cmis:objectId']);
					$file=$repo1Obj->download();
					echo "@@@@Downloading $file\n";
					//upload to repo2
					$newObjectId=$newObjectContainer->upload($file);
					unlink($file);
					$newObject=new CMISalfObject($repo2,$user2,$password2,$newObjectId);				
				}	
				echo "SETTING aspects\n";
				//Generates a warning if aspect not found. Too lazy to fix it!!! :-)
				if ($description= $obj->aspects['cm:description']) $newObject->setAspect("cm:description",$description);
				if ($author= $obj->aspects['cm:author']) $newObject->setAspect("cm:author",$author);
				if ($title= $obj->aspects['cm:title']) $newObject->setAspect("cm:title",$title);
				if ($summary= $obj->aspects['cm:summary']) $newObject->setAspect("cm:summary",$summary);

			}//END creating object
			if($obj->properties['cmis:baseTypeId']=="cmis:folder")
				//RECURSIVELY call itself! Take care to reserve enough memory for complex trees!!!!!!
				migrateFolder($obj->properties['cmis:path']);
	}

}



?>
