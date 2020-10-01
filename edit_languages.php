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
Call: edit_languages.php?....
      ... refresh=[langid] ... reparse all texts in lang
      ... del=[langid] ... do delete
      ... op=Save ... do insert new 
      ... op=Change ... do update 
      ... new=1 ... display new lang. screen 
      ... chg=[langid] ... display edit screen 
Manage languages
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

pagestart('My Languages',true);

?>

<script type="text/javascript">
//<![CDATA[
<?php echo "var LANGUAGES = " . json_encode(get_languages()) . ";\n"; ?>

function check_dupl_lang(curr) {
	// Check if langname exists and its lang# != curr
	var l = $('#LgName').val();
	if(l in LANGUAGES) {
		if (curr != LANGUAGES[l]) {
			alert ('Language "' + l + '" exists already. Please change the language name!');
			$('#LgName').focus();
			return false;
		}
	}
	return true;
}

//]]>
</script>

<?php

$message = '';

// REFRESH 

if (isset($_REQUEST['refresh'])) {
	$id = $_REQUEST['refresh'] + 0;
	$message2 = runsql('delete from ' . $tbpref . 'sentences where SeLgID = ' . $id, 
		"Sentences deleted");
	$message3 = runsql('delete from ' . $tbpref . 'textitems where TiLgID = ' . $id, 
		"Text items deleted");
	adjust_autoincr('sentences','SeID');
	adjust_autoincr('textitems','TiID');
	$sql = "select TxID, TxText from " . $tbpref . "texts where TxLgID = " . $id . " order by TxID";
	$res = do_mysqli_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$txtid = $record["TxID"];
		$txttxt = $record["TxText"];
		splitCheckText($txttxt, $id, $txtid );
	}
	mysqli_free_result($res);
	$message = $message2 . " / " . $message3 . " / Sentences added: " . get_first_value('select count(*) as value from ' . $tbpref . 'sentences where SeLgID = ' . $id) . " / Text items added: " . get_first_value('select count(*) as value from ' . $tbpref . 'textitems where TiLgID = ' . $id);
}

// DEL

if (isset($_REQUEST['del'])) {
	$anztexts = get_first_value(
		'select count(TxID) as value from ' . $tbpref . 'texts where TxLgID = ' . 
		$_REQUEST['del']);
	$anzarchtexts = get_first_value(
		'select count(AtID) as value from ' . $tbpref . 'archivedtexts where AtLgID = ' . 
		$_REQUEST['del']);
	$anzwords = get_first_value(
		'select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . 
		$_REQUEST['del']);
	if ( $anztexts > 0 || $anzarchtexts > 0 || $anzwords > 0) {
		$message = 'You must first delete texts, archived texts and words with this language!';
	} else {
		$message = runsql('delete from ' . $tbpref . 'languages where LgID = ' . $_REQUEST['del'], 
			"Deleted");
		adjust_autoincr('languages','LgID');
	}
}

// INS/UPD

elseif (isset($_REQUEST['op'])) {
	
	// INSERT
	
	if ($_REQUEST['op'] == 'Save')
		$message = runsql('insert into ' . $tbpref . 'languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, LgExportTemplate, LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft) values(' . 
		convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' .
		convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', '. 
		convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', '.
		$_REQUEST["LgTextSize"] . ', '.
		convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', '.
		convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', '.
		$_REQUEST["LgRemoveSpaces"] . ', '.
		$_REQUEST["LgSplitEachChar"] . ', '.
		$_REQUEST["LgRightToLeft"] . 
		')', 'Saved');
	
	// UPDATE
	
	elseif ($_REQUEST['op'] == 'Change') {
		// Get old values
		$sql = "select * from " . $tbpref . "languages where LgID=" . $_REQUEST["LgID"];
		$res = do_mysqli_query($sql);
		$record = mysqli_fetch_assoc($res);
		if ($record == FALSE) my_die("Cannot access language data: $sql");
		$oldCharacterSubstitutions = $record['LgCharacterSubstitutions'];
		$oldRegexpSplitSentences = $record['LgRegexpSplitSentences'];
		$oldExceptionsSplitSentences = $record['LgExceptionsSplitSentences'];
		$oldRegexpWordCharacters = $record['LgRegexpWordCharacters'];
		$oldRemoveSpaces = $record['LgRemoveSpaces'];
		$oldSplitEachChar = $record['LgSplitEachChar'];
		mysqli_free_result($res);
	
		$needReParse = 
		(convert_string_to_sqlsyntax_notrim_nonull( $_REQUEST["LgCharacterSubstitutions"]) != convert_string_to_sqlsyntax_notrim_nonull( $oldCharacterSubstitutions)) 
			||
		(convert_string_to_sqlsyntax( $_REQUEST["LgRegexpSplitSentences"]) != convert_string_to_sqlsyntax( $oldRegexpSplitSentences)) 
			||
		(convert_string_to_sqlsyntax_notrim_nonull( $_REQUEST["LgExceptionsSplitSentences"]) != convert_string_to_sqlsyntax_notrim_nonull( $oldExceptionsSplitSentences)) 
			||
		(convert_string_to_sqlsyntax( $_REQUEST["LgRegexpWordCharacters"]) != convert_string_to_sqlsyntax( $oldRegexpWordCharacters)) 
			||
		($_REQUEST["LgRemoveSpaces"] != $oldRemoveSpaces) 
			||
		($_REQUEST["LgSplitEachChar"] != $oldSplitEachChar) 
			;
		$needReParse = ($needReParse ? 1 : 0);
	
		$message = runsql('update ' . $tbpref . 'languages set ' . 
		'LgName = ' . convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' . 
		'LgDict1URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', ' .
		'LgDict2URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', ' .
		'LgGoogleTranslateURI = ' . convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
		'LgExportTemplate = ' . convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', ' .
		'LgTextSize = ' . $_REQUEST["LgTextSize"] . ', ' .
		'LgCharacterSubstitutions = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
		'LgRegexpSplitSentences = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
		'LgExceptionsSplitSentences = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
		'LgRegexpWordCharacters = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
		'LgRemoveSpaces = ' . $_REQUEST["LgRemoveSpaces"] . ', ' .
		'LgSplitEachChar = ' . $_REQUEST["LgSplitEachChar"] . ', ' . 
		'LgRightToLeft = ' . $_REQUEST["LgRightToLeft"] . 
		' where LgID = ' . $_REQUEST["LgID"], 'Updated');
		
		if ($needReParse) {
			$id = $_REQUEST["LgID"] + 0;
			runsql('delete from ' . $tbpref . 'sentences where SeLgID = ' . $id, 
				"Sentences deleted");
			runsql('delete from ' . $tbpref . 'textitems where TiLgID = ' . $id, 
				"Text items deleted");
			adjust_autoincr('sentences','SeID');
			adjust_autoincr('textitems','TiID');
			$sql = "select TxID, TxText from " . $tbpref . "texts where TxLgID = " . $id . " order by TxID";
			$res = do_mysqli_query($sql);
			$cntrp = 0;
			while ($record = mysqli_fetch_assoc($res)) {
				$txtid = $record["TxID"];
				$txttxt = $record["TxText"];
				splitCheckText($txttxt, $id, $txtid );
				$cntrp++;
			}
			mysqli_free_result($res);
			$message .= " / Reparsed texts: " . $cntrp;
		} else {
			$message .= " / Reparsing not needed";
		}

	}

}

// NEW

if (isset($_REQUEST['new'])) {
	
	?>
	
	<h4>New Language <a target="_blank" href="info.htm#howtolang"><img src="icn/question-frame.png" title="Help" alt="Help" /></a> </h4>

	<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>	
	<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return check_dupl_lang(0);">

	<table class="tab1" cellspacing="0" cellpadding="5">
	<tr>
	<td class="td1 center backlightyellow" colspan="2"><img src="icn/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" class="click" onclick="window.open('select_lang_pair.php', 'wizard', 'width=400, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no');" /><br /><span class="click" onclick="window.open('select_lang_pair.php', 'wizard', 'width=400, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no');"><img src="icn/arrow-000-medium.png" title="-&gt;" alt="-&gt;" /> <b>Language Settings Wizard</b> <img src="icn/arrow-180-medium.png" title="&lt;-" alt="&lt;-" /></span><br /><span class="smallgray">Select your native (L1) and study (L2) languages, and let the wizard set all language settings marked in yellow!<br />(You can adjust the settings afterwards.)</span></td>
	</tr>
	</table>
	
	<table class="tab1" cellspacing="0" cellpadding="5">
	<tr>
	<td class="td1 right backlightyellow">Study Language "L2":</td>
	<td class="td1"><input type="text" class="notempty setfocus checkoutsidebmp" data_info="Study Language" name="LgName" id="LgName" value="" maxlength="40" size="40" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">Dictionary 1 URI:</td>
	<td class="td1"><input type="text" class="checkdicturl notempty checkoutsidebmp" name="LgDict1URI" value="" maxlength="200" size="60" data_info="Dictionary 1 URI" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Dictionary 2 URI:</td>
	<td class="td1"><input type="text" class="checkdicturl checkoutsidebmp" name="LgDict2URI" value="" maxlength="200" size="60" data_info="Dictionary 2 URI" /></td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">GoogleTranslate URI:</td>
	<td class="td1"><input type="text" class="checkdicturl checkoutsidebmp" name="LgGoogleTranslateURI" value="*http://translate.google.com/?ie=UTF-8&sl=••&tl=••&text=###" maxlength="200" size="60" data_info="GoogleTranslate URI" /></td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">Text Size:</td>
	<td class="td1"><select name="LgTextSize" class="notempty"><?php echo get_languagessize_selectoptions(150); ?></select></td>
	</tr>
	<tr>
	<td class="td1 right">Character Substitutions:</td>
	<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Character Substitutions" name="LgCharacterSubstitutions" value="´='|`='|’='|‘='|...=…|..=‥" maxlength="500" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">RegExp Split Sentences:</td>
	<td class="td1"><input type="text" class="notempty checkoutsidebmp" name="LgRegexpSplitSentences" value=".!?:;" maxlength="500" size="60" data_info="RegExp Split Sentences" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Exceptions Split Sentences:</td>
	<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Exceptions Split Sentences" name="LgExceptionsSplitSentences" value="Mr.|Dr.|[A-Z].|Vd.|Vds." maxlength="500" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">RegExp Word Characters:</td>
	<td class="td1"><input type="text" class="notempty checkoutsidebmp" data_info="RegExp Word Characters" name="LgRegexpWordCharacters" value="a-zA-ZÀ-ÖØ-öø-ȳ" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">Make each character a word:</td>
	<td class="td1"><select name="LgSplitEachChar"><?php echo get_yesno_selectoptions(0); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">Remove spaces:</td>
	<td class="td1"><select name="LgRemoveSpaces"><?php echo get_yesno_selectoptions(0); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
	</tr>
	<tr>
	<td class="td1 right backlightyellow">Right-To-Left Script:</td>
	<td class="td1"><select name="LgRightToLeft"><?php echo get_yesno_selectoptions(0); ?></select> (e.g. for Arabic, Hebrew, Farsi, Urdu, etc.)</td>
	</tr>
	<tr>
	<td class="td1 right">Export Template <img class="click" src="icn/question-frame.png" title="Help" alt="Help" onclick="oewin('info_export_template.htm');" /> :</td>
	<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Export Template" name="LgExportTemplate" value="$y\t$t\n" maxlength="1000" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right" colspan="2"><input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_languages.php';}" /> 
	<input type="submit" name="op" value="Save" /></td>
	</tr>
	</table>
	<p class="smallgray"><b>Important:</b><br />The placeholders "••" for the from/sl and dest/tl language codes in the URIs must be <b>replaced</b> by the actual source and target language codes!<br /><a href="info.htm#howtolang" target="_blank">Please read the documentation</a>. Languages with a <b>non-Latin alphabet need special attention</b>, <a href="info.htm#langsetup" target="_blank">see also here</a>.</p>	
	</form>
	
	<?php
	
}

// CHG

elseif (isset($_REQUEST['chg'])) {
	
	$sql = 'select * from ' . $tbpref . 'languages where LgID = ' . $_REQUEST['chg'];
	$res = do_mysqli_query($sql);
	if ($record = mysqli_fetch_assoc($res)) {
	
		?>
	
		<h4>Edit Language <a target="_blank" href="info.htm#howtolang"><img src="icn/question-frame.png" title="Help" alt="Help" /></a> </h4>
		<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>	
		<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return check_dupl_lang(<?php echo $_REQUEST['chg']; ?>);">
		<input type="hidden" name="LgID" value="<?php echo $_REQUEST['chg']; ?>" />
		<table class="tab1" cellspacing="0" cellpadding="5">
		<tr>
		<td class="td1 right">Study Language "L2":</td>
		<td class="td1"><input type="text" class="notempty setfocus checkoutsidebmp" data_info="Study Language" name="LgName" id="LgName" value="<?php echo tohtml($record['LgName']); ?>" maxlength="40" size="40" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Dictionary 1 URI:</td>
		<td class="td1"><input type="text" class="notempty checkdicturl checkoutsidebmp" name="LgDict1URI" value="<?php echo tohtml($record['LgDict1URI']); ?>"  maxlength="200" size="60" data_info="Dictionary 1 URI" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Dictionary 2 URI:</td>
		<td class="td1"><input type="text" class="checkdicturl checkoutsidebmp" name="LgDict2URI" value="<?php echo tohtml($record['LgDict2URI']); ?>" maxlength="200" size="60" data_info="Dictionary 2 URI" /></td>
		</tr>
		<tr>
		<td class="td1 right">GoogleTranslate URI:</td>
		<td class="td1"><input type="text" class="checkdicturl checkoutsidebmp" name="LgGoogleTranslateURI" value="<?php echo tohtml($record['LgGoogleTranslateURI']); ?>" maxlength="200" size="60" data_info="GoogleTranslate URI" /></td>
		</tr>
		<tr>
		<td class="td1 right">Text Size:</td>
		<td class="td1"><select name="LgTextSize"><?php echo get_languagessize_selectoptions($record['LgTextSize']); ?></select></td>
		</tr>
		<tr>
		<td class="td1 right">Character Substitutions:</td>
		<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Character Substitutions" name="LgCharacterSubstitutions" value="<?php echo tohtml($record['LgCharacterSubstitutions']); ?>" maxlength="500" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">RegExp Split Sentences:</td>
		<td class="td1"><input type="text" class="notempty checkoutsidebmp" name="LgRegexpSplitSentences" value="<?php echo tohtml($record['LgRegexpSplitSentences']); ?>" maxlength="500" size="60" data_info="RegExp Split Sentences" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Exceptions Split Sentences:</td>
		<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Exceptions Split Sentences" name="LgExceptionsSplitSentences" value="<?php echo tohtml($record['LgExceptionsSplitSentences']); ?>" maxlength="500" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">RegExp Word Characters:</td>
		<td class="td1"><input type="text" class="notempty checkoutsidebmp" data_info="RegExp Word Characters" name="LgRegexpWordCharacters" value="<?php echo tohtml($record['LgRegexpWordCharacters']); ?>" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Make each character a word:</td>
		<td class="td1"><select name="LgSplitEachChar"><?php echo get_yesno_selectoptions($record['LgSplitEachChar']); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
		</tr>
		<tr>
		<td class="td1 right">Remove spaces:</td>
		<td class="td1"><select name="LgRemoveSpaces"><?php echo get_yesno_selectoptions($record['LgRemoveSpaces']); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
		</tr>
		<tr>
		<td class="td1 right">Right-To-Left Script:</td>
		<td class="td1"><select name="LgRightToLeft"><?php echo get_yesno_selectoptions($record['LgRightToLeft']); ?></select> (e.g. for Arabic, Hebrew, Farsi, Urdu,  etc.)</td>
		</tr>
		<tr>
		<td class="td1 right">Export Template <img class="click" src="icn/question-frame.png" title="Help" alt="Help" onclick="oewin('info_export_template.htm');" /> :</td>
		<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Export Template" name="LgExportTemplate" value="<?php echo tohtml($record['LgExportTemplate']); ?>" maxlength="1000" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right" colspan="2"><input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_languages.php';}" /> 
		<input type="submit" name="op" value="Change" /></td>
		</tr>
		</table>
		<p class="smallgray"><b>Warning:</b> Changing certain language settings (e.g. RegExp Word Characters, etc.)<br />may cause partial or complete loss of improved annotated texts!</p>
		</form>
		<?php

	}
	mysqli_free_result($res);
}

// DISPLAY

else {
	
	echo error_message_with_hide($message,0);
	
	$current = (int) getSetting('currentlanguage');
	
	$recno = get_first_value('select count(*) as value from ' . $tbpref . 'languages'); 
	
?>

<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1"><img src="icn/plus-button.png" title="New" alt="New" /> New Language ...</a></p>

<?php
if ($recno==0) {
?>
<p>No languages found.</p>
<?php
} else {
?>

<table class="sortable tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Curr.<br />Lang.</th>
<th class="th1 sorttable_nosort">Test<br />↓↓↓</th>
<th class="th1 sorttable_nosort">Actions</th>
<th class="th1 clickable">Language</th>
<th class="th1 sorttable_numeric clickable">Texts,<br />Reparse</th>
<th class="th1 sorttable_numeric clickable">Arch.<br />Texts</th>
<th class="th1 sorttable_numeric clickable">Terms</th>
<th class="th1 sorttable_nosort">Export<br />Template?</th>
</tr>

<?php

$sql = 'select LgID, LgName, LgExportTemplate from ' . $tbpref . 'languages order by LgName';
if ($debug) echo $sql;
$res = do_mysqli_query($sql);
while ($record = mysqli_fetch_assoc($res)) {
	$textcount = get_first_value('select count(TxID) as value from ' . $tbpref . 'texts where TxLgID=' . $record['LgID']);
	$archtextcount = get_first_value('select count(AtID) as value from ' . $tbpref . 'archivedtexts where AtLgID=' . $record['LgID']);
	$wordcount = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID=' . $record['LgID']);
	echo '<tr>';
	if ( $current == $record['LgID'] ) {
		$tdth = 'th';
		echo '<th class="th1"><img src="icn/exclamation-red.png" title="Current Language" alt="Current Language" /></th>';
	} else {
		$tdth = 'td';
		echo '<td class="td1 center"><a href="save_setting_redirect.php?k=currentlanguage&amp;v=' . $record['LgID'] . '&amp;u=edit_languages.php"><img src="icn/tick-button.png" title="Set as Current Language" alt="Set as Current Language" /></a></td>';
	}
	echo '<' . $tdth . ' class="' . $tdth . '1 center"><a href="do_test.php?lang=' . $record['LgID'] . '"><img src="icn/question-balloon.png" title="Test" alt="Test" /></a></' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center" nowrap="nowrap">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['LgID'] . '"><img src="icn/document--pencil.png" title="Edit" alt="Edit" /></a>';
	if ($textcount == 0 && $archtextcount == 0 && $wordcount == 0) 
		echo '&nbsp; <span class="click" onclick="if (confirmDelete()) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $record['LgID'] . '\';"><img src="icn/minus-button.png" title="Delete" alt="Delete" /></span>';
	else 
		echo '&nbsp; <img src="icn/placeholder.png" title="Delete not possible" alt="Delete not possible" />';
	echo '&nbsp;</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . tohtml($record['LgName']) . '</' . $tdth . '>';
	if ($textcount > 0) 
		echo '<' . $tdth . ' class="' . $tdth . '1 center"><a href="edit_texts.php?page=1&amp;query=&amp;filterlang=' . $record['LgID'] . '">' . $textcount . '</a> &nbsp;&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?refresh=' . $record['LgID'] . '"><img src="icn/lightning.png" title="Reparse Texts" alt="Reparse Texts" /></a>';
	else
		echo '<' . $tdth . ' class="' . $tdth . '1 center">0 &nbsp;&nbsp; <img src="icn/placeholder.png" title="No texts to reparse" alt="No texts to reparse" />';
	echo '</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . ($archtextcount > 0 ? '<a href="edit_archivedtexts.php?page=1&amp;query=&amp;filterlang=' . $record['LgID'] . '">' . $archtextcount . '</a>' : '0' ) . '</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . ($wordcount > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=' . $record['LgID'] . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">' . $wordcount . '</a>' : '0' ) . '</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . (isset($record['LgExportTemplate']) ? '<img src="icn/status.png" title="Yes" alt="Yes" />' : '<img src="icn/status-busy.png" title="No" alt="No" />' ) . '</' . $tdth . '>';
	echo '</tr>';
}
mysqli_free_result($res);

?>

</table>

<?php

}
}

pageend();

?> 
