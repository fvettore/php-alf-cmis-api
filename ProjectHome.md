# Alfesco PHP CMIS CLIENT #
## Introduction ##

I couldn't find a working/maintained PHP client for the latest Alfresco 4.0 CMIS implementation (nothing seems to be maintained outside the big java world).<br>
So I decided to create it by myself.<br>
I recently used it to transfer some sites (thousands of documents and folders) from an old Alfresco 3.x repo to a new installation and it did the job.<br>
you can find an example of a simple migration script in the <a href='https://code.google.com/p/php-alf-cmis-api/source/checkout'>source</a>.<br>
<br>
<br>
<h2>Features</h2>
Object handling: create, upload, download, delete, edit properties, <b>edit aspects</b> as <i>title</i> and <i>description</i> (this is the real reason why i wrote it ;-))<br>
<br>
<h2>Compatibility</h2>
ALFRESCO 4.x with cmisatom binding<br>
(url like: <a href='http://alfresco:8080/alfresco/cmisatom'>http://alfresco:8080/alfresco/cmisatom</a>)<br>
<br><br>
Partial compatibility  (browsing and retrieving objects and aspects is OK) with old Alfresco (under development)<br>
<br>(url like  <a href='http://alfresco:8080/alfresco/service/api/cmis'>http://alfresco:8080/alfresco/service/api/cmis</a>)<br>
<br>
<h2>Installation</h2>
Simply include the main class in your code.<br>
<br>
<h2>Requirements</h2>
PHP with CURL and XML enabled<br>
<br>
<h2>Current status</h2>
This project is at a very initial stage.<br>
You can expect it works fine most of the time but it misses a lot of functionnality.<br>
For example:<br>
<ul><li>Error handling<br>
</li><li>Parameter should be sanitized in order to avoid breaking XML posted code<br>
</li><li>SSL not tested yet.</li></ul>

<h2>Do you wish to contribute?</h2>
If you wish to join don't hexitate to contact me!<br>
<br>
<h2>Many thanks to</h2>
<a href='http://www.cifarelli.it'>CIFARELLI Spa</a> (my current company)<br>
<a href='http://alfresco.com'>Alfresco</a>

<h2>Found it useful?</h2>
The code is <b>absolutely free</b> even for commercial use, but donations will be appreciated :)<br>
<br><br>
<a href='http://us1.go2net.it/donate/'><img src='http://us1.go2net.it/donate/button.gif' /></a>