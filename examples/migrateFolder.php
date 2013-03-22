<?
/********************************************************************************
* 2013 by Fabrizio Vettore - fabrizio (at) vettore.org
*
* Simple script to migrate a folder from
* an ALFRESCO repo1 to a repo2.
* Tested with Alf3.x->Alf4.x
*
* It recursively navigates folder and subfoders to retrive objects
* It has limitate sync capabilities:
* transfers and updates only when needed (missing and updated objects).
*
* You can download the needed Alfresco_CMIS_API.php from
* http://code.google.com/p/php-alf-cmis-api/
* 
* CAN be useful to copy documents between 2 repos but it doesn't take care of a
* lot of things like permissions, tags, revisioning etc. 
* (you have to adjust them by yourself)
* Moreover you will loose info about the original creator
* since all docs trasferred will be created by $user2
*
* I Know there are better migration tecniques, so take a look at them before using
* this uncomplete migration script in a production environment!
*
*
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*
*********************************************************************************/+
require_once("../CMIS/newapi/Alfresco_CMIS_API.php");

$repo1="http://alfresco.cifarelli.loc:8080/alfresco/service/cmis";
$repo2="http://alfresconew.cifarelli.loc:8080/alfresco/cmisatom";
$user1="admin";
$user2="admin";
$password1="isSecret";
$password2="isSecret";
//the aboves are quite self-explaining......

//Origin an destination PATHs
$percorso1="/Sites/documentale/documentLibrary";
$percorso2="/Siti/documentale/documentLibrary";

$startFolder=new CMISalfObject($repo1,$user1,$password1,null,null,$percorso1);

//using objectId instead of path: had a lot of troubles with pathnames
migrateFolder($startFolder->objId);

function migrateFolder($folderId){
	global $repo1,$repo2,$user1,$user2,$password1,$password2,$percorso1,$percorso2;

	$folder=new CMISalfObject($repo1,$user1,$password1,$folderId);
	$path=$folder->properties['cmis:path'];
	echo "\n=================================\n";
	echo "\nENTERING FOLDER $path\n";
	echo "\n=================================\n";

	if(!$folder->loaded) die("\nSORRY! cannot open folder!\nThe last HTTP request returned the following status: ".$folder->lastHttpStatus."\n\n");
	$folder->quickListContent();

	foreach($folder->containedObjects as $obj){
		//I have to load original object to check a lot of things....
		$repo1obj=new CMISalfObject($repo1,$user1,$password1,null,$obj->objUrl);

		//IF YOU WISH TO EXCLUDE SOME FOLDERS..........
		//if($repo1obj->properties['cmis:name']=="inbound FAX")return FALSE;
		
		echo "------------------------------------\n";
		echo "### ".$repo1obj->properties['cmis:name']." (".$repo1obj->properties['cmis:baseTypeId'].")\n";
		$relpath=str_replace($percorso1,"",$folder->properties['cmis:path'])."/".$repo1obj->properties['cmis:name'];
		echo "### RELATIVE PATH: $relpath \n\n ";

		//check if object is present on repo2
		$newobj=new CMISalfObject($repo2,$user2,$password2,null,null,$percorso2.$relpath);
		if($newobj->loaded){//OBJECT is already present
			echo "\nFOUND on REPO2!!!\n";				
			//CHECK FOR LAST MODIFICATION
				$repo1LastMod=$repo1obj->properties['cmis:lastModificationDate'];				
			$repo2LastMod=$newobj->properties['cmis:lastModificationDate'];
			echo "Last mod: $repo1LastMod -> $repo2LastMod\n";
			if(strcmp($repo1LastMod,$repo2LastMod)>0){
				//PAY ATTENTION to the time zone!!! It is not checked
				//PAY ATTENTION: working with documents NOT with folders! (cannot delete a nonempty folder)
				echo "DOCUMENT RECENTLY MODIFIED, REPLACING IT!\n";
				$newobj->delete();
				$newobj->loaded=FALSE;//force object reloading see following lines....
			}
		}
		if(!$newobj->loaded){//object not present on REPO2
			echo "\nNOT PRESENT on REPO2!!!\n";
			//CREATE THE OBJECT
			$folderOnRepo2=substr($percorso2.$relpath,0,(strlen($percorso2.$relpath)-strlen($repo1obj->properties['cmis:name'])));
			echo "CREATE IN $folderOnRepo2\n";
			//get the PARENT folder on REPO2
			$newObjectContainer=new CMISalfObject($repo2,$user2,$password2,null,null,$folderOnRepo2);
			if(!$newObjectContainer->loaded)die("\n\nFATAL: cannot find parent on repo2\n\n");
			//IT is a FOLDER
			if($repo1obj->properties['cmis:baseTypeId']=="cmis:folder"){
				//create it!
				$newObjectId=$newObjectContainer->createFolder($obj->title);
				$newObject=new CMISalfObject($repo2,$user2,$password2,$newObjectId);
			}
			//IT is a document
			else {	
				//download file from repo1	
				$file=$repo1obj->download();
				echo "   @@@@Downloading $file\n";
				//upload to repo2
				$newObjectId=$newObjectContainer->upload($file);
				unlink($file);
				$newObject=new CMISalfObject($repo2,$user2,$password2,$newObjectId);				
			}	
			echo "SETTING aspects\n";
			//Should be implemented in order to scan all elements in array..... too lazy to implement it now!
			//Generates a warning if aspect not found. Too lazy to fix it!!! :-)
			if ($description= $repo1obj->aspects['cm:description']) $newObject->setAspect("cm:description",$description);
			if ($author= $repo1obj->aspects['cm:author']) $newObject->setAspect("cm:author",$author);
			if ($title= $repo1obj->aspects['cm:title']) $newObject->setAspect("cm:title",$title);
			if ($summary= $repo1obj->aspects['cm:summary']) $newObject->setAspect("cm:summary",$summary);
			}//END creating object
			if($repo1obj->properties['cmis:baseTypeId']=="cmis:folder")
			//RECURSIVELY call itself! Take care of reserving enough memory for complex trees!!!!!!
			migrateFolder($repo1obj->properties['cmis:objectId']);
	}

}



?>
