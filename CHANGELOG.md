# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.4.3 - 2023-07-14

### Added
- Voucher Codes and Gift-cards (only Gift-cards tested so far - vouchers still a work in progress)
- Job Positions can now be removed
- Items can now have different types
- added new item type "gift card"

## Fixes
- Fixes for No VAT Mode
- Job Financials are now calculated correctly

## 0.4.2 - 2023-07-13

### Added
- Client Side Navigation 
- New Fields for job, item, contact basic variants
- Form Fields now have a required flag

## Changed
- Added baseTable property for AbstractRepo, removed most overwritten functions in repos

## Fixes
- Tons of small fixes for DynamicForms

## 0.4.1 - 2023-07-12

### Added
- test database build script to fix GitHub pipeline

## 0.4.0 - 2023-07-12

### Added

### Changed
- migrated Job Module to DynamicDto
- split up database to multiple files to allow dynamic setups

### Removed
- test db - is now built by a script (not included in this repo)
- job position units (now using item units for job positions instead)

## 0.3.1 - 2023-07-11

### Added
- Notification System, communicate from license server to instances

## 0.3.0 - 2023-07-11

### Added
- E-Mail Address for Users
- Start Date for Licenses (only for license servers)