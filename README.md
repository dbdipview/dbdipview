# dbDIPview

A viewer for long-term archived databases.

## The Problem Statement and Motivation
The goal of long term preservation of databases is to preserve their structure and content with the objective of keeping them available for an unlimited time, as described by the Open Archival Information System (OAIS) reference model. Here we cannot rely on original applications as their maintenance would become an impossible task throughout a longer period. Fortunately, there are tools that enable us to archive this kind of content.

Apart from preserving the data, there is also a need to provide user-friendly access to the preserved information. The simple way is to study the (archived) documentation and then use a database management tool to execute appropriate queries. For an archival reading room, this is a difficult task because a technical expert must be available to assist the end user.
 
One of the alternative solutions is to store these queries in a way that will enable the end user to execute them in a generic application. The ideal system would be technology independent, simple, understandable and with a long-expected usability time. 

## The Concept
### Preparation
* Export data from the database and create a preservation package. This is usually done by the creator (or maintainer).
* When the database content is delivered to the archives (as a SIP), the typical goal and use of the data by the future user will be defined. The starting point here is the information from the creator combined with available documentation.
* Prepare the SQL queries as part of the validation of the content in the first package. In the process, we test the quality and usability of the data when they are received in the archives. The queries are stored in an XML file.
* Create an additional package with information on how to access the data - "access enabling configuration package". Both packages are using the same schema name. 
* When the acceptance testing is completed, the packages are stored for future use - with no need for involvement of a database expert.
### Use
* In a reading room environment, restore the database by deploying the database preservation package to a database management system (DBMS).
* Activate the access package. More than one database can be activated and handled by the same tool in a DBMS - the user simply selects the target database.
* As a consequence, the user gets access to a list of configured reports for a selected database. For each report a menu is available where search terms can be entered in different forms and combinations (basic AND, OR, NOT). Also, drop-down menus with table data can be used with the possibility of multiple selections, as well as inline help for a specific input field. 
* In the results pane, for a single report output of one or more different queries may be displayed, and each is preceded by a title and a subtitle (or short explanation text). The lines may be sorted based on a selected column, columns may be removed. It is possible to link a column value as a parameter to another report. 

## The Benefits
* PHP code of the access module can be modified if the technology changes
* PHP code simplifies security audit
* The quality of the ingested data can be tested during the ingest process
* No further expert involvement is needed for future use of the content
* Configurable interface enables mimicking the reporting part of the original application
* User-friendly reports
* It is possible to combine and link the results (i.e. jump from one report to another one with detailed information)
* Links to external files are possible from column data
* Simple technology minimizes the dependencies and increases supportability for long-term use
* GUI translations are possible
* Database restoration using SIARD format is possible with the tool
* Restoration using CSV/TSV content is also possible as an alternative (here the content and access information are in the same package)
### Prerequisites
* A dedicated server is kept available with a DBMS and dbDIPview. 
* The archival information packages (AIP) with database content and the corresponding access enabling information package need to be delivered or kept available in the defined dbDIPview folder.
* The databases can quickly be made available for access on user request or kept up and running as a persistent (reusable) DIP.

## Technology
The tool is based Linux, PHP, Apache nad PostgreSQL.

If this is a topic of your interest, you are probably already familiar with some variant of long-term preservation methods and tools for databases, like SIARD Suite, DBPTK/DBVTK, CSV/TSV or ADDML.

## The Modules
* Packager (creation of a package with or without database content as CSV data, XML schema validation)
* Administrative tool (creation of an empty database and restoration of its content, access activation)
* End-user web access (selection of a desired database and schema, execution of the available queries)

### First Installation
The environment uses Linux (Ubuntu 18.04 LTS) with PostgreSQL, and the dbDIPview code is installed on the same server. First, we set up the configuration.

```
# downloaded from github, move the dbdipview to the target server...
# in postgres create two accounts: admin user and a read-only access user...
#   copy the installation package...
#   on the server, choose the direcory, e.g. cd ~
# take care about apache DocumentRoot (dbdipview/www)...
# in dbdipview/admin and dbdipview/www rename the *.txt.template files to *.txt...
# edit the configuarion settings... 
# run the administrative tool to check the folders
cd dbdipview/admin
php menu.php
```

### Creation of an access enabling dbDIPview package
* Create an XML file with your first query. The XML template is in the folder ```dbdipview/doc```.
* Build the dbDIPview package. Note that the package can optionally include the complete database content (CSV). With data in the work folder ```dbdipview/records``` use this command:

```
cd dbdipview/packager
./createPackage.sh
```

### Deployment of a single database
Here we use the administration menu that allows us to do step by step operations like:
* Define the target database.
* Select the desired package.
* Restore the database content (using the SIARD package or CSV). 
* Unpack and activate the corresponding dbDIPview access package for this schema. 

```
cd dbdipview/admin
php menu.php
```
The end user can now use the browser to access the information in the database. Similarly, the access can be deactivated and the restored copy of the database deleted.

### End-user Access
The user's entry point is the address http://yourHostName/dbdipview/login.htm 

## Versioning
For the archiving purpose, backward compatibility needs to be maintained at least on the level of XML schema.

## Authors

* Boris Domajnko - *Initial work and maintenance*, [Archives of the Republic of Slovenia](http://www.arhiv.gov.si/en/)

