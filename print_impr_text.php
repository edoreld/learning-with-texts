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
Call: print_impr_text.php?text=[textid]&...
			... edit=1 ... edit own annotation 
			... del=1  ... delete own annotation 
Print/Edit an improved annotated text
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$textid = getreq('text')+0;
$editmode = getreq('edit');
$editmode = ($editmode == '' ? 0 : ($editmode+0));
$delmode = getreq('del');
$delmode = ($delmode == '' ? 0 : ($delmode+0));
$ann = get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
$ann_exists = (strlen($ann) > 0);
if ($ann_exists) {
	$ann = recreate_save_ann($textid, $ann);
	$ann_exists = (strlen($ann) > 0);
}

if($textid==0) {
	header("Location: edit_texts.php");
	exit();
}

if ( $delmode ) {  // Delete
	if ( $ann_exists ) $dummy = runsql('update ' . $tbpref . 'texts set ' .
			'TxAnnotatedText = ' . convert_string_to_sqlsyntax("") . ' where TxID = ' . $textid, "");
	$ann_exists = ((get_first_value("select length(TxAnnotatedText) as value from " . $tbpref . "texts where TxID = " . $textid) + 0) > 0);
	if ( ! $ann_exists ) {
		header("Location: print_text.php?text=" . $textid);
		exit();
	}
}

$sql = 'select TxLgID, TxTitle, TxAudioURI, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $textid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$title = $record['TxTitle'];
$sourceURI = $record['TxSourceURI'];
$langid = $record['TxLgID'];
$audio = $record['TxAudioURI'];
if(! isset($audio)) $audio='';
$audio = trim($audio);
mysqli_free_result($res);

$sql = 'select LgTextSize, LgRemoveSpaces, LgRightToLeft from ' . $tbpref . 'languages where LgID = ' . $langid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$textsize = $record['LgTextSize'];
$removeSpaces = $record['LgRemoveSpaces'];
$rtlScript = $record['LgRightToLeft'];
mysqli_free_result($res);

saveSetting('currenttext',$textid);

pagestart_nobody('Annotated Text');

echo '<div class="noprint">';

echo '<h4>';
echo '<a href="edit_texts.php" target="_top">';
echo_lwt_logo();
echo 'LWT';
echo '</a>&nbsp; | &nbsp;';
quickMenu();
echo getPreviousAndNextTextLinks($textid, 'print_impr_text.php?text=', TRUE, '&nbsp; | &nbsp;');
echo '&nbsp; | &nbsp;<a href="do_text.php?start=' . $textid . '" target="_top"><img src="icn/book-open-bookmark.png" title="Read" alt="Read" /></a> &nbsp;<a href="do_test.php?text=' . $textid . '" target="_top"><img src="icn/question-balloon.png" title="Test" alt="Test" /></a> &nbsp;<a href="print_text.php?text=' . $textid . '" target="_top"><img src="icn/printer.png" title="Print" alt="Print" /> &nbsp;<a target="_top" href="edit_texts.php?chg=' . $textid . '"><img src="icn/document--pencil.png" title="Edit Text" alt="Edit Text" /></a>';
echo '</h4><h3>ANN.TEXT&nbsp;▶ ' . tohtml($title) . (isset($sourceURI) ? ' <a href="' . $sourceURI . '" target="_blank"><img src="icn/chain.png" title="Text Source" alt="Text Source" /></a>' : '') . '</h3>';

echo "<p id=\"printoptions\"><b>Improved Annotated Text";

if($editmode) {
	echo " (Edit Mode)</b> <img src=\"icn/question-frame.png\" title=\"Help\" alt=\"Help\" class=\"click\" onclick=\"window.open('info.htm#il');\" /><br /><input type=\"button\" value=\"Display/Print Mode\" onclick=\"location.href='print_impr_text.php?text=" . $textid . "';\" />\n";
} else {
	echo " (Display/Print Mode)</b><br /><input type=\"button\" value=\"Edit\" onclick=\"location.href='print_impr_text.php?edit=1&amp;text=" . $textid . "';\" />";
	echo " &nbsp; | &nbsp; ";
	echo "<input type=\"button\" value=\"Delete\" onclick=\"if (confirm ('Are you sure?')) location.href='print_impr_text.php?del=1&amp;text=" . $textid . "';\" /> ";
	echo " &nbsp; | &nbsp; ";
	echo "<input type=\"button\" value=\"Print\" onclick=\"window.print();\" />";
	echo " &nbsp; | &nbsp; ";
	echo "<input type=\"button\" value=\"Display" . (($audio != '') ? ' with Audio Player' : '') . " in new Window\" onclick=\"window.open('display_impr_text.php?text=" . $textid . "');\" />";
}
echo "</p></div> <!-- noprint -->";

// --------------------------------------------------------

if ( $editmode ) {  // Edit Mode

	if ( ! $ann_exists ) {  // No Ann., Create...
		$ann = create_save_ann($textid);
		$ann_exists = (strlen($ann) > 0);
	}
	
	if ( ! $ann_exists ) {  // No Ann., not possible
		echo '<p>No annotated text found, and creation seems not possible.</p>';
	} else { // Ann. exists, set up for editing.
		echo "\n";
?>
<?php
		echo '<div data_id="' . $textid . '" id="editimprtextdata"></div>';
		echo "\n";
?>
	<script type="text/javascript">
	//<![CDATA[
	$(document).ready( function() {
	do_ajax_edit_impr_text(0,'');
	} ); 
	//]]>
	</script>
<?php
	}
	echo '<div class="noprint"><input type="button" value="Display/Print Mode" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" /></div>';

}

else {  // Print Mode

	echo "<div id=\"print\"" . ($rtlScript ? ' dir="rtl"' : '') . ">";
	
	echo '<p style="font-size:' . $textsize . '%;line-height: 1.35; margin-bottom: 10px; ">' . tohtml($title) . '<br /><br />';
	
	$items = preg_split('/[\n]/u', $ann);
	
	foreach ($items as $item) {
		$vals = preg_split('/[\t]/u', $item);
		if ($vals[0] > -1) {
			$trans = '';
			if (count($vals) > 3) $trans = $vals[3];
			if ($trans == '*') $trans = $vals[1] . " "; // <- U+200A HAIR SPACE
			echo ' <ruby><rb><span class="anntermruby">' . tohtml($vals[1]) . '</span></rb><rt><span class="anntransruby2">' . tohtml($trans) . '</span></rt></ruby> ';
		} else {
			if (count($vals) >= 2) 
				echo str_replace(
				"¶",
				'</p><p style="font-size:' . $textsize . '%;line-height: 1.3; margin-bottom: 10px;">',
				" " . tohtml($vals[1]) . " ");
		}
	}
	
	echo "</p></div>";

}

pageend();

?>
