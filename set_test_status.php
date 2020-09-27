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
Call: set_test_status.php?wid=[wordid]&stchange=+1/-1
      set_test_status.php?wid=[wordid]&status=1..5/98/99
Change status of term while testing
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$stchange = getreq('stchange');
$status = getreq('status');
$wid = getreq('wid') + 0;

$oldstatus = get_first_value("select WoStatus as value from " . $tbpref . "words where WoID = " . $wid) + 0;

$oldscore = get_first_value('select greatest(0,round(WoTodayScore,0)) AS value from ' . $tbpref . 'words where WoID = ' . $wid) + 0;

if ($stchange == '') {

	$status = $status + 0;
	$stchange = $status - $oldstatus;
	if ($stchange <= 0) $stchange=-1;
	if ($stchange > 0) $stchange=1;
	
} else {

	$stchange = $stchange + 0;
	$status = $oldstatus + $stchange;
	if ($status < 1) $status=1;
	if ($status > 5) $status=5;
	
}

$word = get_first_value("select WoText as value from " . $tbpref . "words where WoID = " . $wid);
pagestart("Term: " . $word, false);

$m1 = runsql('update ' . $tbpref . 'words set WoStatus = ' . 
	$status . ', WoStatusChanged = NOW(),' . make_score_random_insert_update('u') . ' where WoID = ' . $wid, 'Status changed');
	
$newscore = get_first_value('select greatest(0,round(WoTodayScore,0)) AS value from ' . $tbpref . 'words where WoID = ' . $wid) + 0;

if ($oldstatus == $status)
	echo '<p>Status ' . get_colored_status_msg($status) . ' not changed.</p>';
else
	echo '<p>Status changed from ' . get_colored_status_msg($oldstatus) . ' to ' . get_colored_status_msg($status) . '.</p>';

echo "<p>Old score was " . $oldscore . ", new score is now " . $newscore . ".</p>";

$totaltests = $_SESSION['testtotal'];
$wrong = $_SESSION['testwrong'];
$correct = $_SESSION['testcorrect'];
$notyettested = $totaltests - $correct - $wrong;
if ( $notyettested > 0 ) {
	if ( $stchange >= 0 ) 
		$_SESSION['testcorrect']++;
	else
		$_SESSION['testwrong']++;
}		

?>
<script type="text/javascript">
//<![CDATA[
var context = window.parent.frames['l'].document;
$('.word<?php echo $wid; ?>', context).removeClass('todo todosty').addClass('done<?php echo ($stchange >= 0 ? 'ok' : 'wrong'); ?>sty').attr('data_status','<?php echo $status; ?>').attr('data_todo','0');
<?php
$waittime = getSettingWithDefault('set-test-main-frame-waiting-time') + 0;
if ($waittime <= 0 ) {
?>
window.parent.frames['l'].location.reload();
<?php
} else {
?>
setTimeout('window.parent.frames[\'l\'].location.reload();', <?php echo $waittime; ?>);
<?php
}
?>
//]]>
</script>
<?php

pageend();

?>