$out = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>KCFinder: /<?php echo $this->session['dir'] ?></title>
<!-- <?php INCLUDE "$template_dir/tpl_css.php" ?>  -->
<link href="css.php?type=<?php echo $this->type ?>" rel="stylesheet" type="text/css" />
<link href="themes/<?php echo $this->config['theme'] ?>/style.css" rel="stylesheet" type="text/css" />
<!-- <?php INCLUDE "$template_dir/tpl_javascript.php" ?>  -->

<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.rightClick.js" type="text/javascript"></script>
<script src="js/jquery.drag.js" type="text/javascript"></script>
<script src="js/helper.js" type="text/javascript"></script>
<!-- <script src="js/browser/joiner.php" type="text/javascript"></script> -->
<script src="js_localize.php?lng=<?php echo $this->lang ?>" type="text/javascript"></script>
<?php IF (isset($this->opener['TinyMCE']) && $this->opener['TinyMCE']): ?>
<script src="<?php echo $this->config['_tinyMCEPath'] ?>/tiny_mce_popup.js" type="text/javascript"></script>
<?php ENDIF ?>
<?php IF (file_exists("themes/{$this->config['theme']}/init.js")): ?>
<script src="themes/<?php echo $this->config['theme'] ?>/init.js" type="text/javascript"></script>
<?php ENDIF ?>
<script type="text/javascript">
browser.version = "<?php echo self::VERSION ?>";
browser.support.chromeFrame = <?php echo (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), " chromeframe") !== false) ? "true" : "false" ?>;
browser.support.zip = <?php echo (class_exists('ZipArchive') && !$this->config['denyZipDownload']) ? "true" : "false" ?>;
browser.support.check4Update = <?php echo ((!isset($this->config['denyUpdateCheck']) || !$this->config['denyUpdateCheck']) && (ini_get("allow_url_fopen") || function_exists("http_get") || function_exists("curl_init") || function_exists('socket_create'))) ? "true" : "false" ?>;
browser.lang = "<?php echo Admin_Model_Kclib_Text::jsValue($this->lang) ?>";
browser.type = "<?php echo Admin_Model_Kclib_Text::jsValue($this->type) ?>";
browser.theme = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['theme']) ?>";
browser.access = <?php echo json_encode($this->config['access']) ?>;
browser.dir = "<?php echo Admin_Model_Kclib_Text::jsValue($this->session['dir']) ?>";
browser.uploadURL = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['uploadURL']) ?>";
browser.thumbsURL = browser.uploadURL + "/<?php echo Admin_Model_Kclib_Text::jsValue($this->config['thumbsDir']) ?>";
<?php IF (isset($this->get['opener']) && strlen($this->get['opener'])): ?>
browser.opener.name = "<?php echo Admin_Model_Kclib_Text::jsValue($this->get['opener']) ?>";
<?php ENDIF ?>
<?php IF (isset($this->opener['CKEditor']['funcNum']) && preg_match('/^\d+$/', $this->opener['CKEditor']['funcNum'])): ?>
browser.opener.CKEditor = {};
browser.opener.CKEditor.funcNum = <?php echo $this->opener['CKEditor']['funcNum'] ?>;
<?php ENDIF ?>
<?php IF (isset($this->opener['TinyMCE']) && $this->opener['TinyMCE']): ?>
browser.opener.TinyMCE = true;
<?php ENDIF ?>
browser.cms = "<?php echo Admin_Model_Kclib_Text::jsValue($this->cms) ?>";
_.kuki.domain = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookieDomain']) ?>";
_.kuki.path = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookiePath']) ?>";
_.kuki.prefix = "<?php echo Admin_Model_Kclib_Text::jsValue($this->config['cookiePrefix']) ?>";
$(document).ready(function() {
    browser.resize();
    browser.init();
    $('#all').css('visibility', 'visible');
});
$(window).resize(browser.resize);
</script>


</head>
<body>
<script type="text/javascript">
$('body').noContext();
</script>
<div id="resizer"></div>
<div id="shadow"></div>
<div id="dialog"></div>
<div id="alert"></div>
<div id="clipboard"></div>
<div id="all">
<div id="left">
    <div id="folders"></div>
</div>
<div id="right">
    <div id="toolbar">
        <div>
        <a href="kcact:upload"><?php echo $this->label("Upload") ?></a>
        <a href="kcact:refresh"><?php echo $this->label("Refresh") ?></a>
        <a href="kcact:settings"><?php echo $this->label("Settings") ?></a>
        <a href="kcact:maximize"><?php echo $this->label("Maximize") ?></a>
        <a href="kcact:about"><?php echo $this->label("About") ?></a>
        <div id="loading"></div>
        </div>
    </div>
    <div id="settings">

    <div>
    <fieldset>
    <legend><?php echo $this->label("View:") ?></legend>
        <table summary="view" id="view"><tr>
        <th><input id="viewThumbs" type="radio" name="view" value="thumbs" /></th>
        <td><label for="viewThumbs">&nbsp;<?php echo $this->label("Thumbnails") ?></label> &nbsp;</td>
        <th><input id="viewList" type="radio" name="view" value="list" /></th>
        <td><label for="viewList">&nbsp;<?php echo $this->label("List") ?></label></td>
        </tr></table>
    </fieldset>
    </div>

    <div>
    <fieldset>
    <legend><?php echo $this->label("Show:") ?></legend>
        <table summary="show" id="show"><tr>
        <th><input id="showName" type="checkbox" name="name" /></th>
        <td><label for="showName">&nbsp;<?php echo $this->label("Name") ?></label> &nbsp;</td>
        <th><input id="showSize" type="checkbox" name="size" /></th>
        <td><label for="showSize">&nbsp;<?php echo $this->label("Size") ?></label> &nbsp;</td>
        <th><input id="showTime" type="checkbox" name="time" /></th>
        <td><label for="showTime">&nbsp;<?php echo $this->label("Date") ?></label></td>
        </tr></table>
    </fieldset>
    </div>

    <div>
    <fieldset>
    <legend><?php echo $this->label("Order by:") ?></legend>
        <table summary="order" id="order"><tr>
        <th><input id="sortName" type="radio" name="sort" value="name" /></th>
        <td><label for="sortName">&nbsp;<?php echo $this->label("Name") ?></label> &nbsp;</td>
        <th><input id="sortType" type="radio" name="sort" value="type" /></th>
        <td><label for="sortType">&nbsp;<?php echo $this->label("Type") ?></label> &nbsp;</td>
        <th><input id="sortSize" type="radio" name="sort" value="size" /></th>
        <td><label for="sortSize">&nbsp;<?php echo $this->label("Size") ?></label> &nbsp;</td>
        <th><input id="sortTime" type="radio" name="sort" value="date" /></th>
        <td><label for="sortTime">&nbsp;<?php echo $this->label("Date") ?></label> &nbsp;</td>
        <th><input id="sortOrder" type="checkbox" name="desc" /></th>
        <td><label for="sortOrder">&nbsp;<?php echo $this->label("Descending") ?></label></td>
        </tr></table>
    </fieldset>
    </div>

    </div>
    <div id="files">
        <div id="content"></div>
    </div>
</div>
<div id="status"><span id="fileinfo">&nbsp;</span></div>
</div>
</body>
</html>

EOT;
