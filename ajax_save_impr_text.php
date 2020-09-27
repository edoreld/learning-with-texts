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
Call: ajax_save_impr_text.php
Save Improved Annotation
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$textid = $_POST['id'] + 0;
$elem = $_POST['elem'];
$stringdata = stripTheSlashesIfNeeded($_POST['data']);
$data = json_decode ($stringdata);

$val = $data->{$elem};
if(substr($elem,0,2) == "rg") {
	if($val == "") $val = $data->{'tx' . substr($elem,2)}; 
}
$line = substr($elem,2) + 0;

// Save data
$success = "NOTOK";
$ann = get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
$items = preg_split('/[\n]/u', $ann);
if (count($items) >= $line) {
	$vals = preg_split('/[\t]/u', $items[$line-1]);
	if ($vals[0] > -1 && count($vals) == 4) {
		$vals[3] = $val;
		$items[$line-1] = implode("\t", $vals);
		$dummy = runsql('update ' . $tbpref . 'texts set ' .
			'TxAnnotatedText = ' . convert_string_to_sqlsyntax(implode("\n",$items)) . ' where TxID = ' . $textid, "");
		$success = "OK";
	}
}

// error_log ("ajax_save_impr_text / " . $success . " / " . $stringdata);

echo $success;

?>
