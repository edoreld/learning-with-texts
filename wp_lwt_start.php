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
wp_lwt_start.php
----------------
To start LWT (and to login into WordPress), use this URL:
http://...path-to-wp-blog.../lwt/wp_lwt_start.php
Cookies must be enabled. A session cookie will be set.
The lwt installation must be in sub directory "lwt" under
the WordPress main drectory.
In the "lwt" directory, "connect.inc.php" must contain 
           include "wp_logincheck.inc.php"; 
at the end!
To properly log out from both WordPress and LWT, use:
http://...path-to-wp-blog.../lwt/wp_lwt_stop.php
***************************************************************/

require_once( '../wp-load.php' );

if (is_user_logged_in()){
	global $current_user;

	get_currentuserinfo();
	$wpuser = $current_user->ID;

	setcookie('LWT-WP-User', $wpuser, 0, '/');
	header("Location: ./index.php");
	exit;
}
else { 
	setcookie('LWT-WP-User', $wpuser, time() - 1000, '/');
	header("Location: ../wp-login.php?redirect_to=./lwt/wp_lwt_start.php");
	exit;
}

?>