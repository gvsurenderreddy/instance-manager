Instance Manager
============

Instance Manager lets you synchronize databases and media between different app instances, call deploy scripts, log into ssh. All directly from console in context of your project.

Installation
============

***Using Composer***

Add the following to the "repositories" section of your `composer.json` file:

```
    "repositories": [
        {
            "type": "vcs",
            "url":  "git@github.com:sourcebroker/instance-manager.git"
        }
    ]
```

Add the following to the "require" section of your `composer.json` file:

```
    "sourcebroker/instance-manager": "dev-master",
```

And update your dependencies

```
    php composer.phar update
```


Configuration
============

Adds the following configuration to following path:

- For Symfony application `app/config/config.yml`:
- For TYPO3 instance in `typo3conf/ext/AdditionalConfiguration_deploy.yml`
- For Magento instance in `app/etc/local.yml`

Example configuration will follow later.