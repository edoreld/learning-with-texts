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
Call: show_word.php?wid=...&ann=...
Show term
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

pagestart_nobody('Term');

$wid = getreq('wid');
$ann = stripTheSlashesIfNeeded($_REQUEST["ann"]);

if ($wid == '') my_die ('Word not found in show_word.php');

$sql = 'select WoLgID, WoText, WoTranslation, WoSentence, WoRomanization, WoStatus from ' . $tbpref . 'words where WoID = ' . $wid;
$res = do_mysqli_query($sql);
if ($record = mysqli_fetch_assoc($res)) {

	$transl = repl_tab_nl($record['WoTranslation']);
	if($transl == '*') $transl='';
	
	$tags = getWordTagList($wid, '', 0, 0);
	$rom = $record['WoRomanization'];
	$scrdir = getScriptDirectionTag($record['WoLgID']);

?>


<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<td class="td1 right" style="width:30px;">Term:</td>
<td class="td1" style="font-size:120%;" <?php echo $scrdir; ?>><b><?php echo tohtml($record['WoText']); ?></b></td>
</tr>
<tr>
<td class="td1 right">Translation:</td>
<td class="td1" style="font-size:120%;"><b><?php echo 
	str_replace_first(tohtml($ann), '<span style="color:red">' . tohtml($ann) . 
	'</span>', tohtml($transl)); ?></b></td>
</tr>
<?php if ($tags != '') { ?>
<tr>
<td class="td1 right">Tags:</td>
<td class="td1" style="font-size:120%;"><b><?php echo tohtml($tags); ?></b></td>
</tr>
<?php } ?>
<?php if ($rom != '') { ?>
<tr>
<td class="td1 right">Romaniz.:</td>
<td class="td1" style="font-size:120%;"><b><?php echo tohtml($rom); ?></b></td>
</tr>
<?php } ?>
<tr>
<td class="td1 right">Sentence<br />Term in {...}:</td>
<td class="td1" <?php echo $scrdir; ?>><?php echo tohtml($record['WoSentence']); ?></td>
</tr>
<tr>
<td class="td1 right">Status:</td>
<td class="td1"><?php echo get_colored_status_msg($record['WoStatus']); ?></span>
</td>
</tr>
</table>

<script type="text/javascript">
//<![CDATA[
window.parent.frames['l'].focus();
window.parent.frames['l'].setTimeout('cClick()', 100);
//]]>
</script>

<?php
}

mysqli_free_result($res);

pageend();

?>