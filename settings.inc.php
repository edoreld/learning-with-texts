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
Debug switch / Display PHP error settings
Set script time limit
Start a PHP session if not one already exists
***************************************************************/

$debug = 0;        // 1 = debugging on, 0 = .. off
$dsplerrors = 0;   // 1 = display all errors on, 0 = .. off
$dspltime = 0;     // 1 = display time on, 0 = .. off

if ($dsplerrors) {
	@error_reporting(E_ALL);
	@ini_set('display_errors','1');
	@ini_set('display_startup_errors','1');
} else {
	@error_reporting(0);
	@ini_set('display_errors','0');
	@ini_set('display_startup_errors','0');
}

@ini_set('max_execution_time', '600');  // 10 min.
@set_time_limit(600);  // 10 min.

@ini_set('memory_limit', '999M');  

if(session_id() == '') {
	// session isn't started
	$err = @session_start();
	if ($err === FALSE) 
		my_die('SESSION error (Impossible to start a PHP session)');
	if(session_id() == '')
		my_die('SESSION ID empty (Impossible to start a PHP session)');
	if (! isset($_SESSION))
		my_die('SESSION array not set (Impossible to start a PHP session)');
}

?>