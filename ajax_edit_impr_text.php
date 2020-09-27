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
Call: ajax_edit_impr_text.php?id=[textid]
Display table for Improved Annotation (Edit Mode), 
Ajax call in print_impr_text.php
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

function make_trans($i, $wid, $trans, $word, $lang) {
	global $tbpref;	
	$trans = trim($trans);
	$widset = is_numeric($wid);
	if ($widset) {
		$alltrans = get_first_value("select WoTranslation as value from " . $tbpref . "words where WoID = " . $wid);
		$transarr = preg_split('/[' . get_sepas()  . ']/u', $alltrans);
		$r = "";
		$set = false;
		foreach ($transarr as $t) {
			$tt = trim($t);
			if (($tt == '*') || ($tt == '')) continue;
			if ((! $set) && ($tt == $trans)) {
				$set = true;
				$r .= '<span class="nowrap"><input class="impr-ann-radio" checked="checked" type="radio" name="rg' . $i . '" value="' . tohtml($tt) . '" />&nbsp;' . tohtml($tt) . '</span> <br /> ';
			} else {
				$r .= '<span class="nowrap"><input class="impr-ann-radio" type="radio" name="rg' . $i . '" value="' . tohtml($tt) . '" />&nbsp;' . tohtml($tt) . '</span>  <br />  ';
			}
		}
		if (! $set) {
			$r .= '<span class="nowrap"><input class="impr-ann-radio" checked="checked" type="radio" name="rg' . $i . '" value="" />&nbsp;<input class="impr-ann-text" type="text" name="tx' . $i . '" id="tx' . $i . '" value="' . tohtml($trans) . '" maxlength="50" size="40" />';
		} else {
			$r .= '<span class="nowrap"><input class="impr-ann-radio" type="radio" name="rg' . $i . '" value="" />&nbsp;<input class="impr-ann-text" type="text" name="tx' . $i . '" id="tx' . $i . '" value="" maxlength="50" size="40" />';
		}
	} else {
		$r = '<span class="nowrap"><input checked="checked" type="radio" name="rg' . $i . '" value="" />&nbsp;<input class="impr-ann-text" type="text" name="tx' . $i . '" id="tx' . $i . '" value="' . tohtml($trans) . '" maxlength="50" size="40" />';
	}
	$r .= ' &nbsp;<img class="click" src="icn/eraser.png" title="Erase Text Field" alt="Erase Text Field" onclick="$(\'#tx' . $i . '\').val(\'\').trigger(\'change\');" />';
	$r .= ' &nbsp;<img class="click" src="icn/star.png" title="* (Set to Term)" alt="* (Set to Term)" onclick="$(\'#tx' . $i . '\').val(\'*\').trigger(\'change\');" />';
	if ($widset)
		$r .= ' &nbsp;<img class="click" src="icn/plus-button.png" title="Save another translation to existent term" alt="Save another translation to existent term" onclick="addTermTranslation(' . $wid . ', \'#tx' . $i . '\',\'\',' . $lang . ');" />';
	else 
		$r .= ' &nbsp;<img class="click" src="icn/plus-button.png" title="Save translation to new term" alt="Save translation to new term" onclick="addTermTranslation(0, \'#tx' . $i . '\',' . prepare_textdata_js($word) . ',' . $lang . ');" />';
	$r .= '&nbsp;&nbsp;<span id="wait' . $i . '"><img src="icn/empty.gif" /></span></span>';
	return $r;
}

$textid = $_POST["id"] + 0;
$wordlc = stripTheSlashesIfNeeded($_POST['word']);

$sql = 'select TxLgID, TxTitle from ' . $tbpref . 'texts where TxID = ' . $textid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$title = $record['TxTitle'];
$langid = $record['TxLgID'];
mysqli_free_result($res);

$sql = 'select LgTextSize, LgRightToLeft from ' . $tbpref . 'languages where LgID = ' . $langid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$textsize = $record['LgTextSize'] + 0;
if ($textsize > 100) $textsize = intval($textsize * 0.8);
$rtlScript = $record['LgRightToLeft'];
mysqli_free_result($res);

$ann = get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
$ann_exists = (strlen($ann) > 0);
if ($ann_exists) {
	$ann = recreate_save_ann($textid, $ann);
	$ann_exists = (strlen($ann) > 0);
}

$rr = "";
$r = "";
$r .= '<form action="" method="post"><table class="tab1" cellspacing="0" cellpadding="5"><tr>';
$r .= '<th class="th1 center">Text</th>';
$r .= '<th class="th1 center">Dict.</th>';
$r .= '<th class="th1 center">Edit<br />Term</th>';
$r .= '<th class="th1 center">Term Translations (Delim.: ' . tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')<br /><input type="button" value="Reload" onclick="do_ajax_edit_impr_text(0,\'\');" /></th>';
$r .= '</tr>';
$nonterms = "";
$items = preg_split('/[\n]/u', $ann);
$i = 0;
$nontermbuffer ='';
foreach ($items as $item) {
	$i++;
	$vals = preg_split('/[\t]/u', $item);
	if ($vals[0] > -1) {
		if ($nontermbuffer != '') {
			$r .= '<tr><td class="td1 center" style="font-size:' . $textsize . '%;">';
			$r .= $nontermbuffer; 
			$r .= '</td><td class="td1 right" colspan="3"><img class="click" src="icn/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" /></td></tr>';
			$nontermbuffer ='';
		}
		$id = '';
		$trans = '';
		if (count($vals) > 2) {
			$id = $vals[2];
			if (is_numeric($id)) {
				if(get_first_value("select count(WoID) as value from " . $tbpref . "words where WoID = "
				 . $id) < 1) $id = '';
			}
		}
		if (count($vals) > 3) $trans = $vals[3];
		$r .= '<tr><td class="td1 center" style="font-size:' . $textsize . '%;"' . 
			($rtlScript ? ' dir="rtl"' : '') . '><span id="term' . $i . '">';
		$r .= tohtml($vals[1]);
		$mustredo = (trim($wordlc) == mb_strtolower(trim($vals[1]), 'UTF-8'));
		$r .= '</span></td><td class="td1 center" nowrap="nowrap">';
		$r .= makeDictLinks($langid,prepare_textdata_js($vals[1]));
		$r .= '</td><td class="td1 center"><span id="editlink' . $i . '">';
		if ($id == '') {
			$plus = '&nbsp;';
		} else {
			$plus = '<a name="rec' . $i . '"></a><span class="click" onclick="oewin(\'edit_word.php?fromAnn=\' + $(document).scrollTop() + \'&amp;wid=' . $id . '\');"><img src="icn/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" /></span>';
		}
		if ($mustredo) $rr .= "$('#editlink" . $i . "').html(" . prepare_textdata_js($plus) . ");";
		$r .= $plus;
		$r .= '</span></td><td class="td1" style="font-size:90%;"><span id="transsel' . $i . '">';
		$plus = make_trans($i, $id, $trans, $vals[1], $langid);
		if ($mustredo) $rr .= "$('#transsel" . $i . "').html(" . prepare_textdata_js($plus) . ");";
		$r .= $plus;
		$r .= '</span></td></tr>';
	} else {
		if (trim($vals[1]) != '') {
			$nontermbuffer .= str_replace("¶", '<img src="icn/new_line.png" title="New Line" alt="New Line" />', tohtml($vals[1])); 
		}
	}
}
if ($nontermbuffer != '') {
	$r .= '<tr><td class="td1 center" style="font-size:' . $textsize . '%;">';
	$r .= $nontermbuffer; 
	$r .= '</td><td class="td1 right" colspan="3"><img class="click" src="icn/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" /></td></tr>';
}
$r .= '<th class="th1 center">Text</th>';
$r .= '<th class="th1 center">Dict.</th>';
$r .= '<th class="th1 center">Edit<br />Term</th>';
$r .= '<th class="th1 center">Term Translations (Delim.: ' . tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')<br /><input type="button" value="Reload" onclick="do_ajax_edit_impr_text(1e6,\'\');" /><a name="bottom"></a></th>';
$r .= '</tr></table></form>' . "\n";
/*
$r .= '<script type="text/javascript">' . "\n";
$r .= '//<![CDATA[' . "\n";
$r .= '$(document).ready( function() {' . "\n";
$r .= "$('input.impr-ann-text').change(changeImprAnnText);\n";
$r .= "$('input.impr-ann-radio').change(changeImprAnnRadio);\n";
$r .= '} );' . "\n";
$r .= '//]]>' . "\n";
$r .= '</script>' . "\n";
*/

if ($wordlc == '')
	echo "$('#editimprtextdata').html(" . prepare_textdata_js($r) . ");";
else
	echo $rr;

?>
