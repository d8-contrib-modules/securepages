CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

This is the Drupal 8 module Securepages, which will redirect user traffic
to a secure https connection, if the user is accessing the site to create or
editing content, viewing user details, or performing any other site
administrative tasks.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/securepages

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/securepages

REQUIREMENTS
------------

The server running the Drupal installation with this module enabled must be
configured to serve pages via SSL.

RECOMMENDED MODULES
-------------------

There are no specific modules recommended in tandem with this module.

INSTALLATION
------------

 * Prior to installation
    - Be sure the web server your Drupal site is on has been enabled with SSL.
    - Be sure the Drupal installation is configured to support SSL access.
 * Download and enable the module like any other Drupal module.
 * See https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

Options for this module can be set by visiting the configuration page
at admin/config/securepages or Configuration > System > Secure Pages. Options
include a master toggle to enable this module. Specific settings will be
retained whether the module is enabled or not.

 * Enable the module using listed settings below
 * Base URLs for secure and non-secure pages
 * Enter a list of paths to match to enable secure pages (node/\*/edit, for example)
   * Enable matching against above list or all but this above list
   * Enable if no matches specified in above list switch back to http
 * Enter a list of paths to ignore (<front> for example)
 * Enable secure pages always for specific user roles (administrator, for example)
 * Enter a list of specific form id machine names to enable secure pages
 * Enable Debugging


TROUBLESHOOTING
---------------

 * Make sure server is configured to server pages via SSL.
 * Make sure Drupal configuration file includes SSL access settings.


MAINTAINERS
-----------

Current maintainers:

 * Stephen Dix (jsdix) (https://www.drupal.org/user/422902)
 * Adam Bergstein (nerdstein) (https://www.drupal.org/user/1557710)

