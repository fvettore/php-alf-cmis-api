
####Alfesco PHP CMIS CLIENT####
originally hosted on https://code.google.com/p/php-alf-cmis-api/

####Introduction####

I couldn't find a working/maintained PHP client for the latest Alfresco 4.0 CMIS implementation (nothing seems to be maintained outside the big java world).
So I decided to create it by myself.
I recently used it to transfer some sites (thousands of documents and folders) from an old Alfresco 3.x repo to a new installation and it did the job.
You can find an example of a simple migration script in the source.

####Features:####

#####Object handling:#####
* create
* upload
* download
* delete
* edit properties
* edit aspects as title and description (this is the real reason why i wrote it ;-))
#####Query:#####
* simple CMIS query on the repo

####Compatibility####

ALFRESCO 4.x with cmisatom binding
(url like: http://alfresco:8080/alfresco/cmisatom) 

Partial compatibility (browsing and retrieving objects and aspects is OK) with old Alfresco (under development) 
(url like http://alfresco:8080/alfresco/service/api/cmis)

####Installation####

Simply include the main class in your code.

####Requirements####

PHP with CURL and XML enabled

####Current status####

This project is at a very initial stage. You can expect it works fine most of the time but it misses a lot of functionnality.
For example:

####Error handling####

* Parameter should be sanitized in order to avoid breaking XML posted code
* SSL not tested yet.

####Do you wish to contribute?####

If you wish to join don't hexitate to contact me!

####Many thanks to####
CIFARELLI Spa (my current company)

Alfresco

####Found it useful?####

The code is absolutely free even for commercial use, but donations (paypal fabrizio [at] vettore.org) will be appreciated :) 
