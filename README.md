# Welcome to ZFKC project
This project regards a php connector written in zend framework for [KcFinder](http://kcfinder.sunhater.com// "go to KCFinder site") file manager.
The original code (server side) enclosed in the original package wasn't possible manage privileges at user level.
The connector here created, should make easier this job. Furthermore, the original code, was adapt for standalone usage and(probably) for single user usage.
Our requirements are:

- Zend Framework library;
- each action on client side must correspond an action on a ZF controller;
- usage of interafaces and classic ZF helpers where it possible;
- do not modify KCFinder javascript code and integrate it as it is;
- first goal is obtain the same functionalities as the original but written in ZF style;
 
This project reflects the [demo site](http://zfkc.ovum.it/ "jump to zfkc.ovum.it") and the source code here shown 
isn't specific to the connector. The connector can be seen in the following files:

- lib/My/Controller/Action/Helper/Kcfiles.php
- lib/My/KcFinder/Router.php
- application/controllers/KcController.php
- application/views/scripts/kc/*
- application/configs/KcConfig.ini

Whether you want to download and test this project remember that is necessary to include also the zend framework library(not included here) at this path:

- library/Zend

Or modify the file public/index.php file.
