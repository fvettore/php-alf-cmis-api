<?php
/**************************************************************************
*	ALFRESCO PHP CMIS API
*	© 2013 by Fabrizio Vettore - fabrizio(at)vettore.org
*	V0.1a
*
*	BASIC repo and object handling:
*	Create, upload, download, delete, change properties.
*	Can change basic ASPECTS like TITLE and DESCRIPTION
*	(this is the real reason why i wrote it ;-))
*
*	COMPATIBILTY:
*	ALFRESCO 4.x with cmisatom binding
*	(url like: http://alfrescoserver:8080/alfresco/cmisatom)
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
**************************************************************************/

//MAIN CLASS FOR HANDLING THE REPO 
class CMISalfRepo
{
	var $username;
	var $url;
	var $password;
	public $repoId;//Very important
	public $rootFolderId;//can be useful
	public $connected=FALSE;
	//last http reply for debugging purpose can be accessed by the program
	public $lastHttp;


function __construct($url, $username = null, $password = null){
	$this->url=$url;
	$this->connect($url, $username, $password);
}


//CONNECTION to the repo to get basic data
function connect($url, $username, $password){
	$this->url=$url;
	//try to connect
	$ch=curl_init();
	$reply=$this->getHttp($url,$username,$password);
	$this->lastHttp=$reply;
	//complex handling of different namespaces returned;
	//ATOM->APP->CMISRA
//	echo $reply;
	$repodata=simplexml_load_string($reply);
//	print_r($repodata);
	if($repodata==FALSE){
//	return FALSE;
	}
	$this->namespaces=$repodata->getNameSpaces(true);
	if($app=$repodata->children($this->namespaces['app']))
		$cmisra=$app->children($this->namespaces['cmisra']);
	else $cmisra=$repodata->children($this->namespaces['cmisra']);
	$cmis=$cmisra->children($this->namespaces['cmis']);
	$this->rootFolderId=$cmis->rootFolderId;
	$this->repoId=$cmis->repositoryId;
	$this->cmisobject=$cmis;
	$this->connected=TRUE;
	return$this->repoId;
}


//Handles HTTP requests with GET method
function getHttp($url, $username, $password){
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE ); 
	curl_setopt($ch, CURLOPT_USERPWD,"$username:$password");
	$reply=curl_exec($ch);
	$this->lastHttp=$reply;
	if(curl_errno($ch)){
		echo curl_error($ch);
		return FALSE;
	}
	else return $reply;
	}

//Handles HTTP requests with POST method
function postHttp($url, $username, $password,$postfields){

	$ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE ); 
	curl_setopt($ch, CURLOPT_POST, TRUE ); 
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");//probably unnecessary
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/atom+xml;type=entry"));
	curl_setopt($ch, CURLOPT_USERPWD,"$username:$password");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields); 
	$reply=curl_exec($ch);
	$this->lastHttp=$reply;
	if(curl_errno($ch)){
		echo curl_error($ch);
		return FALSE;
	}
	else return $reply;

	}

//Handles HTTP requests with PUT method
function putHttp($url, $username, $password,$postfields){

	//found no other way for the PUT method to work fine other than putting a real file.
	//May be there is a better solution.....
	$fp=fopen("put.xml","wb+");
	if(!$fp){
		echo "CANNOT open file! Please check for folder write permission\n\n";
		return FALSE;
	}
	fwrite($fp,$postfields);
	fclose($fp);//reopening for curl INFILE
	$fp=fopen("put.xml","rb+");
	$ch=curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE ); 
	curl_setopt($ch, CURLOPT_PUT, TRUE ); 
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");//probably unnecessary
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($postfields),"X-HTTP-Method-Override: PUT","Content-Type: application/atom+xml;type=entry"));
	curl_setopt($ch, CURLOPT_USERPWD,"$username:$password");
	$reply=curl_exec($ch);
	$this->lastHttp=$reply;
	fclose($fp);
	unlink("put.xml");
	if(curl_errno($ch)){
		echo curl_error($ch);
		return FALSE;
	}
	else return $reply;
	}

//Handles HTTP requests with DELETE method
function deleteHttp($url, $username, $password){
	$ch=curl_init($url);
	curl_setopt($ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE ); 
	curl_setopt($ch, CURLOPT_USERPWD,"$username:$password");
	$reply=curl_exec($ch);
	$this->lastHttp=$reply;
	if(curl_errno($ch)){
		echo curl_error($ch);
		return FALSE;
	}
	else return $reply;

	}


//END of CLASS
}

//OBJECT class is an extension of the REPo ABOVE
class CMISalfObject extends CMISalfRepo
{

//some properties accessible from the program after loading object
public $properties=array();
public $aspects=array();
public $objLoaded=FALSE;
public $objId;
public $contentUrl;
//A complete list of all contained objects with their properties and aspects
//Must be initialized with listContent() method.
public $containedObjects=array();

function __construct($url, $username = null, $password = null,$objId = null,$objUrl=null,$objPath=null){
	$this->url=$url;
	$this->connect($url, $username, $password);
	$this->username=$username;
	$this->password=$password;
	if($objUrl){
		$this->loadCMISObject(null,$objUrl);
	}
	else if($objId){
		$this->loadCMISObject($objId);
	}
	else if($objPath){
		$this->loadCMISObject(null,null,$objPath);
	}
	else return FALSE;
}


//Loads object by ID or by URL with PROPERTIES and ASPECTS
function loadCMISObject($objId=null,$objUrl=null,$objPath=null){
	$this->objId=$objId;
	$this->objUrl=$objUrl;
	
	//BE CAREFUL to access objects directly by their SELF url or PATH....
	if($objUrl){
		$reply=$this->getHttp($objUrl,$this->username,$this->password);
	}
	else if($objPath){
		$url=$this->pathUrl($objPath);
		$reply=$this->getHttp($url,$this->username,$this->password);
	}
	else {
		//Get object info under ENTRY
		$newurl=$this->entryUrl($objId);
		$reply=$this->getHttp($newurl,$this->username,$this->password);
	}

	$objdata=simplexml_load_string($reply);
	if($objdata==FALSE){
//			return FALSE;
	}

	//very complex handling of different namespaces returned;
	//ATOM->CMISRA-> CMIS -> ASPECTS
	$atom=$objdata->children($this->namespaces['atom']);
	$this->namespaces=$objdata->getNameSpaces(true);
	$cmisra=$objdata->children($this->namespaces['cmisra']);
	$cmis=$cmisra->children($this->namespaces['cmis']);
	$this->cmisobject=$cmis;
	$this->objLoaded=TRUE;
//	print_r($cmis);
//	print_r($this->namespaces);
	if($atom->content)$this->contentUrl=(string)$atom->content->attributes()->src;//useful for downloading content
//	print_r ($app);

	foreach($objdata->link as $link){
	$rel= $link->attributes()->rel;
	$href= $link->attributes()->href;
	$type= $link->attributes()->type;
		if($rel=="down"){	
			echo "\nCHILDREN: $href\n";
		}
		else if($rel=="up"){	
			echo "\nPARENT: $href\n";
		}
	}

	//Getting object PROPERTIES with different attributes
	//PropertyString
	for($x=0;$x<count($cmis->properties->propertyString);$x++){
		$propertyDefinitionId=$cmis->properties->propertyString[$x]->attributes()->propertyDefinitionId;
		$value=(string)$cmis->properties->propertyString[$x]->value;
		$this->properties["$propertyDefinitionId"]=$value;
	}			
	//PropertyId				
	for($x=0;$x<count($cmis->properties->propertyId);$x++){
		$propertyDefinitionId=$cmis->properties->propertyId[$x]->attributes()->propertyDefinitionId;
		$value=(string)$cmis->properties->propertyId[$x]->value;
		$this->properties["$propertyDefinitionId"]=$value;
	}			
	//PropertyDateTime
	for($x=0;$x<count($cmis->properties->propertyDateTime);$x++){
		$propertyDefinitionId=$cmis->properties->propertyDateTime[$x]->attributes()->propertyDefinitionId;
		$value=(string)$cmis->properties->propertyDateTime[$x]->value;
		$this->properties["$propertyDefinitionId"]=$value;
	}			
	//Getting object ASPECTS
	//Driving me crazy with NESTED NAMESPACES :-)
	$aspectsdata=$cmis->children($this->namespaces['aspects'])->aspects;
	for($x=0;$x<count($aspectsdata->properties);$x++){
		$cmisprop=$aspectsdata->properties[$x]->children($this->namespaces['cmis']);
		if($n=count($cmisprop)){
			for($k=0;$k<$n;$k++){
				$propertyDefinitionId=$cmisprop[$k]->attributes()->propertyDefinitionId;
				$value=(string)$cmisprop[$k]->value;
				if($value)$this->aspects["$propertyDefinitionId"]=$value;
			}	
		}		
	}
	$this->objId=$this->properties['cmis:objectId'];
}
	
//Initializes content array (for easy access to the contained objects)
//Useful for browsing a folder in a repo
public function listContent(){
	//note the check on baseTypeId instead of objectId in order to include sites....
	if($this->properties['cmis:baseTypeId']<>"cmis:folder"){	
		//NOT A FOLDER!!!!
		return FALSE;
	}
	$newurl=$this->childrenUrl($this->objId);
	$reply=$this->getHttp($newurl,$this->username,$this->password);
	$objdata=simplexml_load_string($reply);
	$this->namespaces=$objdata->getNameSpaces(true);
	//LOADING atom NAMESPACE
	$atom=$objdata->children($this->namespaces['atom']);
	//LOOKING FOR ENTRIES
	$entry=$atom->entry;
	//How many entries?
	for($x=0;$x<count($entry);$x++){
		$ent=$entry[$x];
		$link=$ent->link;
		foreach($ent->link as $link){
			//resolve all links looking for SELF
			$rel= $link->attributes()->rel;
			$href= $link->attributes()->href;
			if($rel=="self")$objUrl=$href;		
		}
		$tempdoc[$x]=new CMISalfObject($this->url,$this->username,$this->password,null,$objUrl);
		$this->containedObjects[$x]->objUrl=$objUrl;

		$this->containedObjects[$x]->author=(string)$ent->author->name;
		$this->containedObjects[$x]->title=(string)$ent->title;
		if($ent->content)$this->containedObjects[$x]->content=(string)$ent->content->attributes()->src;//useful for downloading content
		$this->containedObjects[$x]->properties=$tempdoc[$x]->properties;
		$this->containedObjects[$x]->aspects=$tempdoc[$x]->aspects;
	}
}



//RETURNS the MIME content of the current object
public function getContent(){
	$url=$this->contentUrl;
	return $this->getHttp($url, $this->username, $this->password);
}

//DOWNLOADS and saves the MIME content of the current object
public function download(){
	$url=$this->contentUrl;
	$content=$this->getHttp($url, $this->username, $this->password);
	if(!$content)return FALSE;
	//note filename=object name.
	//take care of the filename extension under windows :-)
	$name=$this->properties['cmis:name'];
	$fp=fopen($name,"wb");
	if(!$fp)return FALSE;
	fwrite($fp,$content);
	fclose($fp);
	return $name;
}

//CREATES a new folder into a folder object
//RETURNS new object id
public function createFolder($foldername){
	//note the check on baseTypeId instead of objectId in order to include sites....
	if($this->properties['cmis:baseTypeId']<>"cmis:folder"){	
		//NOT A FOLDER!!!!
		return FALSE;
	}
	//ATOM FEED for new folder	
	$inquiry="<?xml version='1.0' encoding='UTF-8'?>
<atom:entry 
        xmlns:atom=\"http://www.w3.org/2005/Atom\"  
        xmlns:cmis=\"http://docs.oasis-open.org/ns/cmis/core/200908/\"  
        xmlns:cmisra=\"http://docs.oasis-open.org/ns/cmis/restatom/200908/\"  
        xmlns:app=\"http://www.w3.org/2007/app\">
<atom:title>$foldername</atom:title>
<cmisra:object>
        <cmis:properties>
                <cmis:propertyId queryName=\"cmis:objectTypeId\" displayName=\"Object Type Id\" localName=\"objectTypeId\" propertyDefinitionId=\"cmis:objectTypeId\">
                <cmis:value>cmis:folder</cmis:value>
                </cmis:propertyId>
        </cmis:properties>
</cmisra:object>
</atom:entry>
";	
	$url=$this->childrenUrl($this->objId);
	$result=$this->postHttp($url,$this->username,$this->password,$inquiry);

	return $this->getObjectId($result);
}

//UPLOADS a new file into a folder object
//RETURNS new object id
public function upload($filename){
	//note the check on baseTypeId instead of objectId in order to include sites....
	if($this->properties['cmis:baseTypeId']<>"cmis:folder"){	
		//NOT A FOLDER!!!!
		return FALSE;
	}
	$handle = fopen($filename, "r");
	if(!$handle)return FALSE;//file not found
	$contents = fread($handle, filesize($filename));
	$type=mime_content_type($filename);
	fclose($handle);
	$base64_content=base64_encode($contents);


//ATOM FEED for new doc
$inquiry="<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
<atom:entry xmlns:cmis=\"http://docs.oasis-open.org/ns/cmis/core/200908/\" 
xmlns:cmism=\"http://docs.oasis-open.org/ns/cmis/messaging/200908/\" 
xmlns:atom=\"http://www.w3.org/2005/Atom\" 
xmlns:app=\"http://www.w3.org/2007/app\" 
xmlns:cmisra=\"http://docs.oasis-open.org/ns/cmis/restatom/200908/\"> 
<atom:title>$filename</atom:title>
	<cmisra:content>
		<cmisra:mediatype>$type</cmisra:mediatype>
                        <cmisra:base64>$base64_content</cmisra:base64>
		</cmisra:content>
	<cmisra:object>
		<cmis:properties>
	        <cmis:propertyId propertyDefinitionId=\"cmis:objectTypeId\">
		<cmis:value>cmis:document</cmis:value>
	               </cmis:propertyId>
	        </cmis:properties>
        </cmisra:object>
</atom:entry>
";

	$url=$this->childrenUrl($this->objId);
 	$result=$this->postHttp($url,$this->username,$this->password,$inquiry);
	return $this->getObjectId($result);
}

//ASPECT set/modification on the current object
public function setAspect($aspect,$value){
	
	$inquiry="<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>
<atom:entry xmlns:cmis=\"http://docs.oasis-open.org/ns/cmis/core/200908/\"  
xmlns:cmism=\"http://docs.oasis-open.org/ns/cmis/messaging/200908/\" 
xmlns:atom=\"http://www.w3.org/2005/Atom\" 
xmlns:app=\"http://www.w3.org/2007/app\"  
xmlns:aspects=\"http://www.alfresco.org\" 
xmlns:cmisra=\"http://docs.oasis-open.org/ns/cmis/restatom/200908/\"> 
   <cmisra:object>
       <cmis:properties>
           <aspects:setAspects>
               <aspects:properties>
                   <cmis:propertyString propertyDefinitionId=\"$aspect\" queryName=\"$aspect\">
                        <cmis:value>$value</cmis:value>
                   </cmis:propertyString>
               </aspects:properties>
           </aspects:setAspects>
       </cmis:properties>
    </cmisra:object>
</atom:entry>
";

	$url=$this->entryUrl($this->properties['alfcmis:nodeRef']);
	$result=$this->putHttp($url,$this->username,$this->password,$inquiry);
	//reload modified object
	$this->loadCMISObject($this->properties['alfcmis:nodeRef']);
}



//DELETES node
public function delete(){
	$url=$this->entryUrl($this->properties['alfcmis:nodeRef']);
	$result=$this->deleteHttp($url,$this->username,$this->password);
	//reload modified object
}


//returns object id fom a XML node
function getObjectId($node){
	$objdata=simplexml_load_string($node);
	if($objdata==FALSE){
//			return FALSE;
	}
	//GETS objecId from returned XML
	//very complex handling of different namespaces returned;
	//ATOM->CMISRA-> CMIS -> ASPECTS
	$namespaces=$objdata->getNameSpaces(true);
	$cmisra=$objdata->children($namespaces['cmisra']);
	$cmis=$cmisra->children($namespaces['cmis']);
	for($x=0;$x<count($cmis->properties->propertyId);$x++){
		$propertyDefinitionId=$cmis->properties->propertyId[$x]->attributes()->propertyDefinitionId;
		$value=(string)$cmis->properties->propertyId[$x]->value;
		if($propertyDefinitionId=="cmis:objectId") return $value;
	}			
	return FALSE;//not found (is it possible???? :-) )
}


//THE FOLLOWING ARE COMPATIBLE WITH ALFERSCO 4 CMISATOM IMPLEMENTATION ONLY

//returns the ENTRY url based on object ID
function entryUrl($objId){
	$newurl=$this->url."/".$this->repoId."/entry?id=".urlencode($objId);
	return $newurl;
}
//returns the CHILDREN url based on object ID
function childrenUrl($objId){
	$newurl=$this->url."/".$this->repoId."/children?id=".urlencode($objId);
	return $newurl;
}
//returns the CONTENT url based on object ID
function contentUrl($objId){
	$newurl=$this->url."/".$this->repoId."/content?id=".urlencode($objId);
	return $newurl;
}
//returns the PARENT url based on object ID
function parentUrl($objId){
	$newurl=$this->url."/".$this->repoId."/parent?id=".urlencode($objId);
	return $newurl;
}
//returns the PATH url based on path
function pathUrl($path){
	$newurl=$this->url."/".$this->repoId."/path?path=".urlencode($path);
	return $newurl;
}

//END of CLASS
}

?>