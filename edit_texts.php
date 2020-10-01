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
Call: edit_texts.php?....
      ... markaction=[opcode] ... do actions on marked texts
      ... del=[textid] ... do delete
      ... arch=[textid] ... do archive
      ... op=Check ... do check
      ... op=Save ... do insert new 
      ... op=Change ... do update
      ... op=Save+and+Open ... do insert new and open 
      ... op=Change+and+Open ... do update and open
      ... new=1 ... display new text screen 
      ... chg=[textid] ... display edit screen 
      ... filterlang=[langid] ... language filter 
      ... sort=[sortcode] ... sort 
      ... page=[pageno] ... page  
      ... query=[titlefilter] ... title filter   
Manage active texts
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

// Page, Sort, etc. 

$currentlang = validateLang(processDBParam("filterlang",'currentlanguage','',0));
$currentsort = processDBParam("sort",'currenttextsort','1',1);

$currentpage = processSessParam("page","currenttextpage",'1',1);
$currentquery = processSessParam("query","currenttextquery",'',0);
$currenttag1 = validateTextTag(processSessParam("tag1","currenttexttag1",'',0),$currentlang);
$currenttag2 = validateTextTag(processSessParam("tag2","currenttexttag2",'',0),$currentlang);
$currenttag12 = processSessParam("tag12","currenttexttag12",'',0);

$wh_lang = ($currentlang != '') ? (' and TxLgID=' . $currentlang) : '';
$wh_query = convert_string_to_sqlsyntax(str_replace("*","%",mb_strtolower($currentquery, 'UTF-8')));
$wh_query = ($currentquery != '') ? (' and TxTitle like ' . $wh_query) : '';

if ($currenttag1 == '' && $currenttag2 == '')
	$wh_tag = '';
else {
	if ($currenttag1 != '') {
		if ($currenttag1 == -1)
			$wh_tag1 = "group_concat(TtT2ID) IS NULL";
		else
			$wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'";
	} 
	if ($currenttag2 != '') {
		if ($currenttag2 == -1)
			$wh_tag2 = "group_concat(TtT2ID) IS NULL";
		else
			$wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'";
	} 
	if ($currenttag1 != '' && $currenttag2 == '')	
		$wh_tag = " having (" . $wh_tag1 . ') ';
	elseif ($currenttag2 != '' && $currenttag1 == '')	
		$wh_tag = " having (" . $wh_tag2 . ') ';
	else
		$wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) ';
}

$no_pagestart = (getreq('markaction') == 'test' || getreq('markaction') == 'deltag' || substr(getreq('op'),-8) == 'and Open');

if (! $no_pagestart) {
	pagestart('My ' . getLanguage($currentlang) . ' Texts',true);
}

$message = '';

// MARK ACTIONS

if (isset($_REQUEST['markaction'])) {
	$markaction = $_REQUEST['markaction'];
	$actiondata = stripTheSlashesIfNeeded(getreq('data'));
	$message = "Multiple Actions: 0";
	if (isset($_REQUEST['marked'])) {
		if (is_array($_REQUEST['marked'])) {
			$l = count($_REQUEST['marked']);
			if ($l > 0 ) {
				$list = "(" . $_REQUEST['marked'][0];
				for ($i=1; $i<$l; $i++) $list .= "," . $_REQUEST['marked'][$i];
				$list .= ")";
				
				if ($markaction == 'del') {
					$message3 = runsql('delete from ' . $tbpref . 'textitems where TiTxID in ' . $list, "Text items deleted");
					$message2 = runsql('delete from ' . $tbpref . 'sentences where SeTxID in ' . $list, "Sentences deleted");
					$message1 = runsql('delete from ' . $tbpref . 'texts where TxID in ' . $list, "Texts deleted");
					$message = $message1 . " / " . $message2 . " / " . $message3;
					adjust_autoincr('texts','TxID');
					adjust_autoincr('sentences','SeID');
					adjust_autoincr('textitems','TiID');
					runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL",'');
				} 
				
				elseif ($markaction == 'arch') {
					runsql('delete from ' . $tbpref . 'textitems where TiTxID in ' . $list, "");
					runsql('delete from ' . $tbpref . 'sentences where SeTxID in ' . $list, "");
					$count = 0;
					$sql = "select TxID from " . $tbpref . "texts where TxID in " . $list;
					$res = do_mysqli_query($sql);
					while ($record = mysqli_fetch_assoc($res)) {
						$id = $record['TxID'];
						$count += (0 + runsql('insert into ' . $tbpref . 'archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) select TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $id, ""));
						$aid = get_last_key();
						runsql('insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) select ' . $aid . ', TtT2ID from ' . $tbpref . 'texttags where TtTxID = ' . $id, "");	
					}
					mysqli_free_result($res);
					$message = 'Text(s) archived: ' . $count;
					runsql('delete from ' . $tbpref . 'texts where TxID in ' . $list, "");
					runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL",'');
					adjust_autoincr('texts','TxID');
					adjust_autoincr('sentences','SeID');
					adjust_autoincr('textitems','TiID');
				} 
				
				elseif ($markaction == 'addtag' ) {
					$message = addtexttaglist($actiondata,$list);
				}
				
				elseif ($markaction == 'deltag' ) {
					$message = removetexttaglist($actiondata,$list);
					header("Location: edit_texts.php");
					exit();
				}
				
				elseif ($markaction == 'setsent') {
					$count = 0;
					$sql = "select WoID, WoTextLC, min(TiSeID) as SeID from " . $tbpref . "words, " . $tbpref . "textitems where TiLgID = WoLgID and TiTextLC = WoTextLC and TiTxID in " . $list . " and ifnull(WoSentence,'') not like concat('%{',WoText,'}%') group by WoID order by WoID, min(TiSeID)";
					$res = do_mysqli_query($sql);
					while ($record = mysqli_fetch_assoc($res)) {
						$sent = getSentence($record['SeID'], $record['WoTextLC'], (int) getSettingWithDefault('set-term-sentence-count'));
						$count += runsql('update ' . $tbpref . 'words set WoSentence = ' . convert_string_to_sqlsyntax(repl_tab_nl($sent[1])) . ' where WoID = ' . $record['WoID'], '');
					}
					mysqli_free_result($res);
					$message = 'Term Sentences set from Text(s): ' . $count;
				} 
				
				elseif ($markaction == 'rebuild') {
					$count = 0;
					$sql = "select TxID, TxLgID from " . $tbpref . "texts where TxID in " . $list;
					$res = do_mysqli_query($sql);
					while ($record = mysqli_fetch_assoc($res)) {
						$id = $record['TxID'];
						$message2 = runsql('delete from ' . $tbpref . 'sentences where SeTxID = ' . $id, "Sentences deleted");
						$message3 = runsql('delete from ' . $tbpref . 'textitems where TiTxID = ' . $id, "Text items deleted");
						adjust_autoincr('sentences','SeID');
						adjust_autoincr('textitems','TiID');
						splitCheckText(
							get_first_value(
								'select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), 
								$record['TxLgID'], $id );
						$count++;
					}
					mysqli_free_result($res);
					$message = 'Text(s) reparsed: ' . $count;
				}
				
				elseif ($markaction == 'test' ) {
					$_SESSION['testsql'] = ' ' . $tbpref . 'words, ' . $tbpref . 'textitems where TiLgID = WoLgID and TiTextLC = WoTextLC and TiTxID in ' . $list . ' ';
					header("Location: do_test.php?selection=1");
					exit();
				}
				
			}
		}
	}
}

// DEL

if (isset($_REQUEST['del'])) {
	$message3 = runsql('delete from ' . $tbpref . 'textitems where TiTxID = ' . $_REQUEST['del'], 
		"Text items deleted");
	$message2 = runsql('delete from ' . $tbpref . 'sentences where SeTxID = ' . $_REQUEST['del'], 
		"Sentences deleted");
	$message1 = runsql('delete from ' . $tbpref . 'texts where TxID = ' . $_REQUEST['del'], 
		"Texts deleted");
	$message = $message1 . " / " . $message2 . " / " . $message3;
	adjust_autoincr('texts','TxID');
	adjust_autoincr('sentences','SeID');
	adjust_autoincr('textitems','TiID');
	runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL",'');
}

// ARCH

elseif (isset($_REQUEST['arch'])) {
	$message3 = runsql('delete from ' . $tbpref . 'textitems where TiTxID = ' . $_REQUEST['arch'], 
		"Text items deleted");
	$message2 = runsql('delete from ' . $tbpref . 'sentences where SeTxID = ' . $_REQUEST['arch'], 
		"Sentences deleted");
	$message4 = runsql('insert into ' . $tbpref . 'archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) select TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $_REQUEST['arch'], "Archived Texts saved");
	$id = get_last_key();
	runsql('insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) select ' . $id . ', TtT2ID from ' . $tbpref . 'texttags where TtTxID = ' . $_REQUEST['arch'], "");	
	$message1 = runsql('delete from ' . $tbpref . 'texts where TxID = ' . $_REQUEST['arch'], "Texts deleted");
	$message = $message4 . " / " . $message1 . " / " . $message2 . " / " . $message3;
	adjust_autoincr('texts','TxID');
	adjust_autoincr('sentences','SeID');
	adjust_autoincr('textitems','TiID');
	runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL",'');
}

// INS/UPD

elseif (isset($_REQUEST['op'])) {

	if (strlen(prepare_textdata($_REQUEST['TxText'])) > 65000) {
		$message = "Error: Text too long, must be below 65000 Bytes";
		if ($no_pagestart) pagestart('My ' . getLanguage($currentlang) . ' Texts',true);
	}

	else {
	
		// CHECK
		
		if ($_REQUEST['op'] == 'Check') {
			echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
			echo splitCheckText(remove_soft_hyphens($_REQUEST['TxText']), $_REQUEST['TxLgID'], -1);
			echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
			pageend();
			exit();
		} 
		
		// INSERT
		
		elseif (substr($_REQUEST['op'],0,4) == 'Save') {
			$message1 = runsql('insert into ' . $tbpref . 'texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) values( ' . 
			$_REQUEST["TxLgID"] . ', ' . 
			convert_string_to_sqlsyntax($_REQUEST["TxTitle"]) . ', ' . 
			convert_string_to_sqlsyntax(remove_soft_hyphens($_REQUEST["TxText"])) . ", '', " .
			convert_string_to_sqlsyntax($_REQUEST["TxAudioURI"]) . ', ' .
			convert_string_to_sqlsyntax($_REQUEST["TxSourceURI"]) . ')', "Saved");
			$id = get_last_key();
			saveTextTags($id);
		} 
		
		// UPDATE
		
		elseif (substr($_REQUEST['op'],0,6) == 'Change') {
			$oldtext = get_first_value('select TxText as value from ' . $tbpref . 'texts where TxID = ' . $_REQUEST["TxID"]);
			$textsdiffer = (convert_string_to_sqlsyntax(remove_soft_hyphens($_REQUEST["TxText"])) != convert_string_to_sqlsyntax($oldtext));
			$message1 = runsql('update ' . $tbpref . 'texts set ' .
			'TxLgID = ' . $_REQUEST["TxLgID"] . ', ' .
			'TxTitle = ' . convert_string_to_sqlsyntax($_REQUEST["TxTitle"]) . ', ' .
			'TxText = ' . convert_string_to_sqlsyntax(remove_soft_hyphens($_REQUEST["TxText"])) . ', ' .
			'TxAudioURI = ' . convert_string_to_sqlsyntax($_REQUEST["TxAudioURI"]) . ', ' .
			'TxSourceURI = ' . convert_string_to_sqlsyntax($_REQUEST["TxSourceURI"]) . ' ' .
			'where TxID = ' . $_REQUEST["TxID"], "Updated");
			$id = $_REQUEST["TxID"];
			saveTextTags($id);
		}
		
		$message2 = runsql('delete from ' . $tbpref . 'sentences where SeTxID = ' . $id, 
			"Sentences deleted");
		$message3 = runsql('delete from ' . $tbpref . 'textitems where TiTxID = ' . $id, 
			"Textitems deleted");
		adjust_autoincr('sentences','SeID');
		adjust_autoincr('textitems','TiID');
	
		splitCheckText(
			get_first_value(
				'select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), 
			$_REQUEST["TxLgID"], $id );
			
		$message = $message1 . " / " . $message2 . " / " . $message3 . " / Sentences added: " . get_first_value('select count(*) as value from ' . $tbpref . 'sentences where SeTxID = ' . $id) . " / Text items added: " . get_first_value('select count(*) as value from ' . $tbpref . 'textitems where TiTxID = ' . $id);
		
		if(substr($_REQUEST['op'],-8) == "and Open") {
			header('Location: do_text.php?start=' . $id);
			exit();
		}
	
	}

}

if (isset($_REQUEST['new'])) {

// NEW
	
	?>

	<h4>New Text <a target="_blank" href="info.htm#howtotext"><img src="icn/question-frame.png" title="Help" alt="Help" /></a> </h4>
	<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>	
	<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<table class="tab3" cellspacing="0" cellpadding="5">
	<tr>
	<td class="td1 right">Language:</td>
	<td class="td1">
	<select name="TxLgID" class="notempty setfocus">
	<?php
	echo get_languages_selectoptions($currentlang,'[Choose...]');
	?>
	</select> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
	</td>
	</tr>
	<tr>
	<td class="td1 right">Title:</td>
	<td class="td1"><input type="text" class="notempty checkoutsidebmp" data_info="Title" name="TxTitle" value="" maxlength="200" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Text:<br /><br />(max.<br />65,000<br />bytes)</td>
	<td class="td1">
	<textarea name="TxText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"></textarea> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
	</td>
	</tr>
	<tr>
	<td class="td1 right">Source URI:</td>
	<td class="td1"><input type="text" class="checkurl checkoutsidebmp" data_info="Source URI" name="TxSourceURI" value="" maxlength="1000" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right">Tags:</td>
	<td class="td1">
	<?php echo getTextTags(0); ?>
	</td>
	</tr>
	<tr>
	<td class="td1 right">Audio-URI:</td>
	<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Audio-URI" name="TxAudioURI" value="" maxlength="200" size="60" />		
	<span id="mediaselect"><?php echo selectmediapath('TxAudioURI'); ?></span>		
	</td>
	</tr>
	<tr>
	<td class="td1 right" colspan="2">
	<input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_texts.php';}" /> 
	<input type="submit" name="op" value="Check" />
	<input type="submit" name="op" value="Save" />
	<input type="submit" name="op" value="Save and Open" />
	</td>
	</tr>
	</table>
	</form>
	
	<p class="smallgray">Import of a <b>long text</b>, without audio, with splitting it up into smaller texts:</p><p><input type="button" value="Long Text Import" onclick="location.href='long_text_import.php';" /> </p>

	
	<?php
	
}

// CHG

elseif (isset($_REQUEST['chg'])) {
	
	$sql = 'select TxLgID, TxTitle, TxText, TxAudioURI, TxSourceURI, length(TxAnnotatedText) as annotlen from ' . $tbpref . 'texts where TxID = ' . $_REQUEST['chg'];
	$res = do_mysqli_query($sql);
	if ($record = mysqli_fetch_assoc($res)) {

		?>
	
		<h4>Edit Text <a target="_blank" href="info.htm#howtotext"><img src="icn/question-frame.png" title="Help" alt="Help" /></a></h4>
		<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>	
		<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $_REQUEST['chg']; ?>" method="post">
		<input type="hidden" name="TxID" value="<?php echo $_REQUEST['chg']; ?>" />
		<table class="tab3" cellspacing="0" cellpadding="5">
		<tr>
		<td class="td1 right">Language:</td>
		<td class="td1">
		<select name="TxLgID" class="notempty setfocus">
		<?php
		echo get_languages_selectoptions($record['TxLgID'],"[Choose...]");
		?>
		</select> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
		</td>
		</tr>
		<tr>
		<td class="td1 right">Title:</td>
		<td class="td1"><input type="text" class="notempty checkoutsidebmp" data_info="Title" name="TxTitle" value="<?php echo tohtml($record['TxTitle']); ?>" maxlength="200" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Text:<br /><br />(max.<br />65,000<br />bytes)</td>
		<td class="td1">
		<textarea <?php echo getScriptDirectionTag($record['TxLgID']); ?> name="TxText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"><?php echo tohtml($record['TxText']); ?></textarea> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
		</td>
		</tr>
		<tr>
		<td class="td1 right">Ann.Text:</td>
		<td class="td1">
		<?php echo ($record['annotlen'] ? '<img src="icn/tick.png" title="With Improved Annotation" alt="With Improved Annotation" /> Exists - May be partially or fully lost if you change the text!<br /><input type="button" value="Print/Edit..." onclick="location.href=\'print_impr_text.php?text=' . $_REQUEST['chg'] . '\';" />' : '<img src="icn/cross.png" title="No Improved Annotation" alt="No Improved Annotation" /> - None | <input type="button" value="Create/Print..." onclick="location.href=\'print_impr_text.php?edit=1&amp;text=' . $_REQUEST['chg'] . '\';" />'); ?>
		</td>
		</tr>
		<tr>
		<td class="td1 right">Source URI:</td>
		<td class="td1"><input type="text" class="checkurl checkoutsidebmp" data_info="Source URI" name="TxSourceURI" value="<?php echo tohtml($record['TxSourceURI']); ?>" maxlength="1000" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">Tags:</td>
		<td class="td1">
		<?php echo getTextTags($_REQUEST['chg']); ?>
		</td>
		</tr>
		<tr>
		<td class="td1 right">Audio-URI:</td>
		<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Audio-URI" name="TxAudioURI" value="<?php echo tohtml($record['TxAudioURI']); ?>" maxlength="200" size="60" /> 
		<span id="mediaselect"><?php echo selectmediapath('TxAudioURI'); ?></span>		
		</td>
		</tr>
		<tr>
		<td class="td1 right" colspan="2">
		<input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_texts.php#rec<?php echo $_REQUEST['chg']; ?>';}" /> 
		<input type="submit" name="op" value="Check" />
		<input type="submit" name="op" value="Change" />
		<input type="submit" name="op" value="Change and Open" />
		</td>
		</tr>
		</table>
		</form>
		
		<?php

	}
	mysqli_free_result($res);

}

// DISPLAY

else {

	echo error_message_with_hide($message,0);
	
	$sql = 'select count(*) as value from (select TxID from (' . $tbpref . 'texts left JOIN ' . $tbpref . 'texttags ON TxID = TtTxID) where (1=1) ' . $wh_lang . $wh_query . ' group by TxID ' . $wh_tag . ') as dummy';
	$recno = get_first_value($sql);
	if ($debug) echo $sql . ' ===&gt; ' . $recno;

	$maxperpage = getSettingWithDefault('set-texts-per-page');

	$pages = $recno == 0 ? 0 : (intval(($recno-1) / $maxperpage) + 1);
	
	if ($currentpage < 1) $currentpage = 1;
	if ($currentpage > $pages) $currentpage = $pages;
	$limit = 'LIMIT ' . (($currentpage-1) * $maxperpage) . ',' . $maxperpage;

	$sorts = array('TxTitle','TxID desc','TxID');
	$lsorts = count($sorts);
	if ($currentsort < 1) $currentsort = 1;
	if ($currentsort > $lsorts) $currentsort = $lsorts;
	
?>

<p>
<a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1"><img src="icn/plus-button.png" title="New" alt="New" /> New Text ...</a> &nbsp; | &nbsp;
<a href="long_text_import.php"><img src="icn/plus-button.png" title="Long Text Import" alt="Long Text Import" /> Long Text Import ...</a>
</p>

<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="icn/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="resetAll('edit_texts.php');" /></th>
</tr>
<tr>
<td class="td1 center" colspan="2">
Language:
<select name="filterlang" onchange="{setLang(document.form1.filterlang,'edit_texts.php');}"><?php	echo get_languages_selectoptions($currentlang,'[Filter off]'); ?></select>
</td>
<td class="td1 center" colspan="2">
Text Title (Wildc.=*):
<input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value; location.href='edit_texts.php?page=1&amp;query=' + val;}" />&nbsp;
<input type="button" value="Clear" onclick="{location.href='edit_texts.php?page=1&amp;query=';}" />
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Tag #1:
<select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='edit_texts.php?page=1&amp;tag1=' + val;}"><?php echo get_texttag_selectoptions($currenttag1,$currentlang); ?></select>
</td>
<td class="td1 center" nowrap="nowrap">
Tag #1 .. <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='edit_texts.php?page=1&amp;tag12=' + val;}"><?php echo get_andor_selectoptions($currenttag12); ?></select> .. Tag #2
</td>
<td class="td1 center" nowrap="nowrap">
Tag #2:
<select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='edit_texts.php?page=1&amp;tag2=' + val;}"><?php echo get_texttag_selectoptions($currenttag2,$currentlang); ?></select>
</td>
</tr>
<?php if($recno > 0) { ?>
<tr>
<th class="th1" colspan="1" nowrap="nowrap">
<?php echo $recno; ?> Text<?php echo ($recno==1?'':'s'); ?>
</th><th class="th1" colspan="2" nowrap="nowrap">
<?php makePager ($currentpage, $pages, 'edit_texts.php', 'form1', 1); ?>
</th><th class="th1" colspan="1" nowrap="nowrap">
Sort Order:
<select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='edit_texts.php?page=1&amp;sort=' + val;}"><?php echo get_textssort_selectoptions($currentsort); ?></select>
</th></tr>
<?php } ?>
</table>
</form>

<?php
if ($recno==0) {
?>
<p>No texts found.</p>
<?php
} else {
?>
<form name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="data" value="" />
<table class="tab1" cellspacing="0" cellpadding="5">
<tr><th class="th1" colspan="2">Multi Actions <img src="icn/lightning.png" title="Multi Actions" alt="Multi Actions" /></th></tr>
<tr><td class="td1 center">
<input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
<input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
</td><td class="td1 center">
Marked Texts:&nbsp; 
<select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo get_multipletextactions_selectoptions(); ?></select>
</td></tr></table>

<table class="sortable tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 sorttable_nosort">Read<br />&amp;&nbsp;Test</th>
<th class="th1 sorttable_nosort">Actions</th>
<?php if ($currentlang == '') echo '<th class="th1 clickable">Lang.</th>'; ?>
<th class="th1 clickable">Title [Tags] / Audio:&nbsp;<img src="icn/speaker-volume.png" title="With Audio" alt="With Audio" />, Src.Link:&nbsp;<img src="icn/chain.png" title="Source Link available" alt="Source Link available" />, Ann.Text:&nbsp;<img src="icn/tick.png" title="Annotated Text available" alt="Annotated Text available" /></th>
<th class="th1 sorttable_numeric clickable">Total<br />Words</th>
<th class="th1 sorttable_numeric clickable">Saved<br />Wo+Ex</th>
<th class="th1 sorttable_numeric clickable">Unkn.<br />Words</th>
<th class="th1 sorttable_numeric clickable">Unkn.<br />%</th>
</tr>

<?php

$sql = 'select TxID, TxTitle, LgName, TxAudioURI, TxSourceURI, length(TxAnnotatedText) as annotlen, ifnull(concat(\'[\',group_concat(distinct T2Text order by T2Text separator \', \'),\']\'),\'\') as taglist from ((' . $tbpref . 'texts left JOIN ' . $tbpref . 'texttags ON TxID = TtTxID) left join ' . $tbpref . 'tags2 on T2ID = TtT2ID), ' . $tbpref . 'languages where LgID=TxLgID ' . $wh_lang . $wh_query . ' group by TxID ' . $wh_tag . ' order by ' . $sorts[$currentsort-1] . ' ' . $limit;
if ($debug) echo $sql;
$res = do_mysqli_query($sql);
$showCounts = getSettingWithDefault('set-show-text-word-counts')+0;
while ($record = mysqli_fetch_assoc($res)) {
	if ($showCounts) {
		flush();
		$txttotalwords = textwordcount($record['TxID']);
		$txtworkedwords = textworkcount($record['TxID']);
		$txtworkedexpr = textexprcount($record['TxID']);
		$txtworkedall = $txtworkedwords + $txtworkedexpr;
		$txttodowords = $txttotalwords - $txtworkedwords;
		$percentunknown = 0;
		if ($txttotalwords != 0) {
			$percentunknown = 
				round(100*$txttodowords/$txttotalwords,0);
			if ($percentunknown > 100) $percentunknown = 100;
			if ($percentunknown < 0) $percentunknown = 0;
		}
	}
	$audio = $record['TxAudioURI'];
	if(!isset($audio)) $audio='';
	$audio=trim($audio);
	echo '<tr>';
	echo '<td class="td1 center"><a name="rec' . $record['TxID'] . '"><input name="marked[]" class="markcheck" type="checkbox" value="' . $record['TxID'] . '" ' . checkTest($record['TxID'], 'marked') . ' /></a></td>';
	echo '<td nowrap="nowrap" class="td1 center">&nbsp;<a href="do_text.php?start=' . $record['TxID'] . '"><img src="icn/book-open-bookmark.png" title="Read" alt="Read" /></a>&nbsp; <a href="do_test.php?text=' . $record['TxID'] . '"><img src="icn/question-balloon.png" title="Test" alt="Test" /></a>&nbsp;</td>';
	echo '<td nowrap="nowrap" class="td1 center">&nbsp;<a href="print_text.php?text=' . $record['TxID'] . '"><img src="icn/printer.png" title="Print" alt="Print" /></a>&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?arch=' . $record['TxID'] . '"><img src="icn/inbox-download.png" title="Archive" alt="Archive" /></a>&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['TxID'] . '"><img src="icn/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <span class="click" onclick="if (confirmDelete()) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $record['TxID'] . '\';"><img src="icn/minus-button.png" title="Delete" alt="Delete" /></span>&nbsp;</td>';
	if ($currentlang == '') echo '<td class="td1 center">' . tohtml($record['LgName']) . '</td>';
	echo '<td class="td1 center">' . tohtml($record['TxTitle']) . ' <span class="smallgray2">' . tohtml($record['taglist']) . '</span> &nbsp;' . (($audio != '') ? '<img src="icn/speaker-volume.png" title="With Audio" alt="With Audio" />' : '') . (isset($record['TxSourceURI']) ? ' <a href="' . $record['TxSourceURI'] . '" target="_blank"><img src="icn/chain.png" title="Link to Text Source" alt="Link to Text Source" /></a>' : '') . ($record['annotlen'] ? ' <a href="print_impr_text.php?text=' . $record['TxID'] . '"><img src="icn/tick.png" title="Annotated Text available" alt="Annotated Text available" /></a>' : '') . '</td>';
	if ($showCounts) {
		echo '<td class="td1 center"><span title="Total">&nbsp;' . $txttotalwords . '&nbsp;</span></td>'; 
		echo '<td class="td1 center"><span title="Saved" class="status4">&nbsp;' . ($txtworkedall > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=&amp;text=' . $record['TxID'] . '">' . $txtworkedwords . '+' . $txtworkedexpr . '</a>' : '0' ) . '&nbsp;</span></td>';
		echo '<td class="td1 center"><span title="Unknown" class="status0">&nbsp;' . $txttodowords . '&nbsp;</span></td>';
		echo '<td class="td1 center"><span title="Unknown (%)">' . $percentunknown . '</span></td>';
	} else {
		echo '<td class="td1 center"><span id="total-' . $record['TxID'] . '"></span></td><td class="td1 center"><span data_id="' . $record['TxID'] . '" id="saved-' . $record['TxID'] . '"><span class="click" onclick="do_ajax_word_counts();"><img src="icn/lightning.png" title="View Word Counts" alt="View Word Counts" /></span></span></td><td class="td1 center"><span id="todo-' . $record['TxID'] . '"></span></td><td class="td1 center"><span id="todop-' . $record['TxID'] . '"></span></td>'; 
	}
	echo '</tr>';
}
mysqli_free_result($res);

?>
</table>
</form>

<?php if( $pages > 1) { ?>
<form name="form3" action="#">
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" nowrap="nowrap">
<?php echo $recno; ?> Text<?php echo ($recno==1?'':'s'); ?>
</th><th class="th1" nowrap="nowrap">
<?php makePager ($currentpage, $pages, 'edit_texts.php', 'form3', 2); ?>
</th></tr></table>
</form>
<?php 
} 

}

?>

<p><input type="button" value="Archived Texts" onclick="location.href='edit_archivedtexts.php?query=&amp;page=1';" /></p>

<?php

}

pageend();

?>