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
Call: insert_word_ignore.php?tid=[textid]&ord=[textpos]
Ignore single word (new term with status 98)
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$word = get_first_value("select TiText as value from " . $tbpref . "textitems where TiWordCount = 1 and TiTxID = " . $_REQUEST['tid'] . " and TiOrder = " . $_REQUEST['ord']);

$wordlc =	mb_strtolower($word, 'UTF-8');

$langid = get_first_value("select TxLgID as value from " . $tbpref . "texts where TxID = " . $_REQUEST['tid']);

pagestart("Term: " . $word,false);

$m1 = runsql('insert into ' . $tbpref . 'words (WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged,' .  make_score_random_insert_update('iv') . ') values( ' . 
$langid . ', ' . 
convert_string_to_sqlsyntax($word) . ', ' . 
convert_string_to_sqlsyntax($wordlc) . ', 98, NOW(), ' .  
make_score_random_insert_update('id') . ')','Term added');
$wid = get_last_key();

echo "<p>OK, this term will be ignored!</p>";

$hex = strToClassName($wordlc);

?>
<script type="text/javascript">
//<![CDATA[
var context = window.parent.frames['l'].document;
var contexth = window.parent.frames['h'].document;
var title = make_tooltip(<?php echo prepare_textdata_js($word); ?>,'*','','98');
$('.TERM<?php echo $hex; ?>', context).removeClass('status0').addClass('status98 word<?php echo $wid; ?>').attr('data_status','98').attr('data_wid','<?php echo $wid; ?>').attr('title',title);
$('#learnstatus', contexth).html('<?php echo texttodocount2($_REQUEST['tid']); ?>');
window.parent.frames['l'].focus();
window.parent.frames['l'].setTimeout('cClick()', 100);
//]]>
</script>
<?php

pageend();

?>