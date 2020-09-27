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
Call: set_text_mode.php?text=[textid]&mode=0/1
Change the text display mode
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' ); 

$tid = getreq('text') + 0;
$showAll = getreq('mode') + 0;
saveSetting('showallwords',$showAll);

pagestart("Text Display Mode changed", false);

echo '<p><span id="waiting"><img src="icn/waiting.gif" alt="Please wait" title="Please wait" />&nbsp;&nbsp;Please wait ...</span>';
flush();
?>

<script type="text/javascript">
//<![CDATA[
var method = 1;  // 0 (jquery, deactivated, too slow) or 1 (reload) 
if (method) window.parent.frames['l'].location.reload();
else {
var context = window.parent.frames['l'].document;
<?php
/**************************************************************
(jquery, deact.)

$sql = 'select TiWordCount as Code, TiText, TiOrder, TiIsNotWord, WoID from (' . $tbpref . 'textitems left join ' . $tbpref . 'words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) where TiTxID = ' . $tid . ' order by TiOrder asc, TiWordCount desc';

$res = do_mysqli_query($sql);
$hideuntil = -1;
$hidetag = "removeClass('hide');";

while ($record = mysqli_fetch_assoc($res)) {  // MAIN LOOP
	$actcode = $record['Code'] + 0;
	$t = $record['TiText'];
	$order = $record['TiOrder'] + 0;
	$notword = $record['TiIsNotWord'] + 0;
	$termex = isset($record['WoID']);
	$spanid = 'ID-' . $order . '-' . $actcode;

	if ( $hideuntil > 0 ) {
		if ( $order <= $hideuntil )
			$hidetag = "addClass('hide');";
		else {
			$hideuntil = -1;
			$hidetag = "removeClass('hide');";
		}
	}
	
	if ($notword != 0) {  // NOT A TERM
		echo "$('#" . $spanid . "',context)." . $hidetag . "\n";
	}  
	
	else {   // A TERM
		if ($actcode > 1) {   // A MULTIWORD FOUND
			if ($termex) {  // MULTIWORD FOUND - DISPLAY
				if (! $showAll) {
					if ($hideuntil == -1) {
						$hideuntil = $order + ($actcode - 1) * 2;
					}
				}
				echo "$('#" . $spanid . "',context)." .
					($showAll ? ("html('&nbsp;" . $actcode . "&nbsp;')") : ('text(' . prepare_textdata_js($t) . ')')) .
					".removeClass('mwsty wsty').addClass('" .
					($showAll ? 'mwsty' : 'wsty') . "')." . 
					$hidetag . "\n";
			}
			else {  // MULTIWORD PLACEHOLDER - NO DISPLAY 
				echo "$('#" . $spanid . "',context)." .
					($showAll ? ("html('&nbsp;" . $actcode . "&nbsp;')") : ('text(' . prepare_textdata_js($t) . ')')) .
					".removeClass('mwsty wsty').addClass('" .
					($showAll ? 'mwsty' : 'wsty') . " hide');\n";
			}  
		} // ($actcode > 1) -- A MULTIWORD FOUND
		else {  // ($actcode == 1)  -- A WORD FOUND
			echo "$('#" . $spanid . "',context)." . $hidetag . "\n";
		}  // ($actcode == 1)  -- A WORD FOUND
	} // $record['TiIsNotWord'] == 0  -- A TERM
} // while ($record = mysqli_fetch_assoc($res))  -- MAIN LOOP
mysqli_free_result($res);

(jquery, deact.) 
***************************************************************/
?>
}
$('#waiting').html('<b>OK -- </b>');
//]]>
</script>

<?php

if ($showAll == 1) 
	echo '<b><i>Show All</i></b> is set to <b>ON</b>.<br /><br />ALL terms are now shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term.<br /><br />To concentrate more on the multi-word terms and to display them without superscript, set <i>Show All</i> to OFF.</p>';
else
	echo '<b><i>Show All</i></b> is set to <b>OFF</b>.<br /><br />Multi-word terms now hide single words and shorter or overlapping multi-word terms. The creation and deletion of multi-word terms can be a bit slow in long texts.<br /><br />To  manipulate ALL terms, set <i>Show All</i> to ON.</p>';

pageend();

?>