# Changelog
Here you can find the notable changes of the tool. The text format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) (with change types: Added, Changed, Deprecated, Removed, Fixed, Security).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
Generally, the master branch is always stable. You can use it for installation or updating.

## [2.10.1] - 2022-06-29
### Added
- access: query and subquery can be without SQL code, only title and subtitle will be displayed
- test suite: TestAndDemo 6 demonstrates this possibility
- test suite: TestAndDemo 2 - a parameter has been added

## [2.10.0] - 2022-06-27
### Added
- access: start page can be configured: enable/disable the dropdown list of databases
- access: print icon now also for table view

## [2.9.1] - 2022-06-16
### Fixed
- access: session time-out behaviour

## [2.9.0] - 2022-06-13
### Added
- access: configurable multi-column view

## [2.8.6] - 2022-06-01
### Added
- access: configurable multi-column view (preview)
### Modified
- test suite: TestAndDemo 2 shows usage of multi-column view
- access: some CSS changes for tablet devices

## [2.8.5] - 2022-05-20
### Modified
- access: Minor improvement in debugging information in area of hyperlink creation

## [2.8.4] - 2022-05-12
### Modified
- access: Some corrections for messages in Czech language modified (by Martin Rechtorik from NACR)

## [2.8.3] - 2022-05-09
### Modified
- some minor improvements

## [2.8.2] - 2022-04-22
### Modified
- administration module: config files moved to config folder

## [2.8.1] - 2022-04-07
### Modified
- access: changed the way how the attachments are downloaded

## [2.8.0] - 2022-01-31
### Added
- administration module: incremental updating of DIPp is now possible

## [2.7.3] - 2021-11-09
### Added
- access: read-only fields are now marked with an asterisk
### Modified
- packaging: relative folders can be used for input and output parameters
- access: customizable CSS; preview version

## [2.7.2] - 2021-10-22
### Added
- access: customizable CSS; preview version

## [2.7.1] - 2021-10-12
### Added
- access: it is now possible to customize CSS per site; preview version

## [2.7.0] - 2021-09-29
### Added
- access: macro has been introduced to display number of records in each table instead of a DBMS specific script
- access: "Page loading" animation has been added for cases of long search in the database
### Modified
- test suite: TestAndDemo 2 now uses the new macro
### Fixed
- access: non-public database can now be accessed only via a ticket code

## [2.6.2] - 2021-08-25
### Modified
- access: debugg messages for query parameters 
- access: main query before subqueries can be skipped (i.e. empty) now 
### Fixed
- access: description.txt content was too far right on the reports menu

## [2.6.1] - 2021-07-01
### Added
- access: admin can change treeview window preset height
### Fixed
- access: a nested treeview detail

## [2.6.0] - 2021-06-25
### Added
- access: composite keys for links from one report to another one (values from more source columns used as parameters for matching a record)
- access: print button for report in list view
### Modified
- test suite: TestAndDemo 3 enhanced to test new functionality
### Fixed
- access: passing parameters from multiple input fields down to subqueries
- access: some nested treeview details

## [2.5.0] - 2021-05-29
### Added
- access: nested treeview for menu with avaiable reports
- access: in the list view it is possible to choose between showing all lines or non-empty lines
### Modified
- test suite: TestAndDemo 2 and 6 enhanced to test new functionality
### Fixed
- access: copy to clipboard will now exclude the question mark icon

## [2.4.0] - 2021-05-15
### Added
- access: database column descriptions are shown as infotip
### Modified
- test suite: TestAndDemo 2 enhanced to test new functionality

## [2.3.6] - 2021-04-20
### Fixed
- access: exact parameter match for subquery call instead of LIKE

## [2.3.5] - 2021-04-08
### Modified
- access: link download as CSV moved to the first line of the header

## [2.3.4] - 2021-01-30
### Modified
- access: toggle between table and list view is now possible before any search

## [2.3.3] - 2021-01-22
### Modified
- test suite: run all options -v (verbose) and -r (remove only), no need to remove twice because of dependencies in TestAndDemo 4,5 and 6
- packaging: stop processing sooner if a file missing (for easier debugging)

## [2.3.0] - 2020-12-13
### Added
- packaging: when a link to another query is made from a certain column, the forwarded parameter can now also be a value from another column by using the new attribute valueFromColumn
- test suite: some improvements in TestAndDemo cases
### Fixed
- access: issue with forwarded parameter when text with spaces
- access: issue with parenthesis in ORDER BY with ASC and DESC

## [2.2.2] - 2020-12-07
### Added
- test suite: TestAndDemo4 now contains an example of populating a table from two CSV files
### Fixed
- administration module: files folder name (for attachments) did not have the same name if installed with order or manual mode

## [2.2.1] - 2020-12-04
### Added
- access: debug mode parameter is now configurable in the config.txt file
- packaging: in addition to already available redacting, DDV package can now contain definitions of VIEWs for already existing database
- administration module: VIEWs from DDV can now also be configured (not only from EXT DDV)
- test suite: TestAndDemo5 now contains an example of VIEW to another table
### Modified
- access: improved search parameters passing (as forwarded values) for subqueries ( e.g. date >=, date < )

## [2.2.0] - 2020-09-07
### Added
- access: logo of the institution can be displayed on the first page (see local/README.txt)
- access: a scrollable window is used to display available reports for a selected database
- access: description.txt has been added for the html text to be displayed on the reports menu page
- access: the <overview> element has been added as a short alternative to description.txt
- test suite: TestAndDemo2 report show number of records in each table added as a template
- test suite: a sample control report has been added to display number of records in each table in a single report
### Modified
- administration module: the folder for attachment files (LOBs) is now created for each DBC/DDV pair, before it was for each DDV

## [2.1.0] - 2020-05-13
### Added
- allow the definition of URL prefix in the queries.xml if a certain column contains addresses
- packaging: will now validate the columns in list.txt file and check the existence of all files
- packaging: will now accept more encodings for CSV files, not only UTF8
- packaging: will now calculate hash values for files in the data folder
- packaging: creates about.xml with basic information about packaging
- administration module: output and debug output small improvements
- access: Czech translation of the client has been provided by the National Archives of the Czech Republic
- access: product version is now displayed
- access: total number of records is now displayed at the end of the report
- test suite: TestAndDemo6 added
- test suite: run_all.sh option -r added to remove installed TestAndDemo databases
### Changed
- packaging: list.txt allows 'tab' to describe CSV files (previously \\t)
- access: some modifications for visually impaired users
- test suite: some textual improvements for better understandability and shorter learning curve

## [2.0.0] - 2019-12-26
### Added
- packaging: XML queries file validation against the schema 
- administration module: workflow-based menu for deployments and removal of databases
- administration module: CLI for automated installation and uninstallation of packages based on an order file
- administration module: support of SIARD format added
- administration module: combinations of an arbitrary number of different packages are possible
- administration module: redacting is possible
- administration module: automated Regression Test And Demo examples
- access: improved search
- access: save results as CSV
- access: support of BLOBs
- localization
- various minor improvements

### Changed
- packaging: XML schema for queries: versioning introduced, some elements renamed or added

### Deprecated
- packaging: XML schema for queries, version 1

## [1.X.X] - 2019-06-09
### Added
- the project moved to Github
- continuous improvement

## [1.0.0] - 2009
### Added
- implementation and start of use in production
