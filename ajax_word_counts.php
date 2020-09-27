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
Call: ajax_word_counts.php?id=[textid]
Calculating Word Counts, Ajax call in edit_texts.php
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$id = $_POST["id"] + 0;

$txttotalwords = textwordcount($id);
$txtworkedwords = textworkcount($id);
$txtworkedexpr = textexprcount($id);
$txtworkedall = $txtworkedwords + $txtworkedexpr;
$txttodowords = $txttotalwords - $txtworkedwords;
$percentunknown = 0;
if ($txttotalwords != 0) {
	$percentunknown = 
		round(100*$txttodowords/$txttotalwords,0);
	if ($percentunknown > 100) $percentunknown = 100;
	if ($percentunknown < 0) $percentunknown = 0;
}

$r = array();

$r[] = '<span title="Total">&nbsp;' . $txttotalwords . '&nbsp;</span>'; 
$r[] = '<span title="Saved" class="status4">&nbsp;' . ($txtworkedall > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=&amp;text=' . $id . '">' . $txtworkedwords . '+' . $txtworkedexpr . '</a>' : '0' ) . '&nbsp;';
$r[] = '<span title="Unknown" class="status0">&nbsp;' . $txttodowords . '&nbsp;</span>';
$r[] = '<span title="Unknown (%)">' . $percentunknown . '</span></td>';

echo json_encode($r);

?>