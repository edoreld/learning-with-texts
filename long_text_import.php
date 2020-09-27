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
Call: long_text_import.php?...
			op=...
Long Text Import
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

pagestart('Long Text Import',true);

$message = '';
$max_input_vars = ini_get('max_input_vars');
if ($max_input_vars === FALSE) $max_input_vars = 1000;
if ($max_input_vars == '') $max_input_vars = 1000;

if (isset($_REQUEST['op'])) {
	
	if (substr($_REQUEST['op'],0,5) == 'NEXT ') {
		
		$langid = $_REQUEST["LgID"];
		$title = stripTheSlashesIfNeeded($_REQUEST["TxTitle"]);
		$paragraph_handling = $_REQUEST["paragraph_handling"];
		$maxsent = $_REQUEST["maxsent"];
		$source_uri = stripTheSlashesIfNeeded($_REQUEST["TxSourceURI"]);
		$texttags = json_encode(stripTheSlashesIfNeeded($_REQUEST["TextTags"]));
		
		if ( isset($_FILES["thefile"]) && $_FILES["thefile"]["tmp_name"] != "" && $_FILES["thefile"]["error"] == 0 ) {
			$data = file_get_contents($_FILES["thefile"]["tmp_name"]);
			$data = str_replace("\r\n","\n",$data);
			$data = replace_supp_unicode_planes_char($data);
		} else {
			$data = replace_supp_unicode_planes_char(
				prepare_textdata($_REQUEST["Upload"]));
		}
		$data = trim($data);
		
		if((0 + $paragraph_handling) == 2) {
			$data = preg_replace('/\n\s*?\n/u', '¶', $data);
			$data = str_replace("\n",' ',$data);
			$data = preg_replace('/\s{2,}/u', ' ', $data);
			$data = str_replace('¶ ','¶',$data);
			$data = str_replace('¶',"\n",$data);
		} else {
			$data = str_replace("\n",'¶',$data);
			$data = preg_replace('/\s{2,}/u', ' ', $data);
			$data = str_replace('¶ ','¶',$data);
			$data = str_replace('¶',"\n",$data);
		}
		
		if ($data == "") {
			$message = "Error: No text specified!";
			echo error_message_with_hide($message,0);
		}
		else {
			$sent_array = splitCheckText($data, $langid, -2);
			$texts = array();
			$text_index = 0;
			$texts[$text_index] = array();
			$cnt = 0;
			$bytes = 0;
			foreach ($sent_array as $item) {
				$item_len = strlen($item)+1;
				if ($item != '¶') $cnt++;
				if (($cnt <= $maxsent) && (($bytes+$item_len) < 65000)) {
					$texts[$text_index][] = $item;
					$bytes += $item_len;
				} else {
					$text_index++;
					$texts[$text_index] = array($item);
					$cnt = 1;
					$bytes = $item_len;
				}
			}
			$textcount = count($texts);
			$plural = ($textcount==1 ? '' : 's');
			$shorter = ($textcount==1 ? ' ' : ' shorter ');
			
			if ($textcount > $max_input_vars-20) {
				$message = "Error: Too many texts (" . $textcount . " > " . ($max_input_vars-20) . "). You must increase 'Maximum Sentences per Text'!";
				echo error_message_with_hide($message,0);
			}
			else {

?>
		<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>	
		<script type="text/javascript">
		//<![CDATA[
		makeDirty();
		//]]>
		</script>
		<form enctype="multipart/form-data"  action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input type="hidden" name="LgID" value="<?php echo $langid; ?>" />
			<input type="hidden" name="TxTitle" value="<?php echo tohtml($title); ?>" />
			<input type="hidden" name="TxSourceURI" value="<?php echo tohtml($source_uri); ?>" />
			<input type="hidden" name="TextTags" value="<?php echo tohtml($texttags); ?>" />
			<input type="hidden" name="TextCount" value="<?php echo $textcount; ?>" />
			<table class="tab3" cellspacing="0" cellpadding="5">
			<tr>
			<td class="td1" colspan="2">
			<?php echo "This long text will be split into " . $textcount . $shorter . "text" . $plural . " - as follows:"; ?>
			</td>
			</tr>
			<tr>
			<td class="td1 right" colspan="2"><input type="button" value="Cancel" onclick="{resetDirty(); location.href='index.php';}" /> &nbsp; | &nbsp; <input type="button" value="Go Back" onclick="{resetDirty(); history.back();}" /> &nbsp; | &nbsp; <input type="submit" name="op" value="Create <?php echo $textcount; ?> text<?php echo $plural; ?>" />
			</td>
			</tr>
<?php
				$textno = -1;
				foreach ($texts as $item) {
					$textno++;
					$textstring = str_replace("¶","\n",implode(" ",$item));
					$bytes = strlen($textstring);
?>			
			<tr>
			<td class="td1 right"><b>Text <?php echo $textno+1; ?>:</b><br /><br /><br />Length:<br /><?php echo $bytes; ?><br />Bytes</td>
			<td class="td1">
			<textarea readonly="readonly" <?php echo getScriptDirectionTag($langid); ?> name="text[<?php echo $textno; ?>]" cols="60" rows="10"><?php echo str_replace('¶',"\n",str_replace("¶ ","\n",implode(" ",$item))); ?></textarea>
			</td>
			</tr>
<?php
				}
?>
		</table>
		</form>
<?php
			}
		}
	}
	
	elseif (substr($_REQUEST['op'],0,5) == 'Creat') {

		$langid = $_REQUEST["LgID"] + 0;
		$title = stripTheSlashesIfNeeded($_REQUEST["TxTitle"]);
		$source_uri = stripTheSlashesIfNeeded($_REQUEST["TxSourceURI"]);
		$_REQUEST["TextTags"] = json_decode(stripTheSlashesIfNeeded($_REQUEST["TextTags"]), true);
		$textcount = $_REQUEST["TextCount"] + 0;
		$texts = $_REQUEST["text"];
		
		if ( count($texts) != $textcount ) {
			$message = "Error: Number of texts wrong: " .  count($texts) . " != " . $textcount;
		} else {
			
			$imported = 0;
			for ($i = 0; $i < $textcount; $i++) {
				$texts[$i] = remove_soft_hyphens($texts[$i]);
				$counter = makeCounterWithTotal ($textcount, $i+1);
				$thistitle = $title . ($counter == '' ? '' : (' (' . $counter . ')')); 
				$imported = $imported + runsql('insert into ' . $tbpref . 'texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) values( ' . 
				$langid . ', ' . 
				convert_string_to_sqlsyntax($thistitle) . ', ' . 
				convert_string_to_sqlsyntax($texts[$i]) . ", '', NULL, " .
				convert_string_to_sqlsyntax($source_uri) . ')', '');
				$id = get_last_key();
				saveTextTags($id);	
				splitCheckText ($texts[$i], $langid, $id);
			}
		
		}
		
		$message = $imported . " Text(s) imported!";
		
		echo error_message_with_hide($message,0);

?>		
		<p>&nbsp;<br /><input type="button" value="Show Texts" onclick="location.href='edit_texts.php';" /></p>
<?php
		
	}

} else {

?>

	<script type="text/javascript" src="js/unloadformcheck.js" charset="utf-8"></script>			

	<form enctype="multipart/form-data" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<table class="tab3" cellspacing="0" cellpadding="5">
	<tr>
	<td class="td1 right">Language:</td>
	<td class="td1">
	<select name="LgID" class="notempty setfocus">
	<?php
	echo get_languages_selectoptions(getSetting('currentlanguage'),'[Choose...]');
	?>
	</select> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /> 
	</td>
	</tr>
	<tr>
	<td class="td1 right">Title:</td>
	<td class="td1"><input type="text" class="notempty checkoutsidebmp" data_info="Title" name="TxTitle" value="" maxlength="200" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">
		Text:
	</td>
	<td class="td1">
	Either specify a <b>File to upload</b>:<br />
	<input name="thefile" type="file" /><br /><br />
	<b>Or</b> paste a text from the clipboard (and do <b>NOT</b> specify file):<br />
	<textarea class="checkoutsidebmp" data_info="Upload" name="Upload" cols="60" rows="15"></textarea>
	
	<p class="smallgray">
	If the text is too long, the import may not be possible.<br />
	Current upload limits (in bytes):<br />
	<b>post_max_size = <?php echo ini_get('post_max_size'); ?> / 
	upload_max_filesize = <?php echo ini_get('upload_max_filesize'); ?></b><br />
	If needed, increase in <br />"<?php echo tohtml(php_ini_loaded_file()); ?>" <br />and restart server.</p>
	</td>
	</tr>
	<tr>
	<td class="td1 right">NEWLINES and<br />Paragraphs:</td>
	<td class="td1">
	<select name="paragraph_handling">
	<option value="1" selected="selected">ONE NEWLINE: Paragraph ends</option>
	<option value="2">TWO NEWLINEs: Paragraph ends. Single NEWLINE converted to SPACE</option>
	</select>
	<img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
	</td>
	</tr>
	<tr>
	<td class="td1 right">Maximum<br />Sentences<br />per Text:</td>
	<td class="td1"><input type="text" class="notempty posintnumber"  data_info="Maximum Sentences per Text" name="maxsent" value="50" maxlength="3" size="3" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /><br /><span class="smallgray">Values higher than 100 may slow down text display.<br />Very low values (< 5) may result in too many texts.<br />The maximum number of new texts must not exceed <?php echo ($max_input_vars-20); ?>.<br />A single new text will never exceed the length of 65,000 bytes.</span></td>
	</tr>
	<tr>
	<td class="td1 right">Source URI:</td>
	<td class="td1"><input type="text" class="checkurl checkoutsidebmp" data_info="Source URI" name="TxSourceURI" value="" maxlength="1000" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right">Tags:</td>
	<td class="td1">
	<?php echo getTextTags(0); ?>
	</td>
	</tr>
	<tr>
	<td class="td1 right" colspan="2"><input type="button" value="Cancel" onclick="{resetDirty(); location.href='index.php';}" /> &nbsp; | &nbsp; <input type="submit" name="op" value="NEXT STEP: Check the Texts" />
	</td>
	</tr>
	</table>
	</form>
	
	<p class="smallgray">Import of a <b>single text</b>, max. 65,000 bytes long, with optional audio:</p><p><input type="button" value="Standard Text Import" onclick="location.href='edit_texts.php?new=1';" /> </p>


<?php

}

pageend();

?>