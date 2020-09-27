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
Call: do_test_table.php?lang=[langid]
Call: do_test_test.php?text=[textid]
Call: do_test_test.php?&selection=1  
			(SQL via $_SESSION['testsql'])
Show test frame with vocab table
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$p = '';

if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) {
	$testsql = $_SESSION['testsql']; 
}

elseif (isset($_REQUEST['lang'])) {
	$testsql = ' ' . $tbpref . 'words where WoLgID = ' . $_REQUEST['lang'] . ' '; 
}

elseif (isset($_REQUEST['text'])) {
	$testsql = ' ' . $tbpref . 'words, ' . $tbpref . 'textitems where TiLgID = WoLgID and TiTextLC = WoTextLC and TiTxID = ' . $_REQUEST['text'] . ' ';
}

else my_die("do_test_table.php called with wrong parameters");

pagestart_nobody('','html, body { margin:3px; padding:0; }');

$cntlang = get_first_value('select count(distinct WoLgID) as value from ' . $testsql);
if ($cntlang > 1) {
	echo '<p>Sorry - The selected terms are in ' . $cntlang . ' languages, but tests are only possible in one language at a time.</p>';
	pageend();
	exit();
}

$lang = get_first_value('select WoLgID as value from ' . $testsql . ' limit 1');

if (! isset($lang)) {
	echo '<p class="center">&nbsp;<br />Sorry - No terms to display or to test at this time.</p>';
	pageend();
	exit();
}

$sql = 'select LgTextSize, LgRegexpWordCharacters, LgRightToLeft from ' . $tbpref . 'languages where LgID = ' . $lang;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$textsize = round(($record['LgTextSize']-100)/2,0)+100;

$regexword = $record['LgRegexpWordCharacters'];
$rtlScript = $record['LgRightToLeft'];
mysqli_free_result($res);
$span1 = ($rtlScript ? '<span dir="rtl">' : '');
$span2 = ($rtlScript ? '</span>' : '');

$currenttabletestsetting1 = getSettingZeroOrOne('currenttabletestsetting1',1);
$currenttabletestsetting2 = getSettingZeroOrOne('currenttabletestsetting2',1);
$currenttabletestsetting3 = getSettingZeroOrOne('currenttabletestsetting3',0);
$currenttabletestsetting4 = getSettingZeroOrOne('currenttabletestsetting4',1);
$currenttabletestsetting5 = getSettingZeroOrOne('currenttabletestsetting5',0);
$currenttabletestsetting6 = getSettingZeroOrOne('currenttabletestsetting6',1);

?>
<script type="text/javascript">
//<![CDATA[
$(document).ready( function() {
	$('#cbEdit').change(function() {
		if($('#cbEdit').is(':checked')) {
			$('td:nth-child(1),th:nth-child(1)').show();
			do_ajax_save_setting('currenttabletestsetting1','1');
		} else { 
			$('td:nth-child(1),th:nth-child(1)').hide();
			do_ajax_save_setting('currenttabletestsetting1','0');
		}
	});
	
	$('#cbStatus').change(function() {
		if($('#cbStatus').is(':checked')) {
			$('td:nth-child(2),th:nth-child(2)').show();
			do_ajax_save_setting('currenttabletestsetting2','1');
		} else { 
			$('td:nth-child(2),th:nth-child(2)').hide();
			do_ajax_save_setting('currenttabletestsetting2','0');
		}
	});
	
	$('#cbTerm').change(function() {
		if($('#cbTerm').is(':checked')) {
			$('td:nth-child(3)').css('color', 'black').css('cursor', 'auto');
			do_ajax_save_setting('currenttabletestsetting3','1');
		} else { 
			$('td:nth-child(3)').css('color', 'white').css('cursor', 'pointer');
			do_ajax_save_setting('currenttabletestsetting3','0');
		}
	});
	
	$('#cbTrans').change(function() {
		if($('#cbTrans').is(':checked')) {
			$('td:nth-child(4)').css('color', 'black').css('cursor', 'auto');
			do_ajax_save_setting('currenttabletestsetting4','1');
		} else {
			$('td:nth-child(4)').css('color', 'white').css('cursor', 'pointer');
			do_ajax_save_setting('currenttabletestsetting4','0');
		}
	});
	
	$('#cbRom').change(function() {
		if($('#cbRom').is(':checked')) {
			$('td:nth-child(5),th:nth-child(5)').show();
			do_ajax_save_setting('currenttabletestsetting5','1');
		} else {
			$('td:nth-child(5),th:nth-child(5)').hide();
			do_ajax_save_setting('currenttabletestsetting5','0');
		}
	});
	
	$('#cbSentence').change(function() {
		if($('#cbSentence').is(':checked')) {
			$('td:nth-child(6),th:nth-child(6)').show();
			do_ajax_save_setting('currenttabletestsetting6','1');
		} else {
			$('td:nth-child(6),th:nth-child(6)').hide();
			do_ajax_save_setting('currenttabletestsetting6','0');
		}
	});
	
	$('td').click(function() {
		$(this).css('color', 'black').css('cursor', 'auto');
	});
	
	$('td').css('background-color', 'white');
	
	$('#cbEdit').change();
	$('#cbStatus').change();
	$('#cbTerm').change();
	$('#cbTrans').change();
	$('#cbRom').change();
	$('#cbSentence').change();
	 
});
//]]>
</script>
<p>
<input type="checkbox" id="cbEdit" <?php echo get_checked($currenttabletestsetting1); ?> /> Edit
<input type="checkbox" id="cbStatus" <?php echo get_checked($currenttabletestsetting2); ?> /> Status
<input type="checkbox" id="cbTerm" <?php echo get_checked($currenttabletestsetting3); ?> /> Term
<input type="checkbox" id="cbTrans" <?php echo get_checked($currenttabletestsetting4); ?> /> Translation
<input type="checkbox" id="cbRom" <?php echo get_checked($currenttabletestsetting5); ?> /> Romanization
<input type="checkbox" id="cbSentence" <?php echo get_checked($currenttabletestsetting6); ?> /> Sentence
</p>

<table class="sortable tab1" style="width:auto;" cellspacing="0" cellpadding="5">
<tr>
<th class="th1">Ed</th>
<th class="th1 clickable">Status</th>
<th class="th1 clickable">Term</th>
<th class="th1 clickable">Translation</th>
<th class="th1 clickable">Romanization</th>
<th class="th1 clickable">Sentence</th>
</tr>
<?php

$sql = 'SELECT DISTINCT WoID, WoText, WoTranslation, WoRomanization, WoSentence, WoStatus, WoTodayScore As Score FROM ' . $testsql . ' AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != \'\' AND WoTranslation != \'*\' order by WoTodayScore, WoRandom*RAND()';
if ($debug) echo $sql;
$res = do_mysqli_query($sql);
while ($record = mysqli_fetch_assoc($res)) {
	$sent = tohtml(repl_tab_nl($record["WoSentence"]));
	$sent1 = str_replace("{", ' <b>[', str_replace("}", ']</b> ', 
		mask_term_in_sentence($sent,$regexword)));
?>
<tr>
<td class="td1 center" nowrap="nowrap"><a href="edit_tword.php?wid=<?php echo $record['WoID']; ?>" target="ro"><img src="icn/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" /></a></td>
<td class="td1 center" nowrap="nowrap"><span id="STAT<?php echo $record['WoID']; ?>"><?php echo make_status_controls_test_table($record['Score'], $record['WoStatus'], $record['WoID']); ?></span></td>
<td class="td1 center" style="font-size:<?php echo $textsize; ?>%;"><?php echo $span1; ?><span id="TERM<?php echo $record['WoID']; ?>"><?php echo tohtml($record['WoText']); ?></span><?php echo $span2; ?></td>
<td class="td1 center"><span id="TRAN<?php echo $record['WoID']; ?>"><?php echo tohtml($record['WoTranslation']); ?></span></td>
<td class="td1 center"><span id="ROMA<?php echo $record['WoID']; ?>"><?php echo tohtml($record['WoRomanization']); ?></span></td>
<td class="td1 center"><?php echo $span1; ?><span id="SENT<?php echo $record['WoID']; ?>"><?php echo $sent1; ?></span><?php echo $span2; ?></td>
</tr>
<?php
}
mysqli_free_result($res);

?>
</table>
<?php

pageend();

?>