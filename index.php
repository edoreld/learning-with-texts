<?php

/**************************************************************
"Learning with Texts" (LWT) is free and unencumbered software 
released into the PUBLIC DOMAIN.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a
compiled binary, for any purpose, commercial or non-commercial,
and by any means.

In jurisdictions that recognize copyright laws, the author or
authors of this software dedicate any and all copyright
interest in the software to the public domain. We make this
dedication for the benefit of the public at large and to the 
detriment of our heirs and successors. We intend this 
dedication to be an overt act of relinquishment in perpetuity
of all present and future rights to this software under
copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE 
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
THE SOFTWARE.

For more information, please refer to [http://unlicense.org/].
***************************************************************/

/**************************************************************
Call: index.php
LWT Start Screen / Main Menu / Home
***************************************************************/

if (! file_exists ('connect.inc.php')) {
	echo '<div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;"><p><b>Fatal Error:</b> Cannot find file: "connect.inc.php". Please rename the correct file "connect_[servertype].inc.php" to "connect.inc.php" ([servertype] is the name of your server: xampp, mamp, or easyphp). Please read the documentation: http://lwt.sf.net</p></div></body></html>';
	die('');
}

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

if ($tbpref == '') {
	$span2 = "<i>Default</i> Table Set</span>";
} else {
	$span2 = "Table Set: <i>" . tohtml(substr($tbpref,0,-1)) . "</i></span>";
}

if ($fixed_tbpref) {
	$span1 = '<span>';
	$span3 = '<span>';
} else {
	$span1 = '<span title="Manage Table Sets" onclick="location.href=\'table_set_management.php\';" class="click">';
	if (count(getprefixes()) > 0)
		$span3 = '<span title="Select Table Set" onclick="location.href=\'start.php\';" class="click">';
	else
		$span3 = '<span>';	
}

pagestart_nobody("Home");
echo '<h4>' . $span3;
echo_lwt_logo();
echo "Learning With Texts (LWT)";
echo '</span></h4><h3>Home' . ($debug ? ' <span class="red">DEBUG</span>' : '') . '</h3>';
echo "<p>&nbsp;</p>";

$currentlang = getSetting('currentlanguage');
$currenttext = getSetting('currenttext');

$langcnt = get_first_value('select count(*) as value from ' . $tbpref . 'languages');

if ($langcnt == 0) {
echo '<table class="tab3" cellspacing="0" cellpadding="5"><tr><th class="th1">Hint: The database seems to be empty.<br /><a href="install_demo.php">You may install the LWT demo database, </a><br />or<br /><a href="edit_languages.php?new=1">define the first language you want to learn.</a></th></tr></table>';
}

?>

<script type="text/javascript">
//<![CDATA[
if (! areCookiesEnabled()) document.write('<p class="red">*** Cookies are not enabled! Please enable! ***</p>');
//]]>
</script>

<?php if ($langcnt > 0 ) { ?>

<ul>
<li>Language: <select id="filterlang" onchange="{setLang(document.getElementById('filterlang'),'index.php');}"><?php echo get_languages_selectoptions($currentlang,'[Select...]'); ?></select></li>
</ul>
	
<?php
	if ($currenttext != '') {
		$txttit = get_first_value('select TxTitle as value from ' . $tbpref . 'texts where TxID=' . (int)$currenttext);
		if (isset($txttit)) {	
			$txtlng = get_first_value('select TxLgID as value from ' . $tbpref . 'texts where TxID=' . (int)$currenttext);
			$lngname = getLanguage($txtlng);
	?>
			<ul>
			<li>My last Text (in <?php echo tohtml($lngname); ?>):<br /> <i><?php echo tohtml($txttit); ?></i>
			<br />
			<a href="do_text.php?start=<?php echo $currenttext; ?>"><img src="icn/book-open-bookmark.png" title="Read" alt="Read" />&nbsp;Read</a>
			&nbsp; &nbsp; 
			<a href="do_test.php?text=<?php echo $currenttext; ?>"><img src="icn/question-balloon.png" title="Test" alt="Test" />&nbsp;Test</a>
			&nbsp; &nbsp; 
			<a href="print_text.php?text=<?php echo $currenttext; ?>"><img src="icn/printer.png" title="Print" alt="Print" />&nbsp;Print</a>
<?php
			if ((get_first_value("select length(TxAnnotatedText) as value from " . $tbpref . "texts where TxID = " . (int)$currenttext) + 0) > 0) {
?>
			&nbsp; &nbsp; 
			<a href="print_impr_text.php?text=<?php echo $currenttext; ?>"><img src="icn/tick.png" title="Improved Annotated Text" alt="Improved Annotated Text" />&nbsp;Ann. Text</a>
<?php
			}
?>
			</li>
			</ul>
<?php
		}
	}
}
?>

<ul>
<li><a href="edit_texts.php">My Texts</a></li>
<li><a href="edit_archivedtexts.php">My Text Archive</a></li>
<li><a href="edit_texttags.php">My Text Tags</a>
	<br /><br /></li>
<li><a href="edit_languages.php">My Languages</a>
	<br /><br /></li>
<li><a href="edit_words.php">My Terms (Words and Expressions)</a></li>
<li><a href="edit_tags.php">My Term Tags</a>
	<br /><br /></li>
<li><a href="statistics.php">My Statistics</a>
	<br /><br /></li>
<li><a href="check_text.php">Check a Text</a></li>
<li><a href="long_text_import.php">Long Text Import</a></li>
<li><a href="upload_words.php">Import Terms</a></li>
<li><a href="backup_restore.php">Backup/Restore/Empty Database</a>
	<br /><br /></li>
<li><a href="settings.php">Settings/Preferences</a>

<?php
// ********* WORDPRESS LOGOUT *********
if (isset($_COOKIE['LWT-WP-User'])) {
?>
	<br /><br /></li>
<li><a href="wp_lwt_stop.php"><span style="font-size:115%; font-weight:bold; color:red;">LOGOUT</span></a> (from WordPress and LWT)
<?php
}
// ********* WORDPRESS LOGOUT *********
?>

	<br /><br /></li>
<li><a href="info.htm">Help/Information</a></li>
<li><a href="mobile.php">Mobile LWT (Experimental)</a></li>
</ul>

<p class="smallgray graydotted">&nbsp;</p>
<table><tr><td class="width50px"><a target="_blank" href="http://unlicense.org/"><img alt="Public Domain" title="Public Domain" src="img/public_domain.png" /></a></td><td><p class="small"><a href="http://lwt.sourceforge.net/" target="_blank">"Learning with Texts" (LWT)</a> is free and unencumbered software released<br />into the <a href="https://en.wikipedia.org/wiki/Public_domain_software" target="_blank">PUBLIC DOMAIN</a>. <a href="http://unlicense.org/" target="_blank">More information and detailed Unlicense ...</a><br />

<?php

flush();
// optimizedb();

$p = convert_string_to_sqlsyntax_nonull($tbpref);
$mb = get_first_value("SELECT round(sum(data_length+index_length)/1024/1024,1) as value FROM information_schema.TABLES where table_schema = " . convert_string_to_sqlsyntax($dbname) . " and table_name in (" .
	"CONCAT(" . $p . ",'archivedtexts')," .
	"CONCAT(" . $p . ",'archtexttags')," .
	"CONCAT(" . $p . ",'languages')," .
	"CONCAT(" . $p . ",'sentences')," .
	"CONCAT(" . $p . ",'settings')," .
	"CONCAT(" . $p . ",'tags')," .
	"CONCAT(" . $p . ",'tags2')," .
	"CONCAT(" . $p . ",'textitems')," .
	"CONCAT(" . $p . ",'texts')," .
	"CONCAT(" . $p . ",'texttags')," .
	"CONCAT(" . $p . ",'words')," .
	"CONCAT(" . $p . ",'wordtags'))");
if (! isset($mb)) $mb = '0.0';

$serversoft = explode(' ',$_SERVER['SERVER_SOFTWARE']);
$apache = "Apache/?";
if (count($serversoft) >= 1) {
	if (substr($serversoft[0],0,7) == "Apache/") $apache = $serversoft[0];
}
$php = "PHP/" . phpversion();
$mysql = "MySQL/" . get_first_value("SELECT VERSION() as value");

?>

This is LWT Version <?php echo get_version(); ?><br /><a href="https://en.wikipedia.org/wiki/Database" target="_blank">Database</a>: <i><?php echo $dbname; ?></i> on <i><?php echo $server; ?></i> / <?php echo $span1 . $span2; ?> / Size: <?php echo $mb; ?> MB<br /><a href="https://en.wikipedia.org/wiki/Web_server" target="_blank">Web Server</a>: <i><?php echo $_SERVER['HTTP_HOST']; ?></i> / Server Software: <a href="https://en.wikipedia.org/wiki/Apache_HTTP_Server" target="_blank"><?php echo $apache; ?></a>&nbsp;&nbsp;<a href="https://en.wikipedia.org/wiki/PHP" target="_blank"><?php echo $php; ?></a>&nbsp;&nbsp;<a href="https://en.wikipedia.org/wiki/MySQL" target="_blank"><?php echo $mysql; ?></a></p></td></tr></table>

<?php

pageend();

?>