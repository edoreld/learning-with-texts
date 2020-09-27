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
Call 1: trans.php?x=1&t=[textid]&i=[textpos]
				GTr translates sentence in Text t, Pos i
Call 2: trans.php?x=2&t=[text]&i=[dictURI]
				translates text t with dict via dict-url i
Get a translation from Web Dictionary
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$x = $_REQUEST["x"];
$i = stripTheSlashesIfNeeded($_REQUEST["i"]);
$t = stripTheSlashesIfNeeded($_REQUEST["t"]);

if ( $x == 1 ) {
	$sql = 'select SeText, LgGoogleTranslateURI from ' . $tbpref . 'languages, ' . $tbpref . 'sentences, ' . $tbpref . 'textitems where TiSeID = SeID and TiLgID = LgID and TiTxID = ' . $t . ' and TiOrder = ' . $i;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	if ($record) {
		$satz = $record['SeText'];
		$trans = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
		if(substr($trans,0,1) == '*') $trans = substr($trans,1);
	} else {
		my_die("No results: $sql"); 
	}
	mysqli_free_result($res);
	if ($trans != '') {
		/*
		echo "{" . $i . "}<br />";
		echo "{" . $t . "}<br />";
		echo "{" . createTheDictLink($trans,$satz) . "}<br />";
		*/
		header("Location: " . createTheDictLink($trans,$satz));
	}	
	exit();
}	

if ( $x == 2 ) {
	/*
	echo "{" . $i . "}<br />";
	echo "{" . $t . "}<br />";
	echo "{" . createTheDictLink($i,$t) . "}<br />";
	*/
	header("Location: " . createTheDictLink($i,$t));
	exit();
}	

?>