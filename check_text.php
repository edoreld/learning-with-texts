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
Call: check_text.php?...
			op=Check ... do the check
Check (parse & split) a Text (into sentences/words)
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

pagestart('Check a Text',true);

if (isset($_REQUEST['op'])) {

	echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
	if (strlen(prepare_textdata($_REQUEST['TxText'])) > 65000)
		echo "<p>Error: Text too long, must be below 65000 Bytes.</p>";
	else
		echo splitCheckText($_REQUEST['TxText'], $_REQUEST['TxLgID'], -1);
	echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';

} else {

?>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<table class="tab3" cellspacing="0" cellpadding="5">
<tr>
<td class="td1 right">Language:</td>
<td class="td1">
<select name="TxLgID" class="notempty setfocus">
<?php
echo get_languages_selectoptions(getSetting('currentlanguage'),'[Choose...]');
?>
</select> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td>
</tr>
<tr>
<td class="td1 right">Text:<br /><br />(max.<br />65,000<br />bytes)</td>
<td class="td1">
<textarea name="TxText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"></textarea> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td>
</tr>
<tr>
<td class="td1 right" colspan="2">
<input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" /> 
<input type="submit" name="op" value="Check" /></td>
</tr>
</table>
</form>
<?php

}

pageend();

?>