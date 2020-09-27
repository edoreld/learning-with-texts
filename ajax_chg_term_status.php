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
Call: ajax_chg_term_status.php
Change term status (Table Test)
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$wid = $_REQUEST['id'];
$up = $_REQUEST['data'];

$currstatus = get_first_value('SELECT WoStatus as value FROM ' . $tbpref . 'words where WoID = ' . $wid);

if (! isset($currstatus)) {
	echo '';
}

else {
	$currstatus = $currstatus + 0;
	if ($up == 1) {
		$currstatus += 1; // 98,1,2,3,4,5 => 99,2,3,4,5,6
		if ( $currstatus == 99 ) $currstatus = 1;  // 98->1
		if ( $currstatus == 6 ) $currstatus = 99;  // 5->99 
	} else {
		$currstatus -= 1; // 1,2,3,4,5,99 => 0,1,2,3,4,98
		if ( $currstatus == 98 ) $currstatus = 5;  // 99->5
		if ( $currstatus == 0 ) $currstatus = 98;  // 1->98
	}

	if ( ($currstatus >= 1 && $currstatus <= 5) || $currstatus == 99 || $currstatus == 98 ) {
		$m1 = runsql('update ' . $tbpref . 'words set WoStatus = ' . 
			$currstatus . ', WoStatusChanged = NOW(),' . make_score_random_insert_update('u') . ' where WoID = ' . $wid, '') + 0;
		if ($m1 == 1) {
			$currstatus = get_first_value('SELECT WoStatus as value FROM ' . $tbpref . 'words where WoID = ' . $wid);
			if (! isset($currstatus)) {
				echo '';
			}
			echo make_status_controls_test_table(1, $currstatus, $wid);
		}
	} else {
		echo '';
	}
}

?>