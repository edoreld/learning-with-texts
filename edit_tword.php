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
Call: edit_tword.php?....
      ... op=Change ... do update
      ... wid=[wordid] ... display edit screen  
Edit term while testing
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );
require_once( 'simterms.inc.php' );

$translation_raw = repl_tab_nl(getreq("WoTranslation"));
if ( $translation_raw == '' ) $translation = '*';
else $translation = $translation_raw;

// UPDATE

if (isset($_REQUEST['op'])) {
	
	$textlc = trim(prepare_textdata($_REQUEST["WoTextLC"]));
	$text = trim(prepare_textdata($_REQUEST["WoText"]));
	
	if (mb_strtolower($text, 'UTF-8') == $textlc) {
	
		// UPDATE
		
		if ($_REQUEST['op'] == 'Change') {
			
			$titletext = "Edit Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
			pagestart_nobody($titletext);
			echo '<h4><span class="bigger">' . $titletext . '</span></h4>';
			
			$oldstatus = $_REQUEST["WoOldStatus"];
			$newstatus = $_REQUEST["WoStatus"];
			$xx = '';
			if ($oldstatus != $newstatus) $xx = ', WoStatus = ' .	$newstatus . ', WoStatusChanged = NOW()';
		
			$message = runsql('update ' . $tbpref . 'words set WoText = ' . 
			convert_string_to_sqlsyntax($_REQUEST["WoText"]) . ', WoTranslation = ' . 
			convert_string_to_sqlsyntax($translation) . ', WoSentence = ' . 
			convert_string_to_sqlsyntax(repl_tab_nl($_REQUEST["WoSentence"])) . ', WoRomanization = ' .
			convert_string_to_sqlsyntax($_REQUEST["WoRomanization"]) . $xx . ',' . make_score_random_insert_update('u') . ' where WoID = ' . $_REQUEST["WoID"], "Updated");
			$wid = $_REQUEST["WoID"];
			saveWordTags($wid);
			
		}  // $_REQUEST['op'] == 'Change'

	} // (mb_strtolower($text, 'UTF-8') == $textlc)
	
	else { // (mb_strtolower($text, 'UTF-8') != $textlc)
	
		$titletext = "New/Edit Term: " . tohtml(prepare_textdata($_REQUEST["WoTextLC"]));
		pagestart_nobody($titletext);
		echo '<h4><span class="bigger">' . $titletext . '</span></h4>';		
		$message = 'Error: Term in lowercase must be exactly = "' . $textlc . '", please go back and correct this!'; 
		echo error_message_with_hide($message,0);
		pageend();
		exit();
	
	}

?>
	
<p>OK: <?php echo tohtml($message); ?></p>

<?php

	$lang = get_first_value('select WoLgID as value from ' . $tbpref . 'words where WoID = ' . $wid);
	if ( ! isset($lang) ) my_die('Cannot retrieve language in edit_tword.php');
	$regexword = get_first_value('select LgRegexpWordCharacters as value from ' . $tbpref . 'languages where LgID = ' . $lang);
	if ( ! isset($regexword) ) my_die('Cannot retrieve language data in edit_tword.php');
	$sent = tohtml(repl_tab_nl($_REQUEST["WoSentence"]));
	$sent1 = str_replace("{", ' <b>[', str_replace("}", ']</b> ', 
		mask_term_in_sentence($sent,$regexword)));

?>

<script type="text/javascript">
//<![CDATA[
var context = window.parent.frames['l'].document;
var woid = <?php echo prepare_textdata_js($wid); ?>;
if(window.parent.frames['l'].location.href.indexOf('do_test_table') !== -1) {
	// Table Test
	$('#STAT' + woid, context).html(<?php echo prepare_textdata_js(make_status_controls_test_table(1, $_REQUEST["WoStatus"], $wid)); ?>);
	$('#TERM' + woid, context).html(<?php echo prepare_textdata_js(tohtml($_REQUEST["WoText"])); ?>);
	$('#TRAN' + woid, context).html(<?php echo prepare_textdata_js(tohtml($translation)); ?>);
	$('#ROMA' + woid, context).html(<?php echo prepare_textdata_js(tohtml($_REQUEST["WoRomanization"])); ?>);
	$('#SENT' + woid, context).html(<?php echo prepare_textdata_js($sent1); ?>);
} else {
	// Normal Test
	var wotext = <?php echo prepare_textdata_js($_REQUEST["WoText"]); ?>;
	var status = <?php echo prepare_textdata_js($_REQUEST["WoStatus"]); ?>;
	var trans = <?php echo prepare_textdata_js($translation . getWordTagList($wid,' ',1,0)); ?>;
	var roman = <?php echo prepare_textdata_js($_REQUEST["WoRomanization"]); ?>;
	$('.word' + woid, context).attr('data_text',wotext).attr('data_trans',trans).attr('data_rom',roman).attr('data_status',status);
}
window.parent.frames['l'].focus();
window.parent.frames['l'].setTimeout('cClick()', 100);
//]]>
</script>
	
<?php

} // if (isset($_REQUEST['op']))

// FORM

else {  // if (! isset($_REQUEST['op']))

	$wid = getreq('wid');
	
	if ($wid == '') my_die("Term ID missing in edit_tword.php");
	
	$sql = 'select WoText, WoLgID, WoTranslation, WoSentence, WoRomanization, WoStatus from ' . $tbpref . 'words where WoID = ' . $wid;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	if ( $record ) {
		$term = $record['WoText'];
		$lang = $record['WoLgID'];
		$transl = repl_tab_nl($record['WoTranslation']);
		if($transl == '*') $transl='';
		$sentence = repl_tab_nl($record['WoSentence']);
		$rom = $record['WoRomanization'];
		$status = $record['WoStatus'];
	} else {
		my_die("Term data not found in edit_tword.php");
	}
	mysqli_free_result($res);
	
	$termlc =	mb_strtolower($term, 'UTF-8');
	$titletext = "Edit Term: " . tohtml($term);
	pagestart_nobody($titletext);
	$scrdir = getScriptDirectionTag($lang);

?>
<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>
	
<form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
<input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $status; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo tohtml($termlc); ?>" />
<table class="tab2" cellspacing="0" cellpadding="5">
<tr title="Only change uppercase/lowercase!">
<td class="td1 right"><b>Edit Term:</b></td>
<td class="td1"><input <?php echo $scrdir; ?> class="notempty checkoutsidebmp" data_info="Term" type="text" name="WoText" id="wordfield" value="<?php echo tohtml($term); ?>" maxlength="250" size="35" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td></tr>
<?php print_similar_terms_tabrow(); ?>
<tr>
<td class="td1 right">Translation:</td>
<td class="td1"><textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo tohtml($transl); ?></textarea></td>
</tr>
<tr>
<td class="td1 right">Tags:</td>
<td class="td1">
<?php echo getWordTags($wid); ?>
</td>
</tr>
<tr>
<td class="td1 right">Romaniz.:</td>
<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" maxlength="100" size="35" value="<?php echo tohtml($rom); ?>" /></td>
</tr>
<tr>
<td class="td1 right">Sentence<br />Term in {...}:</td>
<td class="td1"><textarea <?php echo $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?php echo tohtml($sentence); ?></textarea></td>
</tr>
<tr>
<td class="td1 right">Status:</td>
<td class="td1">
<?php echo get_wordstatus_radiooptions($status); ?>
</td>
</tr>
<tr>
<td class="td1 right" colspan="2">
<?php echo createDictLinksInEditWin($lang,$term,'document.forms[0].WoSentence',1); ?>
&nbsp; &nbsp; &nbsp; 
<input type="submit" name="op" value="Change" /></td>
</tr>
</table>
</form>
<div id="exsent"><span class="click" onclick="do_ajax_show_sentences(<?php echo $lang; ?>, <?php echo prepare_textdata_js($termlc) . ', ' . prepare_textdata_js("document.forms['editword'].WoSentence"); ?>);"><img src="icn/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" /> Show Sentences</span></div>	
<?php
} // if (! isset($_REQUEST['op']))

pageend();

?>