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
Call: ajax_add_term_transl.php
Add a translation to term
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$wid = $_POST['id'] + 0;
$data = trim(stripTheSlashesIfNeeded($_POST['data'])); // translation
$text = trim(stripTheSlashesIfNeeded($_POST['text'])); // only wid=0 (new)
$lang = $_POST['lang'] + 0; // only wid=0 (lang-id)

// Save data
$success = "";

if ($wid == 0) {
	$textlc = mb_strtolower($text, 'UTF-8');
	$dummy = runsql('insert into ' . $tbpref . 'words (WoLgID, WoTextLC, WoText, ' .
		'WoStatus, WoTranslation, WoSentence, WoRomanization, WoStatusChanged,' .  make_score_random_insert_update('iv') . ') values( ' . 
		$lang . ', ' .
		convert_string_to_sqlsyntax($textlc) . ', ' .
		convert_string_to_sqlsyntax($text) . ', 1, ' .		
		convert_string_to_sqlsyntax($data) . ', ' .
		convert_string_to_sqlsyntax('') . ', ' .
		convert_string_to_sqlsyntax('') . ', NOW(), ' .  
		make_score_random_insert_update('id') . ')', "");
	if ($dummy == 1) $success = $textlc;	
}

else if(get_first_value("select count(WoID) as value from " . $tbpref . "words where WoID = " . $wid) == 1) {

	$oldtrans = get_first_value("select WoTranslation as value from " . $tbpref . "words where WoID = " . $wid);
	
	$oldtransarr = preg_split('/[' . get_sepas()  . ']/u', $oldtrans);
	array_walk($oldtransarr, 'trim_value');
	
	if (! in_array($data, $oldtransarr)) {
		if ((trim($oldtrans) == '') || (trim($oldtrans) == '*')) {
			$oldtrans = $data;
		} else {
			$oldtrans .= ' ' . get_first_sepa() . ' ' . $data;
		}
		$dummy = runsql('update ' . $tbpref . 'words set ' .
			'WoTranslation = ' . convert_string_to_sqlsyntax($oldtrans) . ' where WoID = ' . $wid, "");
	}
	$success = get_first_value("select WoTextLC as value from " . $tbpref . "words where WoID = " . $wid);;	
}

echo $success;

?>
