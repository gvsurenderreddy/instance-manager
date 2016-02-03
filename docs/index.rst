Instance Manager
================

Instance Manager lets you synchronize database and media files between different instances. It can be used in any PHP application.

Features
^^^^^^^^
* Synchronization of databases between different application instances.
* Synchronization of media files between different application instances.
* Wrapper for deployment systems (as capistrano or deployer etc). Wrapping allows you to bring common namings for commands nevertless the deployment system used.
* Log into ssh of selected instance.
* Secure thanks to web hooks used to do pushing of database and media files to proxy ssh accounts.

All commands are done directly from console in context of your project.


Contents
^^^^^^^^

.. toctree::
   :maxdepth: 2

   start
   commands
