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
Call: save_setting_redirect.php?k=[key]&v=[value]&u=[RedirURI]
Save a Setting (k/v) and redirect to URI u
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$k = getreq('k');
$v = getreq('v');
$u = getreq('u');

if($k == 'currentlanguage') {

	unset($_SESSION['currenttextpage']);
	unset($_SESSION['currenttextquery']);
	unset($_SESSION['currenttexttag1']);
	unset($_SESSION['currenttexttag2']);
	unset($_SESSION['currenttexttag12']);
	
	unset($_SESSION['currentwordpage']);
	unset($_SESSION['currentwordquery']);
	unset($_SESSION['currentwordstatus']);
	unset($_SESSION['currentwordtext']);
	unset($_SESSION['currentwordtag1']);
	unset($_SESSION['currentwordtag2']);
	unset($_SESSION['currentwordtag12']);
	
	unset($_SESSION['currentarchivepage']);
	unset($_SESSION['currentarchivequery']);
	unset($_SESSION['currentarchivetexttag1']);
	unset($_SESSION['currentarchivetexttag2']);
	unset($_SESSION['currentarchivetexttag12']);
	
	saveSetting('currenttext','');
}

saveSetting($k,$v);
header("Location: " . $u);
exit(); 
?>