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
Call: do_test_header.php?lang=[langid]
Call: do_test_header.php?text=[textid]
Call: do_test_header.php?selection=1  
			(SQL via $_SESSION['testsql'])
Show test header frame
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$p = '';
$message = '';

if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) { 
	$p = "selection=" . $_REQUEST['selection']; 
	$testsql = $_SESSION['testsql'];
	$totalcount = get_first_value('select count(distinct WoID) as value from ' . $testsql);
	$title = 'Selected ' . $totalcount . ' Term' . ($totalcount==1 ? '' : 's');
	$cntlang = get_first_value('select count(distinct WoLgID) as value from ' . $testsql);
	if ($cntlang > 1) 
		$message = 'Error: The selected terms are in ' . $cntlang . ' languages, but tests are only possible in one language at a time.';
	else 
		$title .= ' in ' . get_first_value('select LgName as value from ' . $tbpref . 'languages, ' . $testsql . ' and LgID = WoLgID limit 1');
}

if (isset($_REQUEST['lang'])) {
	$langid = getreq('lang');
	$p = "lang=" . $langid; 
	$title = "All Terms in " . get_first_value('select LgName as value from ' . $tbpref . 'languages where LgID = ' . $langid);
	$testsql = ' ' . $tbpref . 'words where WoLgID = ' . $langid . ' ';
}

if (isset($_REQUEST['text'])) {
	$textid = getreq('text');
	$p = "text=" . $textid; 
	$title = get_first_value('select TxTitle as value from ' . $tbpref . 'texts where TxID = ' . $textid);
	saveSetting('currenttext',$_REQUEST['text']);
	$testsql = ' ' . $tbpref . 'words, ' . $tbpref . 'textitems where TiLgID = WoLgID and TiTextLC = WoTextLC and TiTxID = ' . $textid . ' ';
}

if ($p == '') my_die("do_test_header.php called with wrong parameters");

$totalcountdue = get_first_value('SELECT count(distinct WoID) as value FROM ' . $testsql . ' AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != \'\' AND WoTranslation != \'*\' AND WoTodayScore < 0');
$totalcount = get_first_value('SELECT count(distinct WoID) as value FROM ' . $testsql . ' AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != \'\' AND WoTranslation != \'*\'');

pagestart_nobody(tohtml($title),$addcss='html, body {margin-bottom:0;}');
echo '<h4>';
echo '<a href="edit_texts.php" target="_top">';
echo_lwt_logo();
echo 'LWT';
echo '</a>&nbsp; | &nbsp;';
quickMenu();
if (substr($p,0,4) == 'text') {
	echo getPreviousAndNextTextLinks($textid, 'do_test.php?text=', FALSE, '&nbsp; | &nbsp;');
	echo '&nbsp; | &nbsp;<a href="do_text.php?start=' . $textid . '" target="_top"><img src="icn/book-open-bookmark.png" title="Read" alt="Read" /></a> &nbsp;<a href="print_text.php?text=' . $textid . '" target="_top"><img src="icn/printer.png" title="Print" alt="Print" /></a>' . get_annotation_link($textid);
}
echo '</h4><table><tr><td><h3>TEST&nbsp;▶</h3></td><td class="width99pc"><h3>' . tohtml($title) . ' (Due: ' . $totalcountdue . ' of ' . $totalcount . ')</h3></td></tr><tr><td colspan="2">';

$_SESSION['teststart'] = time() + 2;
$_SESSION['testcorrect'] = 0;
$_SESSION['testwrong'] = 0;
$_SESSION['testtotal'] = $totalcountdue;

if ($message != '') {
	echo error_message_with_hide($message,1);
}

else {  // OK

?>
<p style="margin-bottom:0;">
<input type="button" value="..[L2].." onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_test.php?type=1&amp;<?php echo $p; ?>';}" />
<input type="button" value="..[L1].." onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm';  parent.frames['l'].location.href='do_test_test.php?type=2&amp;<?php echo $p; ?>';}" />
<input type="button" value="..[••].." onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm';   parent.frames['l'].location.href='do_test_test.php?type=3&amp;<?php echo $p; ?>';}" /> &nbsp; | &nbsp; 
<input type="button" value="[L2]" onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_test.php?type=4&amp;<?php echo $p; ?>';}" />
<input type="button" value="[L1]" onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm';   parent.frames['l'].location.href='do_test_test.php?type=5&amp;<?php echo $p; ?>';}" /> &nbsp; | &nbsp; 
<input type="button" value="Table" onclick="{parent.frames['ro'].location.href='empty.htm'; parent.frames['ru'].location.href='empty.htm'; parent.frames['l'].location.href='do_test_table.php?<?php echo $p; ?>';}" />
</p></td></tr></table>

<?php

}

pageend();

?>