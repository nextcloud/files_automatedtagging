# Nextcloud Files Automated Tagging App

n app for Nextcloud that automatically assigns tags to newly uploaded files based on some conditions.

The tags can later be used to control retention, file access, automatic script execution and more.

![screenshot](screenshots/tagging-retention.png)

## How it works
To define tags, administrators can create and manage a set of rule groups. Each rule group consists of one or more rules combined through operators. Rules can include criteria like file type, size, time and more. A request matches a group if all rules evaluate to true. On uploading a file all defined groups are evaluated and when matching, the given tags are assigned to the file.

## QA metrics on master branch:

[![Build Status](https://travis-ci.org/nextcloud/files_automatedtagging.svg?branch=master)](https://travis-ci.org/nextcloud/files_automatedtagging/branches)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nextcloud/files_automatedtagging/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/files_automatedtagging/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/nextcloud/files_automatedtagging/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/nextcloud/files_automatedtagging/?branch=master)

