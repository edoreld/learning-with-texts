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
Call: table_set_management.php
Analyse DB tables, and manage Table Sets
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$message = "";

if (isset($_REQUEST['delpref'])) {
	if($_REQUEST['delpref'] !== '-') {
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_archivedtexts','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_archtexttags','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_languages','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_sentences','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_tags','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_tags2','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_textitems','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_texts','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_texttags','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_words','');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_wordtags', '');
		$dummy = runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_settings', '');
		$message = 'Table Set "' . $_REQUEST['delpref'] . '" deleted';
		if ($_REQUEST['delpref'] == substr($tbpref, 0, -1)) {
			$tbpref = "";
			LWTTableSet ("current_table_prefix", $tbpref);
		}
	}
}

elseif (isset($_REQUEST['newpref'])) {
	if (in_array($_REQUEST['newpref'], getprefixes())) {
		$message = 'Table Set "' . $_REQUEST['newpref'] . '" already exists';
	} else {
		$tbpref = $_REQUEST['newpref'];
		LWTTableSet ("current_table_prefix", $tbpref);
		header("Location: index.php");
		exit(); 
	}
}

elseif (isset($_REQUEST['prefix'])) {
	if($_REQUEST['prefix'] !== '-') {
		$tbpref = $_REQUEST['prefix'];
		LWTTableSet ("current_table_prefix", $tbpref);
		header("Location: index.php");
		exit(); 
	}
}

pagestart('Select, Create or Delete a Table Set',false);
echo error_message_with_hide($message,0);

if ($fixed_tbpref) {

?>

<table class="tab1" cellspacing="0" cellpadding="5">
<tr>
<td class="td1">
	<p>These features are not currently not available.<br /><br />Reason:<br /><b>$tbpref</b> is set to a fixed value in <i>connect.inc.php</i>.<br />Please remove the definition<br /><span class="red"><b>$tbpref = '<?php echo substr($tbpref, 0, -1); ?>';</b></span></br />in <i>connect.inc.php</i> to make these features available.<br /> Then try again.</p>
	<p class="right">&nbsp;<br /><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>
</td>
</tr>
</table>

<?php	

} else {

$prefix = getprefixes();

?>

<table class="tab1" style="width: auto;" cellspacing="0" cellpadding="5">

<tr>
<th class="th1 center">Select</th>
<td class="td1">
<form name="f1" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<p>Table Set: <select name="prefix">
<option value="-" selected="selected">[Choose...]</option>
<option value="">Default Table Set</option>
<?php
foreach ($prefix as $value) {
?>
<option value="<?php echo tohtml($value); ?>"><?php echo tohtml($value); ?></option>
<?php
}
?>
</select> 
</p>
<p class="right">&nbsp;<br /><input type="submit" name="op" value="Start LWT with selected Table Set" />
</p>
</form>
</td>
</tr>

<tr>
<th class="th1 center">Create</th>
<td class="td1">
<form name="f2" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return check_table_prefix(document.f2.newpref.value);">
<p>New Table Set: <input type="text" name="newpref" value="" maxlength="20" size="20" />
</p>
<p class="right">&nbsp;<br /><input type="submit" name="op" value="Create New Table Set &amp; Start LWT" />
</p>
</form>
</td>
</tr>

<tr>
<th class="th1 center">Delete</th>
<td class="td1">
<form name="f3" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="if (document.f3.delpref.selectedIndex > 0) { return confirm('\n*** DELETING TABLE SET: ' + document.f3.delpref.options[document.f3.delpref.selectedIndex].text + ' ***\n\n*** ALL DATA IN THIS TABLE SET WILL BE LOST! ***\n\n*** ARE YOU SURE ?? ***'); } else { return true; }">
<p>Table Set: <select name="delpref">
<option value="-" selected="selected">[Choose...]</option>
<?php
foreach ($prefix as $value) {
	if ( $value != '') {
?>
<option value="<?php echo tohtml($value); ?>"><?php echo tohtml($value); ?></option>
<?php
	}
}
?>
</select>
<br />
(You cannot delete the Default Table Set.)
</p> 
<p class="right">&nbsp;<br /><span class="red2">YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;</span><input type="submit" name="op" value="DELETE Table Set" />
</p>
</form>
</td>
</tr>

<tr>
<td class="td1 right" colspan="2"> 
<input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" /></td>
</tr>

</table>

<?php

}

pageend();

?>