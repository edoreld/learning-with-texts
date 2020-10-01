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
Call: edit_archivedtexts.php?....
      ... markaction=[opcode] ... do actions on marked texts
      ... del=[textid] ... do delete
      ... unarch=[textid] ... do unarchive
      ... op=Change ... do update
      ... chg=[textid] ... display edit screen 
      ... filterlang=[langid] ... language filter 
      ... sort=[sortcode] ... sort 
      ... page=[pageno] ... page  
      ... query=[titlefilter] ... title filter   
Manage archived texts
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$currentlang = validateLang(processDBParam("filterlang",'currentlanguage','',0));
$currentsort = processDBParam("sort",'currentarchivesort','1',1);

$currentpage = processSessParam("page","currentarchivepage",'1',1);
$currentquery = processSessParam("query","currentarchivequery",'',0);
$currenttag1 = validateArchTextTag(processSessParam("tag1","currentarchivetexttag1",'',0),$currentlang);
$currenttag2 = validateArchTextTag(processSessParam("tag2","currentarchivetexttag2",'',0),$currentlang);
$currenttag12 = processSessParam("tag12","currentarchivetexttag12",'',0);

$wh_lang = ($currentlang != '') ? (' and AtLgID=' . $currentlang) : '';
$wh_query = convert_string_to_sqlsyntax(str_replace("*","%",mb_strtolower($currentquery, 'UTF-8')));
$wh_query = ($currentquery != '') ? (' and AtTitle like ' . $wh_query) : '';

if ($currenttag1 == '' && $currenttag2 == '')
	$wh_tag = '';
else {
	if ($currenttag1 != '') {
		if ($currenttag1 == -1)
			$wh_tag1 = "group_concat(AgT2ID) IS NULL";
		else
			$wh_tag1 = "concat('/',group_concat(AgT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'";
	} 
	if ($currenttag2 != '') {
		if ($currenttag2 == -1)
			$wh_tag2 = "group_concat(AgT2ID) IS NULL";
		else
			$wh_tag2 = "concat('/',group_concat(AgT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'";
	} 
	if ($currenttag1 != '' && $currenttag2 == '')	
		$wh_tag = " having (" . $wh_tag1 . ') ';
	elseif ($currenttag2 != '' && $currenttag1 == '')	
		$wh_tag = " having (" . $wh_tag2 . ') ';
	else
		$wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) ';
}

$no_pagestart = 
	(getreq('markaction') == 'deltag');
if (! $no_pagestart) {
	pagestart('My ' . getLanguage($currentlang) . ' Text Archive',true);
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
					$message = runsql('delete from ' . $tbpref . 'archivedtexts where AtID in ' . $list, "Archived Texts deleted");
					adjust_autoincr('archivedtexts','AtID');
					runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID) WHERE AtID IS NULL",'');
				} 

				elseif ($markaction == 'addtag' ) {
					$message = addarchtexttaglist($actiondata,$list);
				}
				
				elseif ($markaction == 'deltag' ) {
					$message = removearchtexttaglist($actiondata,$list);
					header("Location: edit_archivedtexts.php");
					exit();
				}
				
				elseif ($markaction == 'unarch') {
					$count = 0;
					$sql = "select AtID, AtLgID from " . $tbpref . "archivedtexts where AtID in " . $list;
					$res = do_mysqli_query($sql);
					while ($record = mysqli_fetch_assoc($res)) {
						$ida = $record['AtID'];
						$mess = 0 + runsql('insert into ' . $tbpref . 'texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) select AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI from ' . $tbpref . 'archivedtexts where AtID = ' . $ida, "");
						$count += $mess;
						$id = get_last_key();
						runsql('insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID) select ' . $id . ', AgT2ID from ' . $tbpref . 'archtexttags where AgAtID = ' . $ida, "");	
						splitCheckText(
							get_first_value(
							'select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), 
							$record['AtLgID'], 
							$id );	
						runsql('delete from ' . $tbpref . 'archivedtexts where AtID = ' . $ida, "");
					}
					mysqli_free_result($res);
					adjust_autoincr('archivedtexts','AtID');
					runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID) WHERE AtID IS NULL",'');
					$message = 'Unarchived Text(s): ' . $count;
				} 
												
			}
		}
	}
}

// DEL

if (isset($_REQUEST['del'])) {
	$message = runsql('delete from ' . $tbpref . 'archivedtexts where AtID = ' . $_REQUEST['del'], 
		"Archived Texts deleted");
	adjust_autoincr('archivedtexts','AtID');
	runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID) WHERE AtID IS NULL",'');
}

// UNARCH

elseif (isset($_REQUEST['unarch'])) {
	$message2 = runsql('insert into ' . $tbpref . 'texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) select AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI from ' . $tbpref . 'archivedtexts where AtID = ' . $_REQUEST['unarch'], "Texts added");
	$id = get_last_key();
	runsql('insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID) select ' . $id . ', AgT2ID from ' . $tbpref . 'archtexttags where AgAtID = ' . $_REQUEST['unarch'], "");	
	splitCheckText(
		get_first_value(
		'select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), 
		get_first_value(
		'select TxLgID as value from ' . $tbpref . 'texts where TxID = ' . $id), 
		$id );	
	$message1 = runsql('delete from ' . $tbpref . 'archivedtexts where AtID = ' . $_REQUEST['unarch'], "Archived Texts deleted");
	$message = $message1 . " / " . $message2 . " / Sentences added: " . get_first_value('select count(*) as value from ' . $tbpref . 'sentences where SeTxID = ' . $id) . " / Text items added: " . get_first_value('select count(*) as value from ' . $tbpref . 'textitems where TiTxID = ' . $id);
	adjust_autoincr('archivedtexts','AtID');
	runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID) WHERE AtID IS NULL",'');
}

// UPD

elseif (isset($_REQUEST['op'])) {
	
	// UPDATE
	
	if ($_REQUEST['op'] == 'Change') {
		$oldtext = get_first_value('select AtText as value from ' . $tbpref . 'archivedtexts where AtID = ' . $_REQUEST["AtID"]);
		$textsdiffer = (convert_string_to_sqlsyntax($_REQUEST["AtText"]) != convert_string_to_sqlsyntax($oldtext));
		$message = runsql('update ' . $tbpref . 'archivedtexts set ' .
		'AtLgID = ' . $_REQUEST["AtLgID"] . ', ' .
		'AtTitle = ' . convert_string_to_sqlsyntax($_REQUEST["AtTitle"]) . ', ' .
		'AtText = ' . convert_string_to_sqlsyntax($_REQUEST["AtText"]) . ', ' .
		'AtAudioURI = ' . convert_string_to_sqlsyntax($_REQUEST["AtAudioURI"]) . ', ' .
		'AtSourceURI = ' . convert_string_to_sqlsyntax($_REQUEST["AtSourceURI"]) . ' ' .
		'where AtID = ' . $_REQUEST["AtID"], "Updated");
		if (($message == 'Updated: 1') && $textsdiffer) {
			$dummy = runsql("update " . $tbpref . "archivedtexts set AtAnnotatedText = '' where AtID = " . $_REQUEST["AtID"], "");
		}
		$id = $_REQUEST["AtID"];
	}
	saveArchivedTextTags($id);
	
}

// CHG

if (isset($_REQUEST['chg'])) {
	
	$sql = 'select AtLgID, AtTitle, AtText, AtAudioURI, AtSourceURI, length(AtAnnotatedText) as annotlen from ' . $tbpref . 'archivedtexts where AtID = ' . $_REQUEST['chg'];
	$res = do_mysqli_query($sql);
	if ($record = mysqli_fetch_assoc($res)) {

		?>
	
		<h4>Edit Archived Text</h4>
		<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>	
		<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $_REQUEST['chg']; ?>" method="post">
		<input type="hidden" name="AtID" value="<?php echo $_REQUEST['chg']; ?>" />
		<table class="tab3" cellspacing="0" cellpadding="5">
		<tr>
		<td class="td1 right">Language:</td>
		<td class="td1">
		<select name="AtLgID" class="notempty setfocus">
		<?php
		echo get_languages_selectoptions($record['AtLgID'],"[Choose...]");
		?>
		</select> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
		</td>
		</tr>
		<tr>
		<td class="td1 right">Title:</td>
		<td class="td1"><input type="text" class="notempty checkoutsidebmp" data_info="Title" name="AtTitle" value="<?php echo tohtml($record['AtTitle']); ?>" maxlength="200" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Text:</td>
		<td class="td1">
		<textarea name="AtText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"><?php echo tohtml($record['AtText']); ?></textarea> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
		</td>
		</tr>
		<tr>
		<td class="td1 right">Ann.Text:</td>
		<td class="td1">
		<?php echo ($record['annotlen'] ? '<img src="icn/tick.png" title="With Annotation" alt="With Annotation" /> Exists - May be partially or fully lost if you change the text!' : '<img src="icn/cross.png" title="No Annotation" alt="No Annotation" /> - None'); ?>
		</td>
		</tr>
		<tr>
		<td class="td1 right">Source URI:</td>
		<td class="td1"><input type="text" class="checkurl checkoutsidebmp" data_info="Source URI" name="AtSourceURI" value="<?php echo tohtml($record['AtSourceURI']); ?>" maxlength="1000" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">Tags:</td>
		<td class="td1">
		<?php echo getArchivedTextTags($_REQUEST['chg']); ?>
		</td>
		</tr>
		<tr>
		<td class="td1 right">Audio-URI:</td>
		<td class="td1"><input type="text" class="checkoutsidebmp" data_info="Audio-URI" name="AtAudioURI" value="<?php echo tohtml($record['AtAudioURI']); ?>" maxlength="200" size="60" />
		<span id="mediaselect"><?php echo selectmediapath('AtAudioURI'); ?></span>		
		</td>
		</tr>
		<tr>
		<td class="td1 right" colspan="2">
		<input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_archivedtexts.php#rec<?php echo $_REQUEST['chg']; ?>';}" /> 
		<input type="submit" name="op" value="Change" /></td>
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

	$sql = 	'select count(*) as value from (select AtID from (' . $tbpref . 'archivedtexts left JOIN ' . $tbpref . 'archtexttags ON AtID = AgAtID) where (1=1) ' . $wh_lang . $wh_query . ' group by AtID ' . $wh_tag . ') as dummy';
	$recno = get_first_value($sql);
	if ($debug) echo $sql . ' ===&gt; ' . $recno;

	$maxperpage = getSettingWithDefault('set-archivedtexts-per-page');

	$pages = $recno == 0 ? 0 : (intval(($recno-1) / $maxperpage) + 1);
	
	if ($currentpage < 1) $currentpage = 1;
	if ($currentpage > $pages) $currentpage = $pages;
	$limit = 'LIMIT ' . (($currentpage-1) * $maxperpage) . ',' . $maxperpage;

	$sorts = array('AtTitle','AtID desc','AtID');
	$lsorts = count($sorts);
	if ($currentsort < 1) $currentsort = 1;
	if ($currentsort > $lsorts) $currentsort = $lsorts;
	
?>

<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1" colspan="4">Filter <img src="icn/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="resetAll('edit_archivedtexts.php');" /></th>
</tr>
<tr>
<td class="td1 center" colspan="2">
Language:
<select name="filterlang" onchange="{setLang(document.form1.filterlang,'edit_archivedtexts.php');}"><?php	echo get_languages_selectoptions($currentlang,'[Filter off]'); ?></select>
</td>
<td class="td1 center" colspan="2">
Text Title (Wildc.=*):
<input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value; location.href='edit_archivedtexts.php?page=1&amp;query=' + val;}" />&nbsp;
<input type="button" value="Clear" onclick="{location.href='edit_archivedtexts.php?page=1&amp;query=';}" />
</td>
</tr>
<tr>
<td class="td1 center" colspan="2" nowrap="nowrap">
Tag #1:
<select name="tag1" onchange="{val=document.form1.tag1.options[document.form1.tag1.selectedIndex].value; location.href='edit_archivedtexts.php?page=1&amp;tag1=' + val;}"><?php echo get_archivedtexttag_selectoptions($currenttag1,$currentlang); ?></select>
</td>
<td class="td1 center" nowrap="nowrap">
Tag #1 .. <select name="tag12" onchange="{val=document.form1.tag12.options[document.form1.tag12.selectedIndex].value; location.href='edit_archivedtexts.php?page=1&amp;tag12=' + val;}"><?php echo get_andor_selectoptions($currenttag12); ?></select> .. Tag #2
</td>
<td class="td1 center" nowrap="nowrap">
Tag #2:
<select name="tag2" onchange="{val=document.form1.tag2.options[document.form1.tag2.selectedIndex].value; location.href='edit_archivedtexts.php?page=1&amp;tag2=' + val;}"><?php echo get_archivedtexttag_selectoptions($currenttag2,$currentlang); ?></select>
</td>
</tr>
<?php if($recno > 0) { ?>
<tr>
<th class="th1" nowrap="nowrap">
<?php echo $recno; ?> Text<?php echo ($recno==1?'':'s'); ?>
</th>
<th class="th1" colspan="2" nowrap="nowrap">
<?php makePager ($currentpage, $pages, 'edit_archivedtexts.php', 'form1', 1); ?>
</th>
<th class="th1" nowrap="nowrap">
Sort Order:
<select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='edit_archivedtexts.php?page=1&amp;sort=' + val;}"><?php echo get_textssort_selectoptions($currentsort); ?></select>
</th></tr>
<?php } ?>
</table>
</form>

<?php
if ($recno==0) {
?>
<p>No archived texts found.</p>
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
<select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo get_multiplearchivedtextactions_selectoptions(); ?></select>
</td></tr></table>

<table class="sortable tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Mark</th>
<th class="th1 sorttable_nosort">Actions</th>
<?php if ($currentlang == '') echo '<th class="th1 clickable">Lang.</th>'; ?>
<th class="th1 clickable">Title [Tags] / Audio:&nbsp;<img src="icn/speaker-volume.png" title="With Audio" alt="With Audio" />, Src.Link:&nbsp;<img src="icn/chain.png" title="Source Link available" alt="Source Link available" />, Ann.Text:&nbsp;<img src="icn/tick.png" title="Annotated Text available" alt="Annotated Text available" /></th>
</tr>

<?php

$sql = 'select AtID, AtTitle, LgName, AtAudioURI, AtSourceURI, length(AtAnnotatedText) as annotlen, ifnull(concat(\'[\',group_concat(distinct T2Text order by T2Text separator \', \'),\']\'),\'\') as taglist from ((' . $tbpref . 'archivedtexts left JOIN ' . $tbpref . 'archtexttags ON AtID = AgAtID) left join ' . $tbpref . 'tags2 on T2ID = AgT2ID), ' . $tbpref . 'languages where LgID=AtLgID ' . $wh_lang . $wh_query . ' group by AtID ' . $wh_tag . ' order by ' . $sorts[$currentsort-1] . ' ' . $limit;

if ($debug) echo $sql;

$res = do_mysqli_query($sql);
while ($record = mysqli_fetch_assoc($res)) {
	echo '<tr>';
	echo '<td class="td1 center"><a name="rec' . $record['AtID'] . '"><input name="marked[]" class="markcheck"  type="checkbox" value="' . $record['AtID'] . '" ' . checkTest($record['AtID'], 'marked') . ' /></a></td>';
	echo '<td nowrap="nowrap" class="td1 center">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?unarch=' . $record['AtID'] . '"><img src="icn/inbox-upload.png" title="Unarchive" alt="Unarchive" /></a>&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['AtID'] . '"><img src="icn/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <span class="click" onclick="if (confirmDelete()) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $record['AtID'] . '\';"><img src="icn/minus-button.png" title="Delete" alt="Delete" /></span>&nbsp;</td>';
	if ($currentlang == '') echo '<td class="td1 center">' . tohtml($record['LgName']) . '</td>';
	echo '<td class="td1 center">' . tohtml($record['AtTitle']) . ' <span class="smallgray2">' . tohtml($record['taglist']) . '</span> &nbsp;' . (isset($record['AtAudioURI']) ? '<img src="icn/speaker-volume.png" title="With Audio" alt="With Audio" />' : '') . (isset($record['AtSourceURI']) ? ' <a href="' . $record['AtSourceURI'] . '" target="_blank"><img src="icn/chain.png" title="Link to Text Source" alt="Link to Text Source" /></a>' : '') . ($record['annotlen'] ? ' <img src="icn/tick.png" title="Annotated Text available" alt="Annotated Text available" />' : '') . '</td>';
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
<?php makePager ($currentpage, $pages, 'edit_archivedtexts.php', 'form3', 2); ?>
</th></tr></table>
</form>
<?php } ?>

<?php

}

?>

<p><input type="button" value="Active Texts" onclick="location.href='edit_texts.php?query=&amp;page=1';" /></p>

<?php

}

pageend();

?>