# dbDIPview

A viewer solution for long-term digital preservation of databases.

### Prerequisites
The databases can quickly be made available for access on user request or kept up and running as persistent (reusable) DIP-s. dbDIPview can run on a dedicated server with a DBMS. For a start, we use the administration menu to deploy two packages: the Archival Information Package (AIP) with database content, and the corresponding access enabling information package. Both packages need to be delivered to or kept available in the defined dbDIPview folder.

## Technology
The tool is based on Linux, Apache, PHP, and PostgreSQL. Its relative simplicity ensures long-term code maintainability as a prerequisite for use in archives. 

## The Modules
* Packager (creation of a viewer package with or without database content)
* Administration tool (deployment of databases and viewers)
* Access for users (database selection and use of preconfigured access to the content)

## Versioning
See CHANGELOG for changes. The master tree is a stable version. To upgrade to the latest version with new functionality from the master branch, unpack the zip file over the current installation, or use git commands. In this way, your configuration files will remain unchanged.

## Authors
* Boris Domajnko - *Initial work and maintenance*, [Archives of the Republic of Slovenia](http://www.arhiv.gov.si/en/)
