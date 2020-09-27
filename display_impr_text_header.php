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
Call: display_impr_text_header.php?text=[textid]
Display an improved annotated text (top frame)
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

pagestart_nobody(tohtml($title));
echo '<h2 class="center" style="margin:5px;margin-top:-10px;">';

?>

<script type="text/javascript">
//<![CDATA[
	function do_hide_t() {
		$('#showt').show(); 
		$('#hidet').hide();
		$('.anntermruby', window.parent.frames['text'].document).css('color','#E5E4E2').css('background-color', '#E5E4E2');
	}
	function do_show_t() {
		$('#showt').hide(); 
		$('#hidet').show(); 
		$('.anntermruby', window.parent.frames['text'].document).css('color','black').css('background-color', 'white');
	}
	function do_hide_a() {
		$('#show').show(); 
		$('#hide').hide(); 
		$('.anntransruby2', window.parent.frames['text'].document).css('color','#C8DCF0').css('background-color', '#C8DCF0');
	}
	function do_show_a() {
		$('#show').hide(); 
		$('#hide').show(); 
		$('.anntransruby2', window.parent.frames['text'].document).css('color','#006699').css('background-color', 'white');
	}
//]]>
</script>

<?php

echo '<img id="hidet" style="margin-bottom:-5px;" class="click" src="icn/light-bulb-T.png" title="Toggle Text Display (Now ON)" alt="Toggle Text Display (Now ON)" onclick="do_hide_t();" />';
echo '<img id="showt" style="display:none; margin-bottom:-5px;" class="click" src="icn/light-bulb-off-T.png" title="Toggle Text Display (Now OFF)" alt="Toggle Text Display (Now OFF)" onclick="do_show_t();" />';
echo '<img id="hide" style="margin-bottom:-5px;" class="click" src="icn/light-bulb-A.png" title="Toggle Annotation Display (Now ON)" alt="Toggle Annotation Display (Now ON)" onclick="do_hide_a();" />';
echo '<img id="show" style="display:none; margin-bottom:-5px;" class="click" src="icn/light-bulb-off-A.png" title="Toggle Annotation Display (Now OFF)" alt="Toggle Annotation Display (Now OFF)" onclick="do_show_a();" />';
echo ' &nbsp; &nbsp; ';
echo tohtml($title);
echo (isset($sourceURI) ? ' <a href="' . $sourceURI . '" target="_blank"><img src="icn/chain.png" title="Text Source" alt="Text Source" /></a>' : '');
echo getPreviousAndNextTextLinks($textid, 'display_impr_text.php?text=', TRUE, ' &nbsp; &nbsp; ');
echo ' <img class="click" src="icn/cross.png" title="Close Window" alt="Close Window" onclick="top.close();" /></span></h2>';

makeAudioPlayer($audio);

?>
</table>
<?php

pageend();

?>
