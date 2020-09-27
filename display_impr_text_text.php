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
Call: display_impr_text_text.php?text=[textid]
Display an improved annotated text (text frame)
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$textid = getreq('text')+0;
$ann = get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
$ann_exists = (strlen($ann) > 0);

if(($textid==0) || (! $ann_exists)) {
	header("Location: edit_texts.php");
	exit();
}

$sql = 'select TxLgID, TxTitle from ' . $tbpref . 'texts where TxID = ' . $textid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$title = $record['TxTitle'];
$langid = $record['TxLgID'];
mysqli_free_result($res);

$sql = 'select LgTextSize, LgRemoveSpaces, LgRightToLeft from ' . $tbpref . 'languages where LgID = ' . $langid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$textsize = $record['LgTextSize'];
$removeSpaces = $record['LgRemoveSpaces'];
$rtlScript = $record['LgRightToLeft'];
mysqli_free_result($res);

saveSetting('currenttext',$textid);

pagestart_nobody('Display');

?>
<script type="text/javascript">
//<![CDATA[

function click_ann() {
	if($(this).css('color') == 'rgb(200, 220, 240)') {
		$(this).css('color','#006699');
		$(this).css('background-color','white');
	}
	else {
		$(this).css('color','#C8DCF0');
		$(this).css('background-color','#C8DCF0');
	}
}

function click_text() {
	if($(this).css('color') == 'rgb(229, 228, 226)') {
		$(this).css('color','black');
		$(this).css('background-color','white');
	}
	else {
		$(this).css('color','#E5E4E2');
		$(this).css('background-color','#E5E4E2');
	}
}

$(document).ready(function(){
  $('.anntransruby2').click(click_ann);
  $('.anntermruby').click(click_text);
});
//]]>
</script>

<?php

echo "<div id=\"print\"" . ($rtlScript ? ' dir="rtl"' : '') . ">";

echo '<p style="font-size:' . $textsize . '%;line-height: 1.35; margin-bottom: 10px; ">';

$items = preg_split('/[\n]/u', $ann);

foreach ($items as $item) {
	$vals = preg_split('/[\t]/u', $item);
	if ($vals[0] > -1) {
		$trans = '';
		$c = count($vals);
		$rom = '';
		if ($c > 2) {
			if ($vals[2] !== '') {
				$wid = $vals[2] + 0;
				$rom = get_first_value("select WoRomanization as value from " . $tbpref . "words where WoID = " . $wid);
				if (! isset($rom)) $rom = '';
			}
		}
		if ($c > 3) $trans = $vals[3];
		if ($trans == '*') $trans = $vals[1] . " "; // <- U+200A HAIR SPACE
		echo ' <ruby><rb><span class="click anntermruby" style="color:black;"' . ($rom == '' ? '' : (' title="' . tohtml($rom) . '"')) . '>' . tohtml($vals[1]) . '</span></rb><rt><span class="click anntransruby2">' . tohtml($trans) . '</span></rt></ruby> ';
	} else {
		if (count($vals) >= 2) 
			echo str_replace(
			"¶",
			'</p><p style="font-size:' . $textsize . '%;line-height: 1.3; margin-bottom: 10px;">',
			" " . tohtml($vals[1]));
	}
}

echo "</p></div>";

pageend();

?>
