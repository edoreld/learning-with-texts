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
Call: set_word_status.php?...
			... tid=[textid]&wid=[wordid]&status=1..5/98/99
Change status of term while reading
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$tid = $_REQUEST['tid'];
$wid = $_REQUEST['wid'];
$status = $_REQUEST['status'];

$sql = 'SELECT WoText, WoTranslation, WoRomanization FROM ' . $tbpref . 'words where WoID = ' . $wid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
if ($record) {
	$word = $record['WoText'];
	$trans = repl_tab_nl($record['WoTranslation']) . getWordTagList($wid,' ',1,0);
	$roman = $record['WoRomanization'];
} else {
	my_die("Word not found in set_word_status.php"); 
}
mysqli_free_result($res);

pagestart("Term: " . $word, false);

$m1 = runsql('update ' . $tbpref . 'words set WoStatus = ' . 
	$_REQUEST['status'] . ', WoStatusChanged = NOW(),' . make_score_random_insert_update('u') . ' where WoID = ' . $wid, 'Status changed');

echo '<p>OK, this term has status ' . get_colored_status_msg($status) . ' from now!</p>';

?>
<script type="text/javascript">
//<![CDATA[
var context = window.parent.frames['l'].document;
var contexth = window.parent.frames['h'].document;
var status = '<?php echo $status; ?>';
var title = make_tooltip(<?php echo prepare_textdata_js($word); ?>, <?php echo prepare_textdata_js($trans); ?>, <?php echo prepare_textdata_js($roman); ?>, status);
$('.word<?php echo $wid; ?>', context).removeClass('status98 status99 status1 status2 status3 status4 status5').addClass('status<?php echo $status; ?>').attr('data_status','<?php echo $status; ?>').attr('title',title);
$('#learnstatus', contexth).html('<?php echo texttodocount2($tid); ?>');
window.parent.frames['l'].focus();
window.parent.frames['l'].setTimeout('cClick()', 100);
//]]>
</script>
<?php

pageend();

?>