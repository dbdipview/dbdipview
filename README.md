# dbDIPview

A viewer solution for long-term digital preservation of databases. Instead of archiving the whole application, we keep the archived database and the rendering information.

### Prerequisites
The archived databases are stored in SIARD or dbDIPview (based on CSV) format. For access, they can quickly be made available on user request or kept up and running as persistent (reusable) DIPs. dbDIPview can run on a dedicated server with a DBMS. To enable access to a database we use the administration menu to deploy two packages: the Archival Information Package (AIP) with database content, and the corresponding access enabling information package. Both packages need to be delivered to or kept available in the defined dbDIPview folder.

## Technology
The tool is based on Linux, Apache, PHP, and PostgreSQL. Its relative simplicity ensures long-term code maintainability as a prerequisite for use in archives. 

## The Modules
* Packager (creation of a viewer package without or with database content)
* Administration tool (deployment of databases and viewers)
* Access for users (database selection and use of preconfigured access to the content)

## Versioning
See CHANGELOG for changes. The master tree is a stable version. To upgrade to the latest version with new functionality from the master branch, use git commands, or unpack the zip file over the current installation. Your configuration settings will remain unchanged.

## Authors
* Boris Domajnko - *Initial work and maintenance*, [Archives of the Republic of Slovenia](http://www.arhiv.gov.si/en/)
