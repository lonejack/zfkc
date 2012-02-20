/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';http://kcfinder.sunhater.com/demo/kcfinder/browse.php
	
	config.filebrowserBrowseUrl = '/js/kcfinder/browse.php?type=files';
   	config.filebrowserImageBrowseUrl = '/js/kcfinder/browse.php?type=images';
	config.filebrowserFlashBrowseUrl = '/js/kcfinder/browse.php?type=flash';
   	config.filebrowserUploadUrl = '/js/kcfinder/upload.php?type=files';
   	config.filebrowserImageUploadUrl = '/js/kcfinder/upload.php?type=images';
   	config.filebrowserFlashUploadUrl = '/js/kcfinder/upload.php?type=flash';

/*	
	config.filebrowserBrowseUrl = 'http://kcfinder.sunhater.com/demo/kcfinder/browse.php?type=files';
	config.filebrowserImageBrowseUrl = 'http://kcfinder.sunhater.com/demo/kcfinder/browse.php?type=images';
	config.filebrowserFlashBrowseUrl = 'http://kcfinder.sunhater.com/demo/kcfinder/browse.php?type=flash';
	config.filebrowserUploadUrl = '/js/kcfinder/upload.php?type=files';
	config.filebrowserImageUploadUrl = '/js/kcfinder/upload.php?type=images';
	config.filebrowserFlashUploadUrl = '/js/kcfinder/upload.php?type=flash';	
*/	
};
