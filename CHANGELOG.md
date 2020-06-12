# Changelog
Here you can find the notable changes of the tool. The text format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) (with change types: Added, Changed, Deprecated, Removed, Fixed, Security).
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
Generally, the master branch is always stable. You can use it for installation or updating.
### Added
- access: logo of the instituition can be displayed on the first page (see local/README.txt)

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
