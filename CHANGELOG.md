# Changelog
Here you can find the notable changes of the tool. The text format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) (with change types: Added, Changed, Deprecated, Removed, Fixed, Security).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
Generally, the master branch is always stable. You can use it for installation or updating.
### Modified
- test suite: run all options -v (verbose) and -r (remove only), no need to remove twice because of dependencies in TC 4,5 and 6

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
- administration module: CLI for automized installation and uninstallation of packages based on an order file
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
