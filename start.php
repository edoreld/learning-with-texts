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
Call: start.php
Analyse DB tables, select Table Set, start LWT
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

if ($fixed_tbpref) {
	header("Location: index.php");
	exit(); 
}

if (isset($_REQUEST['prefix'])) {
	if($_REQUEST['prefix'] !== '-') {
		$tbpref = $_REQUEST['prefix'];
		LWTTableSet ("current_table_prefix", $tbpref);
		header("Location: index.php");
		exit(); 
	}
}

$prefix = getprefixes();

if (count($prefix) == 0) {
	$tbpref = '';
	LWTTableSet ("current_table_prefix", $tbpref);
	header("Location: index.php");
	exit(); 
}

pagestart('Select Table Set',false);

?>

<table class="tab1" style="width: auto;" cellspacing="0" cellpadding="5">

<tr>
<th class="th1">
<form name="f1" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<p>Select: <select name="prefix">
<option value="" <?php echo ($tbpref == '' ? 'selected="selected"': ''); ?>>Default Table Set</option>
<?php
foreach ($prefix as $value) {
?>
<option value="<?php echo tohtml($value); ?>" <?php echo (substr($tbpref,0,-1) == $value ? 'selected="selected"': ''); ?>><?php echo tohtml($value); ?></option>
<?php
}
?>
</select> 
</p>
<p class="center"><input type="submit" name="op" value="Start LWT" />
</p>
</form>
</th>
</tr>

</table>

<?php

pageend();

?>