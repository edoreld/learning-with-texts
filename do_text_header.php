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
Call: do_text_header.php?text=[textid]
Show text header frame
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$textid = getreq('text');
$sql = 'select TxLgID, TxTitle, TxAudioURI, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $textid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);

$audio = $record['TxAudioURI'];
if(!isset($audio)) $audio='';
$audio=trim($audio);

$title = $record['TxTitle'];
$sourceURI = $record['TxSourceURI'];
$langid = $record['TxLgID'];
mysqli_free_result($res); 

saveSetting('currenttext',$textid);

pagestart_nobody(tohtml($title),$addcss='html, body {margin-bottom:0;}');
echo '<h4>';
echo '<a href="edit_texts.php" target="_top">';
echo_lwt_logo();
echo 'LWT';
echo '</a>&nbsp; | &nbsp;';
quickMenu();
echo getPreviousAndNextTextLinks($textid, 'do_text.php?start=', FALSE, '&nbsp; | &nbsp;');
echo '&nbsp; | &nbsp;<a href="do_test.php?text=' . $textid . '" target="_top"><img src="icn/question-balloon.png" title="Test" alt="Test" /></a> &nbsp;<a href="print_text.php?text=' . $textid . '" target="_top"><img src="icn/printer.png" title="Print" alt="Print" />' . get_annotation_link($textid) . ' &nbsp;<a target="_top" href="edit_texts.php?chg=' . $textid . '"><img src="icn/document--pencil.png" title="Edit Text" alt="Edit Text" /></a>&nbsp; | &nbsp;<a href="new_word.php?text=' . $textid . '&amp;lang=' . $langid . '" target="ro"><img src="icn/sticky-note--plus.png" title="New Term" alt="New Term" /></a>';
echo '</h4><table><tr><td><h3>READ&nbsp;▶</h3></td><td class="width99pc"><h3>' . tohtml($title) . (isset($sourceURI) ? ' <a href="' . $sourceURI . '" target="_blank"><img src="icn/chain.png" title="Text Source" alt="Text Source" /></a>' : '') . '</h3></td></tr></table>';

$showAll = getSettingZeroOrOne('showallwords', 1);

?>
<table class="width99pc"><tr><td class="center" colspan="7" style="padding:2px 5px 5px 5px;" nowrap="nowrap">TO DO: <span id="learnstatus"><?php echo texttodocount2($_REQUEST['text']); ?></span>&nbsp;&nbsp;&nbsp;&nbsp;<span title="[Show All] = ON: ALL terms are shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term. 
[Show All] = OFF: Multi-word terms now hide single words and shorter or overlapping multi-word terms.">Show All&nbsp;<input type="checkbox" id="showallwords" <?php echo get_checked($showAll); ?> /></span><span id="thetextid" class="hide"><?php echo $textid; ?></span></td></tr>

<?php

makeAudioPlayer($audio);

?>
</table>
<?php

pageend();

?>