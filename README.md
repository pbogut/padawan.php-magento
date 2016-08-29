# Magento plugin for Padawan.php
==============================

A Magento plugin for [padawan.php completion server](https://github.com/mkusher/padawan.php).
Adds factory names completion when ones using `Mage::helper()`,
`Mage::getModel()`, `Mage::getRosourceModel()` and `Mage::getSingleton()`
(`Mage::getStoreConfig()` and `Mage::getStoreConfigFlag()` are going to be add later).
It is also resolving types after using methods above.

## Disclaimer

The plugin is in the development phase and is not stable. Delivered as it is
without any warranty. Use it at your own risk. Said that I'm using it every
day for Magento development and so can you.

## Demo
![demo](https://raw.githubusercontent.com/pbogut/padawan.php-magento/master/demo.gif)
![demo2](https://raw.githubusercontent.com/pbogut/padawan.php-magento/master/demo2.gif)

## Installation

The plugin can be install by the composer:
`composer.phar global require pbogut/padawan-magento`

Then plugin needs to be added to padawan server:
`padawan plugin add pbogut/padawan-magento`

In theory, that should be it. If you are not using composer in your Magento
project padawan may complain about `vendor/composer/autoload_classmap.php`.
In that case its enough if you create that file and return empty array inside:
`echo '<?php return [];' > ./vendor/completion/autoload_classmap.php`

## How it works?

When you use it for the first time in your project, the plugin will check XML files
by using `Mage` class. That may take few seconds, so completion may not work
with the first usage.

Currently, padawan does not support custom actions for plugins so there is no
easy way to detect XML file changes. ~~To get up to date with completion for
Magento (ie. when XML file is changed, or new extension added) padawan
server need to be restarted. The plugin is also implementing the hackish way to
rebuild data when you type `Mage::padawan_refresh(` in any PHP file.~~
However, the plugin is checking for flag file in a `.padawan` directory in
a project root. If there is `magento_reload_xml` file plugin will refresh data
from Magento XML files.

This can be automated by an editor. Below example for Vim:
```vim
:autocmd BufWritePre *.xml :silent! !touch .padawan/magento_reload_xml
```
## License
MIT

## Contribution
Any contribution is welcome.
