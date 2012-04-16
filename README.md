# Welcome to ZFKC project
This project regards a php connector written in zend framework for [KcFinder](http://kcfinder.sunhater.com// "KCFinder") module.
The original code (server side) enclosed in the origina package is designed in a manner that makes difficult to manage privileges at user level.
This connector makes this possible and is integrated on ZF infrastructure.
 
This project reflects the [demo site](http://zfkc.ovum.it/ "jump to zfkc.ovum.it"). The connector can be seen in these files:

- lib/My/Controller/Action/Helper/Kcfiles.php
- lib/My/KcFinder/Router.php
- application/controllers/KcController.php
- application/views/scripts/kc/*
- application/configs/KcConfig.ini

The rest of the code regards the "demo site". Whenever you want to download and test this project remember that is neccessary to include also the zend framework library(not included here):

- library/Zend

