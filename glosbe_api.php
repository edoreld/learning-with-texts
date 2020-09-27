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
Call: glosbe_api.php?from=...&dest=...&phrase=...
      ... from=L2 language code (see Glosbe)
      ... dest=L1 language code (see Glosbe)
      ... phrase=... word or expression to be translated by 
                     Glosbe API (see http://glosbe.com/a-api)

Call Glosbe Translation API, analyze and present JSON results
for easily filling the "new word form"
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$from = trim(stripTheSlashesIfNeeded($_REQUEST["from"]));
$dest = trim(stripTheSlashesIfNeeded($_REQUEST["dest"]));
$destorig = $dest;
$phrase = mb_strtolower(trim(stripTheSlashesIfNeeded($_REQUEST["phrase"])), 'UTF-8');
$ok = FALSE;

pagestart_nobody('');
$titletext = '<a href="http://glosbe.com/' . $from . '/' . $dest . '/' . $phrase . '">Glosbe Dictionary (' . tohtml($from) . "-" . tohtml($dest) . "):  &nbsp; <span class=\"red2\">" . tohtml($phrase) . "</span></a>";
echo '<h3>' . $titletext . '</h3>';
echo '<p>(Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> to copy word(s) into above term)<br />&nbsp;</p>';

?>
<script type="text/javascript">
//<![CDATA[
function addTranslation (s) {
	var w = window.parent.frames['ro'];
	if (typeof w == 'undefined') w = window.opener;
	if (typeof w == 'undefined') {
		alert ('Translation can not be copied!');
		return;
	}
	var c = w.document.forms[0].WoTranslation;
	if (typeof c != 'object') {
		alert ('Translation can not be copied!');
		return;
	}
	var oldValue = c.value;
	if (oldValue.trim() == '') {
		c.value = s;
		w.makeDirty();
	}
	else {
		if (oldValue.indexOf(s) == -1) {
			c.value = oldValue + ' / ' + s;
			w.makeDirty();
		}
		else {
			if (confirm('"' + s + '" seems already to exist as a translation.\nInsert anyway?')) { 
				c.value = oldValue + ' / ' + s;
				w.makeDirty();
			}
		}
	}
}
//]]>
</script>
<?php

if ($from != '' && $dest != '' && $phrase != '') {

	$glosbe_data = file_get_contents('http://glosbe.com/gapi/translate?from=' . urlencode($from) . '&dest=' . urlencode($dest) . '&format=json&phrase=' . urlencode($phrase));

	if(! ($glosbe_data === FALSE)) {

		$data = json_decode ($glosbe_data, true);
		if ( isset($data['phrase']) ) {
			$ok = (($data['phrase'] == $phrase) && (isset($data['tuc'])));
		}
	
	}
	
}

if ( $ok ) {

	if (count($data['tuc']) > 0) {
	
		$i = 0;

		echo "<p>\n";
		foreach ($data['tuc'] as &$value) {
			$word = '';
			if (isset($value['phrase'])) {
				if (isset($value['phrase']['text']))
					$word = $value['phrase']['text'];
			} else if (isset($value['meanings'])) {
				if (isset($value['meanings'][0]['text']))
					$word = "(" . $value['meanings'][0]['text'] . ")";
			}
			if ($word != '') {
				$word = trim(strip_tags($word));
				echo '<span class="click" onclick="addTranslation(' . prepare_textdata_js($word) . ');"><img src="icn/tick-button.png" title="Copy" alt="Copy" /> &nbsp; ' . $word . '</span><br />' . "\n";
				$i++;
			}
		}
		echo "</p>";
		if ($i) {
		echo '<p>&nbsp;<br/>' . $i . ' translation' . ($i==1 ? '' : 's') . ' retrieved via <a href="http://glosbe.com/a-api" target="_blank">Glosbe API</a>.</p>';
		}
		
	} else {
		
		echo '<p>No translations found (' . tohtml($from) . '-' . tohtml($dest) . ').</p>';
		
		if ($dest != "en" && $from != "en") {
		
			$ok = FALSE;
		
			$dest = "en";
			$titletext = '<a href="http://glosbe.com/' . $from . '/' . $dest . '/' . $phrase . '">Glosbe Dictionary (' . tohtml($from) . "-" . tohtml($dest) . "):  &nbsp; <span class=\"red2\">" . tohtml($phrase) . "</span></a>";
			echo '<hr /><p>&nbsp;</p><h3>' . $titletext . '</h3>';

			$glosbe_data = file_get_contents('http://glosbe.com/gapi/translate?from=' . urlencode($from) . '&dest=' . urlencode($dest) . '&format=json&phrase=' . urlencode($phrase));

			if(! ($glosbe_data === FALSE)) {

				$data = json_decode ($glosbe_data, true);
				if ( isset($data['phrase']) ) {
					$ok = (($data['phrase'] == $phrase) && (isset($data['tuc'])));
				}

			}

			if ( $ok ) {

				if (count($data['tuc']) > 0) {
	
					$i = 0;

					echo "<p>&nbsp;<br />\n";
					foreach ($data['tuc'] as &$value) {
						$word = '';
						if (isset($value['phrase'])) {
							if (isset($value['phrase']['text']))
								$word = $value['phrase']['text'];
						} else if (isset($value['meanings'])) {
							if (isset($value['meanings'][0]['text']))
								$word = "(" . $value['meanings'][0]['text'] . ")";
						}
						if ($word != '') {
							$word = trim(strip_tags($word));
							echo '<span class="click" onclick="addTranslation(' . prepare_textdata_js($word) . ');"><img src="icn/tick-button.png" title="Copy" alt="Copy" /> &nbsp; ' . $word . '</span><br />' . "\n";
							$i++;
						}
					}
					echo "</p>";
					if ($i) {
					echo '<p>&nbsp;<br/>' . $i . ' translation' . ($i==1 ? '' : 's') . ' retrieved via <a href="http://glosbe.com/a-api" target="_blank">Glosbe API</a>.</p>';
					}
		
				} else {
	
					echo '<p>&nbsp;<br/>No translations found (' . tohtml($from) . '-' . tohtml($dest) . ').</p>';
		
				}
	
			} else {

				echo '<p>&nbsp;<br/>Retrieval error (' . tohtml($from) . '-' . tohtml($dest) . '). Possible reason: There is a limit of Glosbe API calls that may be done from one IP address in a fixed period of time, to prevent from abuse.</p>';

			}
		}
	
	}
	
} else {

	echo '<p>Retrieval error (' . tohtml($from) . '-' . tohtml($dest) . '). Possible reason: There is a limit of Glosbe API calls that may be done from one IP address in a fixed period of time, to prevent from abuse.</p>';

}

echo '&nbsp;<hr />&nbsp;<form action="glosbe_api.php" method="get">Unhappy?<br/>Change term: 
<input type="text" name="phrase" maxlength="250" size="15" value="' . tohtml($phrase) . '">
<input type="hidden" name="from" value="' . tohtml($from) . '">
<input type="hidden" name="dest" value="' . tohtml($destorig) . '">
<input type="submit" value="Translate via Glosbe">
</form>';

pageend();

?>