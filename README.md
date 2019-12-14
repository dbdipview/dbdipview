# dbDIPview

A viewer solution for long-term digital preservation of databases.

### Prerequisites
The databases can quickly be made available for access on user request or kept up and running as a persistent (reusable) DIP, when
* a dedicated server is kept available with a DBMS and dbDIPview, and
* the Archival Information Packages (AIP) with database content and the corresponding access enabling information package need to be delivered or kept available in the defined dbDIPview folder.


## Technology
The tool is based Linux, PHP, Apache nad PostgreSQL.

If this is a topic of your interest, you are probably already familiar with some variant of long-term preservation tools for databases, like SIARD Suite, DBPTK/DBVTK, CSV/TSV or ADDML.

## The Modules
* Packager (creation of a package with or without database content as CSV data, XML schema validation)
* Administration tool (creation of an empty database and restoration of its content, access activation)
* Browser access for users (selection of a desired database and schema, execution of the available queries)

## Versioning
The master tree is a stable version. No specific version numbers are used for the time being. To upgrade to the latest version with new functionality from the master branch, unpack the zip file over the current installation. In this way, your configuration files will remain unchanged.

## Authors
* Boris Domajnko - *Initial work and maintenance*, [Archives of the Republic of Slovenia](http://www.arhiv.gov.si/en/)
