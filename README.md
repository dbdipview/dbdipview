# dbDIPview

A web viewer solution for long-term digital preservation of databases and retired applications. The archived database is preserved and additional representation information enables further access to the data via predefined user-friendly menus.

### Prerequisites
The archived databases are stored in SIARD (XML) or dbDIPview (CSV) format. For access, they can quickly be made available on user request or kept up and running as persistent (reusable) DIPs. To enable access to a database we use the administration menu to deploy two packages: the Archival Information Package (AIP) with database content, and the corresponding Representation Information Package. Both packages need to be delivered to or kept available in the defined dbDIPview folder. The complete installation can be done with a single command, typically as the last step of the ordering process in the archival reading room.

## Technology
The tool is based on Linux, Apache, PHP, and PostgreSQL. Its relative simplicity ensures long-term code maintainability as a prerequisite for use in archives.

## The Modules
* Packager (creation of a representation information package without or with database content)
* Administration tool (deployment of databases and viewers)
* Access for users (database selection menu, report menu, search window)

For more information, check out [Wiki](../../wiki).

## Versioning
See CHANGELOG for changes. The master tree is a stable version. To upgrade to the latest version with new functionality from the master branch, use git clone command, or unpack the zip file over the current installation. Your configuration settings will remain unchanged.

## Authors
* Boris Domajnko - *Initial work and maintenance*, [Archives of the Republic of Slovenia](http://www.arhiv.gov.si/en/)
