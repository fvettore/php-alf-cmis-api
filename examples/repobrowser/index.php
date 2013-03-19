<?php
/***********************************************************************************
*	This small piece of code shows how it is simple to implement
*	a working repo-browser using this API
*
*	Useful for development too: it shows node ID for every object listed....
*
*	USAGE: simply copy this folder on a reachable folder fo your webserver
*	(ensure your webserver can access to your alfresco server)
*	Advanced functions like DELETE and MODIFY documents can be easilly added
************************************************************************************/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="it-IT">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="author" content="Fabrizio Vettore/">
	<title>PHP CMIS API - repo browser</title>
</head>

<body>

<?php
require_once "../../Alfresco_CMIS_API.php";
session_start(); 

if($_GET['logout'])session_unset();

if($_POST['username']){
	$_SESSION['username']=$_POST['username'];
	$_SESSION['password']=$_POST['password'];
	$_SESSION['repourl']=$_POST['repourl'];
}

if (!isset($_SESSION['username'])){

?>
<center>
</br>
<form  action="<?echo $_SERVER['PHP_SELF'];?>" method="POST" >
Username: <input type="TEXT" name="username" size="80"></br></br>
Password: <input type="TEXT" name="password" size="80"></br></br>
RepoURL: <input type="TEXT" name="repourl" value="http://localhost:8080/alfresco/cmisatom" size="80"></br>
(for document downloading please replace <em>localhost</em> with the FQDN of your server)</br></br>
<input type="submit" value="SUBMIT"></br>
</form>
</center>
<?

}

else {
	if(!isset($_GET['objId'])){
	 	$repo=new CMISalfRepo($_SESSION['repourl'],$_SESSION['username'],$_SESSION['password']);
		$objId=$repo->rootFolderId;
	}	
	else {
		$objId=$_GET['objId'];
	}
	$repoObject=new CMISalfObject($_SESSION['repourl'],$_SESSION['username'],$_SESSION['password'],$objId);
	echo "<h2>".$repoObject->properties['cmis:name']."</h2>\n";
	echo "<h3>".$repoObject->aspects['cm:title']."</h3>";
	echo "<h3>".$repoObject->aspects['cm:author']."</h3>";
	echo $repoObject->aspects['cm:description']."</br>";

	echo "<h3>$objId</h3>\n";
	echo "<hr><b>Properties:</b></br>";
	echo "<pre style=\"font-size:.8em;\">";
	print_r($repoObject->properties);
	echo "</pre>";
	echo "<b>Aspects:</b></br>";
	echo "<pre style=\"font-size:.8em;\">";
	print_r($repoObject->aspects);
	echo "</pre>";
	echo "<hr><hr><h3>Contents:</h3>";

	if($repoObject->properties['cmis:baseTypeId']=="cmis:folder"){
		//It is a folder or something similar
		$repoObject->listContent();
		foreach($repoObject->containedObjects as $object){
			echo "<hr>";
			if($object->properties['cmis:objectTypeId']=="cmis:folder"){
				echo "<a href=\"".$_SERVER['PHP_SELF']."?objId=".$object->properties['cmis:objectId']."\">";
				echo "<img src=\"./img/folder.gif\"></a>";
			}
			if($object->properties['cmis:objectTypeId']=="F:st:site" || $object->properties['cmis:objectTypeId']=="F:st:sites"){
				echo "<a href=\"".$_SERVER['PHP_SELF']."?objId=".$object->properties['cmis:objectId']."\">";
				echo "<img src=\"./img/world1.gif\"></a>";
			}
			if($object->properties['cmis:objectTypeId']=="cmis:document"){
				echo "<a href=\"".$_SERVER['PHP_SELF']."?objId=".$object->properties['cmis:objectId']."\">";
				echo "<img src=\"./img/generic.gif\"></a>";
			}
			echo "\n<b>".$object->title."</b><br>";
			echo "\n".$object->aspects['cm:title']."<br>";
			echo "\n<span style=\"font-size: 0.8em\">".$object->aspects['cm:description']."</span><br>";
			echo "\n<span style=\"font-size: 0.8em\">Object ID:".$object->properties['cmis:objectId']."</span><br>";

		}

	}
	
	else if($repoObject->properties['cmis:baseTypeId']=="cmis:document"){
		//It is a document or something similar
		echo "<a href=\"".$repoObject->contentUrl."\">";
		echo "Download: <img src=\"./img/generic.gif\"></a></br>";
	}
}

if(isset($_SESSION['username'])) echo "<hr><a href=\"".$_SERVER['PHP_SELF']."?logout=1\">logout</a>"; 
?>
</body>
</html>
