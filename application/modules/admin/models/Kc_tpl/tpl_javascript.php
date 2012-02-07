<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.rightClick.js" type="text/javascript"></script>
<script src="js/jquery.drag.js" type="text/javascript"></script>
<script src="js/helper.js" type="text/javascript"></script>
<script src="js/browser/joiner.php" type="text/javascript"></script>
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
