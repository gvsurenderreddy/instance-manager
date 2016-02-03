Instance Manager
============

Instance Manager lets you synchronize databases and media between different app instances, call deploy scripts, log into ssh. All directly from console in context of your project.

Installation
============

***Using Composer***

Add the following to the "require" section of your `composer.json` file:

```
    "sourcebroker/instance-manager": "dev-master",
```

And update your dependencies:

```
    php composer.phar update
```


Configuration
============

Create configuration file at following path:

- For Symfony instance at `app/config/config.yml`:
- For TYPO3 instance at `typo3conf/ext/AdditionalConfiguration_deploy.yml`
- For Magento instance at `app/etc/local.yml`

Example configuration will follow later.