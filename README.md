# dbDIPview

"Database DIP viewer" is a production ready solution for long-term digital preservation of databases and retired applications. It consists of a packager, installer and viewer. For an archived and preserved database, additional representation information enables further access to the data via predefined user-friendly menus.

### Prerequisites
The archived databases are stored in SIARD (XML) or dbDIPview (CSV) format. For access, they can quickly be made available on user request or kept up and running as persistent (reusable) DIP according to Open Archival Information System (OAIS) functional model. To enable access to a database, Archival Information Package (AIP) deployment is done with two packages: database content, and the corresponding Representation Information Package. Both packages need to be delivered to or kept available in the predefined dbDIPview folder. Then, the complete installation can be done with a single command, typically as the last step of the ordering process in the archival reading room.

## Technology
The tool is based on Linux, PHP, PostgreSQL, and Apache. Its relative simplicity and low dependency on external frameworks ensure long-term code maintainability as a prerequisite for use in the archives.

## The Modules
* Packager (creation of a Representation Information Package without or with database content)
* Administration tool (deployment of databases and viewers)
* Access for users (database selection menu, report selection menu, search form, report)

For more information, check out [Wiki](../../wiki).

## Versioning
See CHANGELOG for changes. The master tree is a stable version. To install use git clone command. To upgrade to the latest version with new functionality from the master branch, use git pull origin master --verbose, or unpack the zip file over the current installation. Your configuration settings will remain unchanged.

## Authors
* Boris Domajnko - *Initial work, maintenance, and assistance
