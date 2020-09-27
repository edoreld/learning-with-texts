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
Call: install_demo.php
Install LWT Demo Database
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$message = '';

// RESTORE DEMO

if (isset($_REQUEST['install'])) {
	$file = getcwd() . '/install_demo_db.sql.gz';
	if ( file_exists($file) ) {
		$handle = gzopen ($file, "r");
		if ($handle === FALSE) {
			$message = "Error: File ' . $file . ' could not be opened";
		} // $handle not OK
		else { // $handle OK
			$message = restore_file($handle, "Demo Database");
		} // $handle OK
	} // restore file specified
	else {
		$message = "Error: File ' . $file . ' does not exist";
	}
} 

pagestart('Install LWT Demo Database',true);

echo error_message_with_hide($message,1);

$langcnt = get_first_value('select count(*) as value from ' . $tbpref . 'languages');

if ($tbpref == '') 
	$prefinfo = "(Default Table Set)";
else
	$prefinfo = "(Table Set: <i>" . tohtml(substr($tbpref,0,-1)) . "</i>)";

?>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure?');">
<table class="tab3" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 center">Install Demo</th>
<td class="td1">
<p class="smallgray2">
The database <i><?php echo tohtml($dbname); ?></i> <?php echo $prefinfo; ?> will be <b>replaced</b> by the LWT demo database.

<?php 
if ($langcnt > 0 ) { 
	?>
	<br />The existent database will be <b>overwritten!</b>
	<?php 
} 
?>

</p>
<p class="right">&nbsp;<br /><span class="red2">YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;</span> 
<input type="submit" name="install" value="Install LWT demo database" /></p>
</td>
</tr>
<tr>
<td class="td1 right" colspan="2"> 
<input type="button" value="&lt;&lt; Back to LWT Main Menu" onclick="location.href='index.php';" /></td>
</tr>
</table>
</form>

<?php

pageend();

?>