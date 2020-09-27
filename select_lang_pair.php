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
Call: select_lang_pair.php
Display Language Pair Selection Window for Wizard
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );
require_once( 'langdefs.inc.php' );

function get_wizard_selectoptions($v) {
	global $langDefs;
	$r = "<option value=\"\"" . get_selected($v,"") . ">[Choose...]</option>";
	$keys = array_keys($langDefs);
	foreach ($keys as $item) {
		$r .= "<option value=\"" . $item . "\"" . get_selected($v,$item) . ">" . $item . "</option>";
	}
	return $r;
}

pagestart_nobody('Language Settings Wizard','body {background-color: #FFFACD;}');

$currentnativelanguage = getSetting('currentnativelanguage');

?>

<script type="text/javascript">
//<![CDATA[

<?php echo "var LANGDEFS = " . json_encode($langDefs) . ";\n"; ?>

function wizard_go() {
	var l1 = $('#l1').val();
	var l2 = $('#l2').val();
	if (l1 == '') {
		alert ('Please choose your native language (L1)!');
		return;
	}
	if (l2 == '') {
		alert ('Please choose your language you want to read/study (L2)!');
		return;
	}
	if (l2 == l1) {
		alert ('L1 L2 Languages must not be equal!');
		return;
	}
	var w = window.opener;
	if (typeof w == 'undefined') {
		alert ('Language setting cannot be set. Please try again.');
		wizard_exit();
	}
	var context = w.document;
	$('input[name="LgName"]',context).val(l2);	
	$('input[name="LgDict1URI"]',context).val(
		'*https://de.glosbe.com/' + LANGDEFS[l2][0] + '/' + 
		LANGDEFS[l1][0] + '/###'
		);	
	$('input[name="LgGoogleTranslateURI"]',context).val(
		'*http://translate.google.com/?ie=UTF-8&sl=' + 
		LANGDEFS[l2][1] + '&tl=' + LANGDEFS[l1][1] + '&text=###'
		);	
	$('select[name="LgTextSize"]',context).val(LANGDEFS[l2][2] ? 200 : 150);	
	$('input[name="LgRegexpSplitSentences"]',context).val(LANGDEFS[l2][4]);	
	$('input[name="LgRegexpWordCharacters"]',context).val(LANGDEFS[l2][3]);	
	$('select[name="LgSplitEachChar"]',context).val(LANGDEFS[l2][5] ? 1 : 0);	
	$('select[name="LgRemoveSpaces"]',context).val(LANGDEFS[l2][6] ? 1 : 0);	
	$('select[name="LgRightToLeft"]',context).val(LANGDEFS[l2][7] ? 1 : 0);	
	wizard_exit();
}

function wizard_exit() {
	window.close();
}

//]]>
</script>

<div class="center">

<p class="wizard">
<img src="icn/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" />
</p>

<h3 class="wizard">
Language Settings Wizard
</h3>

<p class="wizard">
<b>My Native language is:</b>
<br />
L1: 
<select name="l1" id="l1" onchange= "{do_ajax_save_setting('currentnativelanguage',($('#l1').val()));}">
<?php echo get_wizard_selectoptions($currentnativelanguage); ?>
</select>
</p>

<p class="wizard">
<b>I want to study:</b>
<br />
L2: 
<select name="l2" id="l2">
<?php echo get_wizard_selectoptions(''); ?>
</select>
</p>

<p class="wizard">
<input type="button" style="font-size:1.1em;" value="Set Language Settings" onclick="wizard_go();" />
</p>

<p class="wizard">
<input type="button" value="Cancel" onclick="wizard_exit();" />
</p>

</div>

<?php

pageend();

?>