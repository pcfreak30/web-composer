# Web Composer

[![Build Status](https://travis-ci.org/pcfreak30/web-composer.svg?branch=master)](https://travis-ci.org/pcfreak30/web-composer)
[![Code Climate](https://codeclimate.com/github/pcfreak30/web-composer/badges/gpa.svg)](https://codeclimate.com/github/pcfreak30/web-composer)


### Description ###

A php library that enables you to download composer and auto-install dependencies.

Currently to deploy an application with composer requires the use of SSH and potentially expose to many other tools. This means an engineer must be on hand. But what if you want to deploy an app by just uploading to a server and running its installer, or editing its config? The burden of managing a `vendor` folder is gone now. This class can be integrated as a git submodule or just copied in, and work with any app to automate installing dependancies.

### Limitations ###

The class currently does not check for install errors and assumes the install will be successful. More comprehensive checks may be done in the future.

### How to Use ###

Require the bootstrap file with `require_once __DIR__.'/web-composer/bootstrap.php';` and create a new instance of `\pcfreak30\Web\Composer`.

* Download target is the full path to download composer to, temporarily.
* Install target is the folder where composer.json and composer.lock exist
