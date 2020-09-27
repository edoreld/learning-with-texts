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
Call: inline_edit.php?...
...
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$value = (isset($_POST['value'])) ? $_POST['value'] : "";
$value = trim($value);
$id = (isset($_POST['id'])) ? $_POST['id'] : "";

if (substr($id, 0, 5) == "trans") {
	$id = substr($id, 5);
	if($value == '') $value='*';
	$message = runsql('update ' . $tbpref . 'words set WoTranslation = ' . 
		convert_string_to_sqlsyntax(repl_tab_nl($value)) . ' where WoID = ' . $id,
		"");
	echo get_first_value("select WoTranslation as value from " . $tbpref . "words where WoID = " . $id);
	exit;
}

if (substr($id, 0, 5) == "roman") {
	if ($value == '*') $value='';
	$id = substr($id, 5);
	$message = runsql('update ' . $tbpref . 'words set WoRomanization = ' . 
		convert_string_to_sqlsyntax(repl_tab_nl($value)) . ' where WoID = ' . $id,
		"");
	$value = get_first_value("select WoRomanization as value from " . $tbpref . "words where WoID = " . $id);
	if ($value == '') 
		echo '*';
	else 
		echo $value;
	exit;
}

echo "ERROR - please refresh page!";

?>