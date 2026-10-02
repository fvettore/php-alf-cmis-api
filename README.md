# Alfresco PHP CMIS Client

A small, dependency-free PHP client for the **Alfresco CMIS AtomPub binding**.

Originally hosted on Google Code (`code.google.com/p/php-alf-cmis-api`).

## Introduction

When Alfresco 4.0 came out I couldn't find a working, maintained PHP client for its CMIS
implementation (nothing seemed to be maintained outside the big Java world), so I wrote one.

It has been used in production for years: to migrate whole sites (thousands of documents and
folders) from an old Alfresco 3.x repository to a new installation, and to keep company
folders in sync with an ERP. A simple migration script is included in the examples.

## Features

- Open objects by **id**, **URL** or **path**
- List folder content (full or "quick" listing)
- Create folders
- Upload and download documents
- Delete objects
- Edit properties
- Edit **aspects** such as `cm:title` and `cm:description` (the real reason I wrote it 😉)
- Simple CMIS queries on the repository

## Compatibility

### Alfresco

| Alfresco version | Endpoint | Status |
|---|---|---|
| Community **26.x** (tested on 26.2) | `http://<host>:8080/alfresco/cmisatom` | ✅ read operations tested (repository, object by path, folder listing, aspects) |
| Community 26.x | `http(s)://<host>/alfresco/api/-default-/public/cmis/versions/1.0/atom` | ✅ read operations tested |
| 5.x | `http://<host>:8080/alfresco/cmisatom` | ✅ used in production for years |
| 4.x | `http://<host>:8080/alfresco/cmisatom` | ✅ original target |
| older (3.x) | `http://<host>:8080/alfresco/service/api/cmis` | ⚠️ partial: browsing and reading objects/aspects only |

> The legacy `/alfresco/cmisatom` endpoint is still available in Alfresco 26.x, so existing
> scripts usually keep working by changing only the host name.

### PHP

| PHP version | Status |
|---|---|
| **8.3** | ✅ tested with Alfresco 26.2 |
| 8.0 – 8.2 | ✅ expected to work (same fixes as 8.3) |
| 7.4 | ✅ used in production |
| 5.3 – 5.6 | ✅ used in production; code kept syntax-compatible with 5.3 |

The library deliberately avoids newer PHP syntax (no `??`, no short array `[]`, no type
declarations) so that the **same file runs on old and new servers**.

## Requirements

- PHP 5.3 or later
- PHP extensions: **curl**, **SimpleXML** / **xml**

## Installation

Copy `Alfresco_CMIS_API.php` into your project and include it:

```php
<?php
require_once "Alfresco_CMIS_API.php";
```

## Quick start

```php
<?php
require_once "Alfresco_CMIS_API.php";

$repoUrl  = "http://alfresco.example.com:8080/alfresco/cmisatom";
$user     = "myuser";
$password = "mypassword";

// connect to the repository
$repo = new CMISalfRepo($repoUrl, $user, $password);
if (!$repo->connected) die("Cannot connect: HTTP " . $repo->lastHttpStatus . "\n");

// open a folder by path
$folder = new CMISalfObject($repoUrl, $user, $password, null, null,
                            "/Sites/mysite/documentLibrary/Customers");
if (!$folder->loaded) die("Folder not found: HTTP " . $folder->lastHttpStatus . "\n");

// list its content
$folder->quickListContent();
foreach ($folder->containedObjects as $obj) {
    echo $obj->title . " (" . $obj->type . ")\n";
}

// create a sub-folder and set title/description aspects
$newId     = $folder->createFolder("C0001");
$newFolder = new CMISalfObject($repoUrl, $user, $password, $newId);
$newFolder->setAspect("cm:title", "ACME Ltd");
$newFolder->setAspect("cm:description", "ACME Ltd");

// upload a document into it
$docId = $newFolder->upload("/tmp/offer.pdf", "application/pdf");

// run a CMIS query
$result = $folder->query("SELECT * FROM cmis:document WHERE cmis:name LIKE 'offer%'");
while ($row = $result->fetch_array()) {
    print_r($row);
}
```

### Main classes and methods

| Class | Method | Description |
|---|---|---|
| `CMISalfRepo` | `__construct($url, $user, $password)` | connects and reads the service document (`$connected`, `$repoId`, `$rootFolderId`) |
| `CMISalfObject` | `__construct($url, $user, $password, $objId, $objUrl, $objPath)` | loads an object by id, URL or path (`$loaded`, `$properties`, `$aspects`) |
| | `listContent()` / `quickListContent()` | fills `$containedObjects` (full objects / light entries) |
| | `createFolder($name)` | creates a sub-folder, returns its id |
| | `upload($filename, $mimetype = null)` | uploads a file, returns the new document id |
| | `getContent()` | returns the document content as a string |
| | `download()` | saves the content in the current directory (file name = `cmis:name`), returns the file name |
| | `setAspect($aspect, $value)` | sets an aspect property, e.g. `cm:title` |
| | `delete()` | deletes the object |
| | `query($cmisQuery)` + `fetch_array()` | CMIS query with paging (`$maxItems`, `$skipCount`, `$num_rows`) |

## Examples

The [`examples`](examples/) folder contains ready-to-use command line scripts:

| Script | Purpose |
|---|---|
| `folderList.php` / `quickFolderList.php` | list a folder |
| `documentUpload.php` | upload a file and set its title |
| `documentDownload.php` | download a document and print properties/aspects |
| `objectDelete.php` | delete an object |
| `query.php` | run a CMIS query |
| `migrateFolder.php` | copy a folder tree between two repositories |
| `repobrowser/` | minimal web repository browser |

```sh
php examples/quickFolderList.php http://localhost:8080/alfresco/cmisatom admin password /
```

## Notes for modern Alfresco installations

- **Links follow the request.** Alfresco builds the links in its responses from the host and
  scheme used in the request. If you call it through a reverse proxy, make sure the proxy
  forwards the right `Host` / `X-Forwarded-*` headers, otherwise the client will follow links
  to an unreachable address.
- **HTTPS with a private CA.** The library uses the system CA store of curl. Add your internal
  CA to the system trust store (e.g. `/etc/pki/ca-trust/source/anchors` + `update-ca-trust`).
- **Very old clients.** Systems with an old TLS stack (for example CentOS 6, curl 7.19 / NSS 3.19)
  cannot negotiate TLS with current HTTPS servers. Keep an internal HTTP endpoint for them
  (restricted to the internal network) or update their TLS libraries.

## Known limitations

- Parameters are not sanitized: special characters may break the XML posted to the server.
- Error handling is minimal: check `$loaded`, `$connected` and `$lastHttpStatus`.
- Only the AtomPub binding is supported (no Browser/JSON binding).

## Changelog

- **2026** – PHP 8 compatibility, still compatible with PHP 5.3:
  - `#[\AllowDynamicProperties]` on both classes (no deprecation notices on PHP 8.2+;
    on PHP 5/7 the attribute line is just a comment);
  - `listContent()` / `quickListContent()` create the result objects explicitly
    (PHP 8 no longer creates objects implicitly);
  - tested with Alfresco Community 26.2.
- **2013 – 2015** – original development (Alfresco 4.x / 5.x).

## Contributing

Issues and pull requests are welcome. If you wish to join, don't hesitate to contact me!

## Thanks

- CIFARELLI S.p.A.
- Alfresco

## License

GNU General Public License v3 or later – see the header of `Alfresco_CMIS_API.php`.

The code is free even for commercial use; donations (PayPal: fabrizio [at] vettore.org) are
appreciated 🙂
