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
Call: statistics.php
Display statistics
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

pagestart('My Statistics',true);

?>
<h4>Breakdown by Language and Term Status<br />(Click on numbers to see the list of terms)</h4>
<table class="tab3" cellspacing="0" cellpadding="5">
<tr>
<th class="th1">Language</th>
<th class="th1">Total<br /></th>
<th class="th1">Active<br />(1..5)</th>
<th class="th1">Learning<br />(1..4)</th>
<th class="th1">Unknown<br />(1)</th>
<th class="th1">Learning<br />(2)</th>
<th class="th1">Learning<br />(3)</th>
<th class="th1">Learning<br />(4)</th>
<th class="th1">Learned<br />(5)</th>
<th class="th1">Well<br />Known<br />(99)</th>
<th class="th1">Known<br />(5+99)</th>
<th class="th1">Ign.<br />(98)</th>
</tr>
<?php

$sum1 = 0;
$sum2 = 0;
$sum3 = 0;
$sum4 = 0;
$sum5 = 0;
$sum98 = 0;
$sum99 = 0;
$sum14 = 0;
$sum15 = 0;
$sum599 = 0;
$sumall = 0;

$sql = 'SELECT LgID, LgName FROM ' . $tbpref . 'languages ORDER BY LgName';
$res = do_mysqli_query($sql);
while ($record = mysqli_fetch_assoc($res)) {
	$lang = $record['LgID'];
	
	flush();
	$s1 = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus = 1');
	$s2 = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus = 2');
	$s3 = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus = 3');
	$s4 = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus = 4');
	$s5 = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus = 5');
	$s98 = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus = 98');
	$s99 = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus = 99');
	$s14 = $s1 + $s2 + $s3 + $s4;
	$s15 = $s14 + $s5;
	$s599 = $s5 + $s99;
	$all = $s15 + $s98 + $s99;
	
	$sum1 += $s1;
	$sum2 += $s2;
	$sum3 += $s3;
	$sum4 += $s4;
	$sum5 += $s5;
	$sum98 += $s98;
	$sum99 += $s99;
	$sum14 += $s14;
	$sum15 += $s15;
	$sum599 += $s599;
	$sumall += $all;

	echo '<tr>';
	echo '<td class="td1">' . tohtml($record['LgName']) . '</td>';
	echo '<td class="td1 center"><a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $all . '</b></a></td>';
	echo '<td class="td1 center"><a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s15 . '</b></a></td>';
	echo '<td class="td1 center"><a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s14 . '</b></a></td>';
	echo '<td class="td1 center"><span class="status1">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=1&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s1 . '</a>&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status2">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=2&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s2 . '</a>&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status3">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=3&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s3 . '</a>&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status4">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=4&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s4 . '</a>&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=5&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s5 . '</a>&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status99">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=99&amp;tag12=0&amp;tag2=&amp;tag1=">' . $s99 . '</a>&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5stat">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=599&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s599 . '</b></a>&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status98">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=' . $lang . '&amp;status=98&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $s98 . '</b></a>&nbsp;</span></td>';
	echo '</tr>';
	
}
mysqli_free_result($res);
echo '<tr>';
echo '<th class="th1"><b>TOTAL</b></th>';
echo '<th class="th1 center"><a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sumall . '</b></a></th>';
echo '<th class="th1 center"><a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=15&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum15 . '</b></a></th>';
echo '<th class="th1 center"><a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=14&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum14 . '</b></a></th>';
echo '<th class="th1 center"><span class="status1">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=1&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum1 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status2">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=2&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum2 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=3&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum3 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status4">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=4&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum4 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=5&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum5 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status99">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=99&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum99 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=599&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum599 . '</b></a>&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status98">&nbsp;<a href="edit_words.php?page=1&amp;text=&amp;query=&amp;filterlang=&amp;status=98&amp;tag12=0&amp;tag2=&amp;tag1="><b>' . $sum98 . '</b></a>&nbsp;</span></th>';
echo '</tr>';
?>
</table>

<?php

$sumct = 0;
$sumat = 0;
$sumkt = 0;
$sumcy = 0;
$sumay = 0;
$sumky = 0;
$sumcw = 0;
$sumaw = 0;
$sumkw = 0;
$sumcm = 0;
$sumam = 0;
$sumkm = 0;
$sumca = 0;
$sumaa = 0;
$sumka = 0;
$sumcall = 0;
$sumaall = 0;
$sumkall = 0;

?>

<h4>Breakdown by Language and Time Range<br />(Terms created (C), Terms changed status = Activity (A), Terms set to "Known" (K))</h4>
<table class="tab3" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" rowspan="2">Language</th>
<th class="th1" colspan="3">Today</th>
<th class="th1" colspan="3">Yesterday</th>
<th class="th1" colspan="3">Last 7 d</th>
<th class="th1" colspan="3">Last 30 d</th>
<th class="th1" colspan="3">Last 365 d</th>
<th class="th1" colspan="3">All Time</th>
</tr>
<tr>
<th class="th1">C</th>
<th class="th1">A</th>
<th class="th1">K</th>
<th class="th1">C</th>
<th class="th1">A</th>
<th class="th1">K</th>
<th class="th1">C</th>
<th class="th1">A</th>
<th class="th1">K</th>
<th class="th1">C</th>
<th class="th1">A</th>
<th class="th1">K</th>
<th class="th1">C</th>
<th class="th1">A</th>
<th class="th1">K</th>
<th class="th1">C</th>
<th class="th1">A</th>
<th class="th1">K</th>
</tr>
<?php
$sql = 'SELECT LgID, LgName FROM ' . $tbpref . 'languages ORDER BY LgName';
$res = do_mysqli_query($sql);
while ($record = mysqli_fetch_assoc($res)) {
	$lang = $record['LgID'];

	flush();
	$ct = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoCreated as date) = curdate()');
	$at = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoStatusChanged as date) = curdate()');
	$kt = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (5,99) and cast(WoStatusChanged as date) = curdate()');

	$cy = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoCreated as date) = subdate(curdate(), \'1 day\')');
	$ay = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoStatusChanged as date) = subdate(curdate(), \'1 day\')');
	$ky = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (5,99) and cast(WoStatusChanged as date) = subdate(curdate(), \'1 day\')');
	
	$cw = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoCreated as date) between subdate(curdate(), \'6 day\') and curdate()');
	$aw = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoStatusChanged as date) between subdate(curdate(), \'6 day\') and curdate()');
	$kw = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (5,99) and cast(WoStatusChanged as date) between subdate(curdate(), \'6 day\') and curdate()');
	
	$cm = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoCreated as date) between subdate(curdate(), \'29 day\') and curdate()');
	$am = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoStatusChanged as date) between subdate(curdate(), \'29 day\') and curdate()');
	$km = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (5,99) and cast(WoStatusChanged as date) between subdate(curdate(), \'29 day\') and curdate()');
	
	$ca = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoCreated as date) between subdate(curdate(), \'364 day\') and curdate()');
	$aa = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99) and cast(WoStatusChanged as date) between subdate(curdate(), \'364 day\') and curdate()');
	$ka = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (5,99) and cast(WoStatusChanged as date) between subdate(curdate(), \'364 day\') and curdate()');
	
	$call = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99)');
	$aall = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (1,2,3,4,5,99)');
	$kall = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . $lang . ' and WoStatus in (5,99)');
	
	$sumct += $ct;
	$sumat += $at;
	$sumkt += $kt;
	$sumcy += $cy;
	$sumay += $ay;
	$sumky += $ky;
	$sumcw += $cw;
	$sumaw += $aw;
	$sumkw += $kw;
	$sumcm += $cm;
	$sumam += $am;
	$sumkm += $km;
	$sumca += $ca;
	$sumaa += $aa;
	$sumka += $ka;
	$sumcall += $call;
	$sumaall += $aall;
	$sumkall += $kall;
	
	echo '<tr>';
	echo '<td class="td1">' . tohtml($record['LgName']) . '</td>';
	
	echo '<td class="td1 center"><span class="status1">&nbsp;' . $ct . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status3">&nbsp;' . $at . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $kt . '&nbsp;</span></td>';
	
	echo '<td class="td1 center"><span class="status1">&nbsp;' . $cy . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status3">&nbsp;' . $ay . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $ky . '&nbsp;</span></td>';
	
	echo '<td class="td1 center"><span class="status1">&nbsp;' . $cw . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status3">&nbsp;' . $aw . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $kw . '&nbsp;</span></td>';
	
	echo '<td class="td1 center"><span class="status1">&nbsp;' . $cm . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status3">&nbsp;' . $am . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $km . '&nbsp;</span></td>';
	
	echo '<td class="td1 center"><span class="status1">&nbsp;' . $ca . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status3">&nbsp;' . $aa . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $ka . '&nbsp;</span></td>';
	
	echo '<td class="td1 center"><span class="status1">&nbsp;' . $call . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status3">&nbsp;' . $aall . '&nbsp;</span></td>';
	echo '<td class="td1 center"><span class="status5stat">&nbsp;' . $kall . '&nbsp;</span></td>';
	
	echo '</tr>';
}
mysqli_free_result($res);
echo '<tr>';
echo '<th class="th1"><b>TOTAL</b></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumct . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumat . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkt . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcy . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumay . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumky . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcw . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumaw . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkw . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcm . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumam . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkm . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumca . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumaa . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumka . '&nbsp;</span></th>';

echo '<th class="th1 center"><span class="status1">&nbsp;' . $sumcall . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status3">&nbsp;' . $sumaall . '&nbsp;</span></th>';
echo '<th class="th1 center"><span class="status5stat">&nbsp;' . $sumkall . '&nbsp;</span></th>';

echo '</tr>';
?>
</table> 
<p><input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" /></p>
<?php

pageend();

?>