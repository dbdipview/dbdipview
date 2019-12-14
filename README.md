# dbDIPview

A viewer solution for long-term digital preservation of databases.

### Prerequisites
The databases can quickly be made available for access on user request or kept up and running as persistent (reusable) DIP-s. dbDIPview can run on a dedicated server with a DBMS. For a start, it deploys two packages: the Archival Information Package (AIP) with database content, and the corresponding access enabling information package. Both packages need to be delivered or kept available in the defined dbDIPview folder.

## Technology
The tool is based Linux, PHP, Apache nad PostgreSQL.

If this is a topic of your interest, you are probably already familiar with some variant of long-term preservation tools for databases, like SIARD Suite, DBPTK (Database Preservation Toolkit), or simply CSV/TSV.

## The Modules
* Packager (creation of a package with or without database content as CSV data, XML schema validation of the viewer)
* Administration tool (database deployment and access activation)
* Browser access for users (database selection and use of preconfigured access to the content)

## Versioning
The master tree is a stable version. No specific version numbers are used for the time being. To upgrade to the latest version with new functionality from the master branch, unpack the zip file over the current installation. In this way, your configuration files will remain unchanged.

## Authors
* Boris Domajnko - *Initial work and maintenance*, [Archives of the Republic of Slovenia](http://www.arhiv.gov.si/en/)
