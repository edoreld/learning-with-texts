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
PHP Utility Functions
Plus (at end): Database Connect, .. Select, .. Updates
***************************************************************/

// -------------------------------------------------------------

function get_version() {
	global $debug;
	return '1.6.2 (March 10 2018)'  . 
	($debug ? ' <span class="red">DEBUG</span>' : '');
}

// -------------------------------------------------------------

function get_version_number() {
	$r = 'v';
	$v = get_version();
	$pos = strpos($v,' ',0);
	if ($pos === false) my_die ('Wrong version: '. $v);
	$vn = preg_split ("/[.]/", substr($v,0,$pos));
	if (count($vn) < 3) my_die ('Wrong version: '. $v);
	for ($i=0; $i<3; $i++) $r .= substr('000' . $vn[$i],-3);
	return $r;  // 'vxxxyyyzzz' wenn version = x.y.z
}

// -------------------------------------------------------------

function my_die($text) {
	echo '</select></p></div><div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
		'<p><b>Fatal Error:</b> ' . 
		tohtml($text) . 
		"</p></div><hr /><pre>Backtrace:\n\n";
	debug_print_backtrace ();
	echo '</pre><hr />';
	die('</body></html>');
}

// -------------------------------------------------------------

function stripTheSlashesIfNeeded($s) {
	if(get_magic_quotes_gpc())
		return stripslashes($s);
	else
		return $s;
}

// -------------------------------------------------------------

function getPreviousAndNextTextLinks($textid,$url,$onlyann,$add) {
	global $tbpref;
	$currentlang = validateLang(processDBParam("filterlang",'currentlanguage','',0));
	$wh_lang = ($currentlang != '') ? (' and TxLgID=' . $currentlang) : '';

	$currentquery = processSessParam("query","currenttextquery",'',0);
	$wh_query = convert_string_to_sqlsyntax(str_replace("*","%",mb_strtolower($currentquery, 'UTF-8')));
	$wh_query = ($currentquery != '') ? (' and TxTitle like ' . $wh_query) : '';

	$currenttag1 = validateTextTag(processSessParam("tag1","currenttexttag1",'',0),$currentlang);
	$currenttag2 = validateTextTag(processSessParam("tag2","currenttexttag2",'',0),$currentlang);
	$currenttag12 = processSessParam("tag12","currenttexttag12",'',0);
	if ($currenttag1 == '' && $currenttag2 == '')
		$wh_tag = '';
	else {
		if ($currenttag1 != '') {
			if ($currenttag1 == -1)
				$wh_tag1 = "group_concat(TtT2ID) IS NULL";
			else
				$wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'";
		} 
		if ($currenttag2 != '') {
			if ($currenttag2 == -1)
				$wh_tag2 = "group_concat(TtT2ID) IS NULL";
			else
				$wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'";
		} 
		if ($currenttag1 != '' && $currenttag2 == '')	
			$wh_tag = " having (" . $wh_tag1 . ') ';
		elseif ($currenttag2 != '' && $currenttag1 == '')	
			$wh_tag = " having (" . $wh_tag2 . ') ';
		else
			$wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) ';
	}

	$currentsort = processDBParam("sort",'currenttextsort','1',1);
	$sorts = array('TxTitle','TxID desc','TxID');
	$lsorts = count($sorts);
	if ($currentsort < 1) $currentsort = 1;
	if ($currentsort > $lsorts) $currentsort = $lsorts;

	if ($onlyann) 
		$sql = 'select TxID from ((' . $tbpref . 'texts left JOIN ' . $tbpref . 'texttags ON TxID = TtTxID) left join ' . $tbpref . 'tags2 on T2ID = TtT2ID), ' . $tbpref . 'languages where LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 ' . $wh_lang . $wh_query . ' group by TxID ' . $wh_tag . ' order by ' . $sorts[$currentsort-1];
	else
		$sql = 'select TxID from ((' . $tbpref . 'texts left JOIN ' . $tbpref . 'texttags ON TxID = TtTxID) left join ' . $tbpref . 'tags2 on T2ID = TtT2ID), ' . $tbpref . 'languages where LgID = TxLgID ' . $wh_lang . $wh_query . ' group by TxID ' . $wh_tag . ' order by ' . $sorts[$currentsort-1];

	$list = array(0);
	$res = do_mysqli_query($sql);		
	while ($record = mysqli_fetch_assoc($res)) {
		array_push($list, ($record['TxID']+0));
	}
	mysqli_free_result($res);
	array_push($list, 0);
	$listlen = count($list);
	for ($i=1; $i < $listlen-1; $i++) {
		if($list[$i] == $textid) {
			if ($list[$i-1] !== 0) {
				$title = tohtml(getTextTitle($list[$i-1]));
				$prev = '<a href="' . $url . $list[$i-1] . '" target="_top"><img src="icn/navigation-180-button.png" title="Previous Text: ' . $title . '" alt="Previous Text: ' . $title . '" /></a>';
			}
			else
				$prev = '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />';
			if ($list[$i+1] !== 0) {
				$title = tohtml(getTextTitle($list[$i+1]));
				$next = '<a href="' . $url . $list[$i+1] . '" target="_top"><img src="icn/navigation-000-button.png" title="Next Text: ' . $title . '" alt="Next Text: ' . $title . '" /></a>';
			}
			else
				$next = '<img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
			return $add . $prev . ' ' . $next;
		}
	}
	return $add . '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" /> <img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
}

// -------------------------------------------------------------

function get_tags($refresh = 0) {
	global $tbpref;
	if (isset($_SESSION['TAGS'])) {
		if (is_array($_SESSION['TAGS'])) {
			if (isset($_SESSION['TBPREF_TAGS'])) {
				if($_SESSION['TBPREF_TAGS'] == $tbpref . url_base()) {
					if ($refresh == 0) return $_SESSION['TAGS'];
				}
			}
		}
	}
	$tags = array();
	$sql = 'select TgText from ' . $tbpref . 'tags order by TgText';
	$res = do_mysqli_query($sql);		
	while ($record = mysqli_fetch_assoc($res)) {
		$tags[] = $record["TgText"];
	}
	mysqli_free_result($res);
	$_SESSION['TAGS'] = $tags;
	$_SESSION['TBPREF_TAGS'] = $tbpref . url_base();
	return $_SESSION['TAGS'];
}

// -------------------------------------------------------------

function get_texttags($refresh = 0) {
	global $tbpref;
	if (isset($_SESSION['TEXTTAGS'])) {
		if (is_array($_SESSION['TEXTTAGS'])) {
			if (isset($_SESSION['TBPREF_TEXTTAGS'])) {
				if($_SESSION['TBPREF_TEXTTAGS'] == $tbpref . url_base()) {
					if ($refresh == 0) return $_SESSION['TEXTTAGS'];
				}
			}
		}
	}
	$tags = array();
	$sql = 'select T2Text from ' . $tbpref . 'tags2 order by T2Text';
	$res = do_mysqli_query($sql);		
	while ($record = mysqli_fetch_assoc($res)) {
		$tags[] = $record["T2Text"];
	}
	mysqli_free_result($res);
	$_SESSION['TEXTTAGS'] = $tags;
	$_SESSION['TBPREF_TEXTTAGS'] = $tbpref . url_base();
	return $_SESSION['TEXTTAGS'];
}

// -------------------------------------------------------------

function getTextTitle ($textid) {
	global $tbpref;
	$text = get_first_value("select TxTitle as value from " . $tbpref . "texts where TxID=" . $textid);
	if (! isset($text)) $text = "?";
	return $text;
}

// -------------------------------------------------------------

function get_tag_selectoptions($v,$l) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	if ($l == '')
		$sql = "select TgID, TgText from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID group by TgID order by TgText";
	else
		$sql = "select TgID, TgText from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $l . " group by TgID order by TgText";
	$res = do_mysqli_query($sql);		
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["TgText"];
		$cnt++;
		$r .= "<option value=\"" . $record["TgID"] . "\"" . get_selected($v,$record["TgID"]) . ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	if ($cnt > 0) {
		$r .= "<option disabled=\"disabled\">--------</option>";
		$r .= "<option value=\"-1\"" . get_selected($v,-1) . ">UNTAGGED</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_texttag_selectoptions($v,$l) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	if ($l == '')
		$sql = "select T2ID, T2Text from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID group by T2ID order by T2Text";
	else
		$sql = "select T2ID, T2Text from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $l . " group by T2ID order by T2Text";
	$res = do_mysqli_query($sql);		
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["T2Text"];
		$cnt++;
		$r .= "<option value=\"" . $record["T2ID"] . "\"" . get_selected($v,$record["T2ID"]) . ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	if ($cnt > 0) {
		$r .= "<option disabled=\"disabled\">--------</option>";
		$r .= "<option value=\"-1\"" . get_selected($v,-1) . ">UNTAGGED</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_archivedtexttag_selectoptions($v,$l) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	if ($l == '')
		$sql = "select T2ID, T2Text from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID group by T2ID order by T2Text";
	else
		$sql = "select T2ID, T2Text from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $l . " group by T2ID order by T2Text";
	$res = do_mysqli_query($sql);		
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["T2Text"];
		$cnt++;
		$r .= "<option value=\"" . $record["T2ID"] . "\"" . get_selected($v,$record["T2ID"]) . ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	if ($cnt > 0) {
		$r .= "<option disabled=\"disabled\">--------</option>";
		$r .= "<option value=\"-1\"" . get_selected($v,-1) . ">UNTAGGED</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function saveWordTags($wid) {
	global $tbpref;
	runsql("DELETE from " . $tbpref . "wordtags WHERE WtWoID =" . $wid,'');
	if (isset($_REQUEST['TermTags'])) {
		if (is_array($_REQUEST['TermTags'])) {
			if (isset($_REQUEST['TermTags']['TagList'])) {
				if (is_array($_REQUEST['TermTags']['TagList'])) {
					$cnt = count($_REQUEST['TermTags']['TagList']);
					if ($cnt > 0 ) {
						for ($i=0; $i<$cnt; $i++) {
							$tag = $_REQUEST['TermTags']['TagList'][$i];
							if(! in_array($tag, $_SESSION['TAGS'])) {
								runsql('insert into ' . $tbpref . 'tags (TgText) values(' . 
								convert_string_to_sqlsyntax($tag) . ')', "");
							}
							runsql('insert into ' . $tbpref . 'wordtags (WtWoID, WtTgID) select ' . $wid . ', TgID from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($tag), "");
						}
						get_tags($refresh = 1);  // refresh tags cache
					}
				}
			}
		}
	}
}

// -------------------------------------------------------------

function saveTextTags($tid) {
	global $tbpref;
	runsql("DELETE from " . $tbpref . "texttags WHERE TtTxID =" . $tid,'');
	if (isset($_REQUEST['TextTags'])) {
		if (is_array($_REQUEST['TextTags'])) {
			if (isset($_REQUEST['TextTags']['TagList'])) {
				if (is_array($_REQUEST['TextTags']['TagList'])) {
					$cnt = count($_REQUEST['TextTags']['TagList']);
					if ($cnt > 0 ) {
						for ($i=0; $i<$cnt; $i++) {
							$tag = $_REQUEST['TextTags']['TagList'][$i];
							if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
								runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . 
								convert_string_to_sqlsyntax($tag) . ')', "");
							}
							runsql('insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID) select ' . $tid . ', T2ID from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($tag), "");
						}
						get_texttags($refresh = 1);  // refresh tags cache
					}
				}
			}
		}
	}
}

// -------------------------------------------------------------

function saveArchivedTextTags($tid) {
	global $tbpref;
	runsql("DELETE from " . $tbpref . "archtexttags WHERE AgAtID =" . $tid,'');
	if (isset($_REQUEST['TextTags'])) {
		if (is_array($_REQUEST['TextTags'])) {
			if (isset($_REQUEST['TextTags']['TagList'])) {
				if (is_array($_REQUEST['TextTags']['TagList'])) {
					$cnt = count($_REQUEST['TextTags']['TagList']);
					if ($cnt > 0 ) {
						for ($i=0; $i<$cnt; $i++) {
							$tag = $_REQUEST['TextTags']['TagList'][$i];
							if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
								runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . 
								convert_string_to_sqlsyntax($tag) . ')', "");
							}
							runsql('insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) select ' . $tid . ', T2ID from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($tag), "");
						}
						get_texttags($refresh = 1);  // refresh tags cache
					}
				}
			}
		}
	}
}

// -------------------------------------------------------------

function getWordTags($wid) {
	global $tbpref;
	$r = '<ul id="termtags">';
	if ($wid > 0) {
		$sql = 'select TgText from ' . $tbpref . 'wordtags, ' . $tbpref . 'tags where TgID = WtTgID and WtWoID = ' . $wid . ' order by TgText';
		$res = do_mysqli_query($sql);		
		while ($record = mysqli_fetch_assoc($res)) {
			$r .= '<li>' . tohtml($record["TgText"]) . '</li>';
		}
		mysqli_free_result($res);
	}
	$r .= '</ul>';
	return $r;
}

// -------------------------------------------------------------

function getTextTags($tid) {
	global $tbpref;
	$r = '<ul id="texttags">';
	if ($tid > 0) {
		$sql = 'select T2Text from ' . $tbpref . 'texttags, ' . $tbpref . 'tags2 where T2ID = TtT2ID and TtTxID = ' . $tid . ' order by T2Text';
		$res = do_mysqli_query($sql);		
		while ($record = mysqli_fetch_assoc($res)) {
			$r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
		}
		mysqli_free_result($res);
	}
	$r .= '</ul>';
	return $r;
}

// -------------------------------------------------------------

function getArchivedTextTags($tid) {
	global $tbpref;
	$r = '<ul id="texttags">';
	if ($tid > 0) {
		$sql = 'select T2Text from ' . $tbpref . 'archtexttags, ' . $tbpref . 'tags2 where T2ID = AgT2ID and AgAtID = ' . $tid . ' order by T2Text';
		$res = do_mysqli_query($sql);
		while ($record = mysqli_fetch_assoc($res)) {
			$r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
		}
		mysqli_free_result($res);
	}
	$r .= '</ul>';
	return $r;
}

// -------------------------------------------------------------

function addtaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) {
		runsql('insert into ' . $tbpref . 'tags (TgText) values(' . convert_string_to_sqlsyntax($item) . ')', "");
		$tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
	}
	$sql = 'select WoID from ' . $tbpref . 'words where WoID in ' . $list;
	$res = do_mysqli_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt += runsql('insert ignore into ' . $tbpref . 'wordtags (WtWoID, WtTgID) values(' . $record['WoID'] . ', ' . $tagid . ')', "");
	}
	mysqli_free_result($res);
	get_tags($refresh = 1);
	return "Tag added in $cnt Terms";
}

// -------------------------------------------------------------

function addarchtexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) {
		runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . convert_string_to_sqlsyntax($item) . ')', "");
		$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	}
	$sql = 'select AtID from ' . $tbpref . 'archivedtexts where AtID in ' . $list;
	$res = do_mysqli_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt += runsql('insert ignore into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) values(' . $record['AtID'] . ', ' . $tagid . ')', "");
	}
	mysqli_free_result($res);
	get_texttags($refresh = 1);
	return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function addtexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) {
		runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . convert_string_to_sqlsyntax($item) . ')', "");
		$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	}
	$sql = 'select TxID from ' . $tbpref . 'texts where TxID in ' . $list;
	$res = do_mysqli_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt += runsql('insert ignore into ' . $tbpref . 'texttags (TtTxID, TtT2ID) values(' . $record['TxID'] . ', ' . $tagid . ')', "");
	}
	mysqli_free_result($res);
	get_texttags($refresh = 1);
	return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function removetaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) return "Tag " . $item . " not found";
	$sql = 'select WoID from ' . $tbpref . 'words where WoID in ' . $list;
	$res = do_mysqli_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt++;
		runsql('delete from ' . $tbpref . 'wordtags where WtWoID = ' . $record['WoID'] . ' and WtTgID = ' . $tagid, "");
	}
	mysqli_free_result($res);
	return "Tag removed in $cnt Terms";
}

// -------------------------------------------------------------

function removearchtexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) return "Tag " . $item . " not found";
	$sql = 'select AtID from ' . $tbpref . 'archivedtexts where AtID in ' . $list;
	$res = do_mysqli_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt++;
		runsql('delete from ' . $tbpref . 'archtexttags where AgAtID = ' . $record['AtID'] . ' and AgT2ID = ' . $tagid, "");
	}
	mysqli_free_result($res);
	return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function removetexttaglist ($item, $list) {
	global $tbpref;
	$tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
	if (! isset($tagid)) return "Tag " . $item . " not found";
	$sql = 'select TxID from ' . $tbpref . 'texts where TxID in ' . $list;
	$res = do_mysqli_query($sql);
	$cnt = 0;
	while ($record = mysqli_fetch_assoc($res)) {
		$cnt++;
		runsql('delete from ' . $tbpref . 'texttags where TtTxID = ' . $record['TxID'] . ' and TtT2ID = ' . $tagid, "");
	}
	mysqli_free_result($res);
	return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function framesetheader($title) {
	@header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	@header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	@header( 'Pragma: no-cache' );
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />

<!-- ***********************************************************
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
************************************************************ -->

	<title>LWT :: <?php echo tohtml($title); ?></title>
</head>
<?php
}

// -------------------------------------------------------------

function pagestart_nobody($titletext, $addcss='') {
	global $debug;
	global $tbpref;
	@header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	@header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	@header( 'Pragma: no-cache' );
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	
<!-- ***********************************************************
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
************************************************************ -->

	<meta name="viewport" content="width=900" />
	<link rel="apple-touch-icon" href="img/apple-touch-icon-57x57.png" />
	<link rel="apple-touch-icon" sizes="72x72" href="img/apple-touch-icon-72x72.png" />
	<link rel="apple-touch-icon" sizes="114x114" href="img/apple-touch-icon-114x114.png" />
	<link rel="apple-touch-startup-image" href="img/apple-touch-startup.png">
	<meta name="apple-mobile-web-app-capable" content="yes" />
	
	<link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
	<link rel="stylesheet" type="text/css" href="css/jquery.tagit.css">
	<link rel="stylesheet" type="text/css" href="css/styles.css">
	<style type="text/css">
	<?php echo $addcss . "\n"; ?>
	</style>
	
	<script type="text/javascript" src="js/jquery.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jquery.scrollTo.min.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jquery-ui.min.js"  charset="utf-8"></script>
	<script type="text/javascript" src="js/tag-it.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jquery.jeditable.mini.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/sorttable/sorttable.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/countuptimer.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/overlib/overlib_mini.js" charset="utf-8"></script>
	<!-- URLBASE : "<?php echo tohtml(url_base()); ?>" -->
	<!-- TBPREF  : "<?php echo tohtml($tbpref); ?>" -->
	<script type="text/javascript">
	//<![CDATA[
	<?php echo "var STATUSES = " . json_encode(get_statuses()) . ";\n"; ?>
	<?php echo "var TAGS = " . json_encode(get_tags()) . ";\n"; ?>
	<?php echo "var TEXTTAGS = " . json_encode(get_texttags()) . ";\n"; ?>
	//]]>
	</script>
	<script type="text/javascript" src="js/pgm.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/jq_pgm.js" charset="utf-8"></script>
	
	<title>LWT :: <?php echo $titletext; ?></title>
</head>
<body>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<?php
	if ($debug) showRequest();
} 

// -------------------------------------------------------------

function pagestart($titletext,$close) {
	global $debug;
	pagestart_nobody($titletext);
	echo '<h4>';
	if ($close) echo '<a href="index.php" target="_top">';
	echo_lwt_logo();
	echo "LWT";
	if ($close) {
		echo '</a>&nbsp; | &nbsp;';
		quickMenu();
	}
	echo '</h4><h3>' . $titletext . ($debug ? ' <span class="red">DEBUG</span>' : '') . '</h3>';
	echo "<p>&nbsp;</p>";
} 

// -------------------------------------------------------------

function url_base() {
	$url = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
	$r = $url["scheme"] . "://" . $url["host"];
	if(isset($url["port"])) $r .= ":" . $url["port"];
	if(isset($url["path"])) {
		$b = basename($url["path"]);
		if (substr($b,-4) == ".php" || substr($b,-4) == ".htm" || substr($b,-5) == ".html") 
			$r .= dirname($url["path"]);
		else
			$r .= $url["path"];
	}
	if(substr($r,-1) !== "/") $r .= "/";
	return $r;
}

// -------------------------------------------------------------

function pageend() {
	global $debug, $dspltime;
	if ($debug) showRequest();
	if ($dspltime) echo "\n<p class=\"smallgray2\">" . 
		round(get_execution_time(),5) . " secs</p>\n";
?></body></html><?php
} 

// -------------------------------------------------------------

function echo_lwt_logo() {
	global $tbpref;
	$pref = substr($tbpref,0,-1);
	if($pref == '') $pref = 'Default Table Set';
	echo '<img class="lwtlogo" src="img/lwt_icon.png"  title="LWT - Current Table Set: ' . tohtml($pref) . '" alt="LWT - Current Table Set: ' . tohtml($pref) . '" />';
}

// -------------------------------------------------------------

function get_execution_time()
{
    static $microtime_start = null;
    if($microtime_start === null)
    {
        $microtime_start = microtime(true);
        return 0.0; 
    }    
    return microtime(true) - $microtime_start; 
}

// -------------------------------------------------------------

function getprefixes() {
	$prefix = array();
	$res = do_mysqli_query(str_replace('_',"\\_","SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull('%_settings')));
	while ($row = mysqli_fetch_row($res)) 
		$prefix[] = substr($row[0], 0, -9);
	mysqli_free_result($res);
	return $prefix;
}

// -------------------------------------------------------------

function selectmediapath($f) {
	$exists = file_exists('media');
	if ($exists) {
		if (is_dir ('media')) $msg = '';
		else $msg = '<br />[Error: ".../' . basename(getcwd()) . '/media" exists, but it is not a directory.]';
	} else {
		$msg = '<br />[Directory ".../' . basename(getcwd()) . '/media" does not yet exist.]';
	}
	$r = '<br /> or choose a file in ".../' . basename(getcwd()) . '/media" (only mp3, ogg, wav files shown): ' . $msg;
	if ($msg == '') {
		$r .= '<br /><select name="Dir" onchange="{val=this.form.Dir.options[this.form.Dir.selectedIndex].value; if (val != \'\') this.form.' . $f . '.value = val; this.form.Dir.value=\'\';}">';
		$r .= '<option value="">[Choose...]</option>';
		$r .= selectmediapathoptions('media');
		$r .= '</select> ';
	}
	$r .= ' &nbsp; &nbsp; <span class="click" onclick="do_ajax_update_media_select();"><img src="icn/arrow-circle-135.png" title="Refresh Media Selection" alt="Refresh Media Selection" /> Refresh</span>';
	return $r;
}

// -------------------------------------------------------------

function selectmediapathoptions($dir) {
	$is_windows = ("WIN" == strtoupper(substr(PHP_OS, 0, 3)));
	$mediadir = scandir($dir);
	$r = '<option disabled="disabled">-- Directory: ' . tohtml($dir) . ' --</option>';
	foreach ($mediadir as $entry) {
		if ($is_windows) $entry = mb_convert_encoding ($entry,'UTF-8','ISO-8859-1');
		if (substr($entry,0,1) != '.') {
			if (! is_dir($dir . '/' . $entry)) {
				$ex = substr($entry,-4);
				if ( (strcasecmp($ex, '.mp3') == 0) ||
					(strcasecmp($ex, '.ogg') == 0) ||
					(strcasecmp($ex, '.wav') == 0))
					$r .= '<option value="' . tohtml($dir . '/' . $entry) . '">' . tohtml($dir . '/' . $entry) . '</option>';
			}
		}
	}
	foreach ($mediadir as $entry) {
		if (substr($entry,0,1) != '.') {
			if (is_dir($dir . '/' . $entry)) $r .= selectmediapathoptions($dir . '/' . $entry);
		}
	}
	return $r;
}

// -------------------------------------------------------------

function get_seconds_selectoptions($v) {
	if ( ! isset($v) ) $v = 5;
	$r = '';
	for ($i=1; $i <= 10; $i++) {
		$r .= "<option value=\"" . $i . "\"" . get_selected($v,$i);
		$r .= ">" . $i . " sec</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_playbackrate_selectoptions($v) {
	if ( ! isset($v) ) $v = '10';
	$r = '';
	for ($i=5; $i <= 15; $i++) {
		$text = ($i<10 ? (' 0.' . $i . ' x ') : (' 1.' . ($i-10) . ' x ') ); 
		$r .= "<option value=\"" . $i . "\"" . get_selected($v,$i);
		$r .= ">&nbsp;" . $text . "&nbsp;</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function quickMenu() {
?><select id="quickmenu" onchange="{var qm = document.getElementById('quickmenu'); var val=qm.options[qm.selectedIndex].value; qm.selectedIndex=0; if (val != '') { if (val == 'INFO') {top.location.href='info.htm';} else {top.location.href = val + '.php';}}}">
<option value="" selected="selected">[Menu]</option>
<option value="index">Home</option>
<option value="edit_texts">Texts</option>
<option value="edit_archivedtexts">Text Archive</option>
<option value="edit_texttags">Text Tags</option>
<option value="edit_languages">Languages</option>
<option value="edit_words">Terms</option>
<option value="edit_tags">Term Tags</option>
<option value="statistics">Statistics</option>
<option value="check_text">Text Check</option>
<option value="long_text_import">Long Text Import</option>
<option value="upload_words">Term Import</option>
<option value="backup_restore">Backup/Restore</option>
<option value="settings">Settings</option>
<option value="INFO">Help</option>
</select><?php
}

// -------------------------------------------------------------

function error_message_with_hide($msg,$noback) {
	if (trim($msg) == '') return '';
	if (substr($msg,0,5) == "Error" )
		return '<p class="red">*** ' . tohtml($msg) . ' ***' . 
			($noback ? 
			'' : 
			'<br /><input type="button" value="&lt;&lt; Go back and correct &lt;&lt;" onclick="history.back();" />' ) . 
			'</p>';
	else
		return '<p id="hide3" class="msgblue">+++ ' . tohtml($msg) . ' +++</p>';
}

// -------------------------------------------------------------

function errorbutton($msg) {
	if (substr($msg,0,5) == "Error" )
		return '<input type="button" value="&lt;&lt; Back" onclick="history.back();" />';
	else
		return '';
} 

// -------------------------------------------------------------

function optimizedb() {
	global $tbpref;
	adjust_autoincr('archivedtexts','AtID');
	adjust_autoincr('languages','LgID');
	adjust_autoincr('sentences','SeID');
	adjust_autoincr('textitems','TiID');
	adjust_autoincr('texts','TxID');
	adjust_autoincr('words','WoID');
	adjust_autoincr('tags','TgID');
	adjust_autoincr('tags2','T2ID');
	$dummy = runsql('OPTIMIZE TABLE ' . $tbpref . 'archivedtexts,' . $tbpref . 'languages,' . $tbpref . 'sentences,' . $tbpref . 'textitems,' . $tbpref . 'texts,' . $tbpref . 'words,' . $tbpref . 'settings,' . $tbpref . 'tags,' . $tbpref . 'wordtags,' . $tbpref . 'tags2,' . $tbpref . 'texttags,' . $tbpref . 'archtexttags, _lwtgeneral', '');
}

// -------------------------------------------------------------

function remove_soft_hyphens($str) {
	return str_replace('­', '', $str);  // first '..' contains Softhyphen 0xC2 0xAD
}

// -------------------------------------------------------------

function limitlength($s, $l) {
	if (mb_strlen ($s, 'UTF-8') <= $l) return $s;
	return mb_substr($s, 0, $l, 'UTF-8');
}

// -------------------------------------------------------------

function adjust_autoincr($table,$key) {
	global $tbpref;
	$val = get_first_value('select max(' . $key .')+1 as value from ' . $tbpref .  $table);
	if (! isset($val)) $val = 1;
	$sql = 'alter table ' . $tbpref . $table . ' AUTO_INCREMENT = ' . $val;
	$res = do_mysqli_query($sql);
}

// -------------------------------------------------------------

function replace_supp_unicode_planes_char($s) {
	return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $s); 
	/* U+2588 = UTF8: E2 96 88 = FULL BLOCK = ⬛︎  */ 
}

// -------------------------------------------------------------

function prepare_textdata($s) {
	return str_replace("\r\n","\n", stripTheSlashesIfNeeded($s));
}

// -------------------------------------------------------------

function prepare_textdata_js($s) {
	$s = convert_string_to_sqlsyntax($s);
	if($s == "NULL") return "''";
	return str_replace("''", "\\'", $s);
}

// -------------------------------------------------------------

function tohtml($s) {
	if (isset($s)) return htmlspecialchars($s, ENT_COMPAT, "UTF-8");
	else return '';
}

// -------------------------------------------------------------

function makeCounterWithTotal ($max, $num) {
	if ($max == 1) return '';
	if ($max < 10) return $num . "/" . $max;
	return substr(
		str_repeat("0", strlen($max)) . $num,
		-strlen($max))  . 
		"/" . $max;
}

// -------------------------------------------------------------

function encodeURI($url) {
	$reserved = array(
		'%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', 
		'%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
	);
	$unescaped = array(
		'%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
		'%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
	);
	$score = array(
		'%23'=>'#'
	);
	return strtr(rawurlencode($url), array_merge($reserved,$unescaped,$score));
}
 
// -------------------------------------------------------------

function showRequest() {
	$olderr = error_reporting(0);
	echo "<pre>** DEBUGGING **********************************\n";
	echo '$GLOBALS...'; print_r($GLOBALS);
	echo 'get_version_number()...'; echo get_version_number() . "\n";
	echo 'get_magic_quotes_gpc()...'; echo get_magic_quotes_gpc() . "\n";
	echo "********************************** DEBUGGING **</pre>";
	error_reporting($olderr);
}

// -------------------------------------------------------------

function convert_string_to_sqlsyntax($data) {
	$result = "NULL";
	$data = trim(prepare_textdata($data));
	if($data != "") $result = "'" . mysqli_real_escape_string($GLOBALS['DBCONNECTION'], $data) . "'";
	return $result;
}

// -------------------------------------------------------------

function convert_string_to_sqlsyntax_nonull($data) {
	$data = trim(prepare_textdata($data));
	return  "'" . mysqli_real_escape_string($GLOBALS['DBCONNECTION'], $data) . "'";
}

// -------------------------------------------------------------

function convert_string_to_sqlsyntax_notrim_nonull($data) {
	return "'" . mysqli_real_escape_string($GLOBALS['DBCONNECTION'], prepare_textdata($data)) . "'";
}

// -------------------------------------------------------------

function remove_spaces($s,$remove) {
	if ($remove) 
		return preg_replace('/\s{1,}/u', '', $s);  // '' enthält &#x200B;
	else
		return $s;
}

// -------------------------------------------------------------

function getreq($s) {
	if ( isset($_REQUEST[$s]) ) {
		return trim($_REQUEST[$s]);
	} else
		return '';
}

// -------------------------------------------------------------

function getsess($s) {
	if ( isset($_SESSION[$s]) ) {
		return trim($_SESSION[$s]);
	} else
		return '';
}

// -------------------------------------------------------------

function get_sepas() {
	static $sepa;
	if (!$sepa) {
		$sepa = preg_quote(getSettingWithDefault('set-term-translation-delimiters'),'/');
	}
	return $sepa;
}

// -------------------------------------------------------------

function get_first_sepa() {
	static $sepa;
	if (!$sepa) {
		$sepa = mb_substr(getSettingWithDefault('set-term-translation-delimiters'),
		0,1,'UTF-8');
	}
	return $sepa;
}

// -------------------------------------------------------------

function getSettingZeroOrOne($key, $dft) {
	$r = getSetting($key);
	$r = ($r == '' ? $dft : ((((int) $r) !== 0) ? 1 : 0));
	return $r;
}

// -------------------------------------------------------------

function getSetting($key) {
	global $tbpref;
	$val = get_first_value('select StValue as value from ' . $tbpref . 'settings where StKey = ' . convert_string_to_sqlsyntax($key));
	if ( isset($val) ) {
		$val = trim($val);
		if ($key == 'currentlanguage' ) $val = validateLang($val);
		if ($key == 'currenttext' ) $val = validateText($val);
		return $val;
	}
	else return '';
}

// -------------------------------------------------------------

function getSettingWithDefault($key) {
	global $tbpref;
	$dft = get_setting_data();
	$val = get_first_value('select StValue as value from ' . $tbpref . 'settings where StKey = ' . convert_string_to_sqlsyntax($key));
	if ( isset($val) && $val != '' ) return trim($val);
	else {
		if (array_key_exists($key,$dft)) return $dft[$key]['dft'];
		else return '';
	}
}

// -------------------------------------------------------------

function get_mobile_display_mode_selectoptions($v) {
	if ( ! isset($v) ) $v = "0";
	$r  = "<option value=\"0\"" . get_selected($v,"0");
	$r .= ">Auto</option>";
	$r .= "<option value=\"1\"" . get_selected($v,"1");
	$r .= ">Force Non-Mobile</option>";
	$r .= "<option value=\"2\"" . get_selected($v,"2");
	$r .= ">Force Mobile</option>";
	return $r;
}

// -------------------------------------------------------------

function get_sentence_count_selectoptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Just ONE</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">TWO (+previous)</option>";
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">THREE (+previous,+next)</option>";
	return $r;
}

// -------------------------------------------------------------

function saveSetting($k,$v) {
	global $tbpref;
	$dft = get_setting_data();
	if (! isset($v)) $v ='';
	$v = stripTheSlashesIfNeeded($v);
	runsql('delete from ' . $tbpref . 'settings where StKey = ' . convert_string_to_sqlsyntax($k), '');
	if ($v !== '') {
		if (array_key_exists($k,$dft)) {
			if ($dft[$k]['num']) {
				$v = (int) $v;
				if ( $v < $dft[$k]['min'] ) $v = $dft[$k]['dft'];
				if ( $v > $dft[$k]['max'] ) $v = $dft[$k]['dft'];
			}
		}
		$dum = runsql('insert into ' . $tbpref . 'settings (StKey, StValue) values(' .
			convert_string_to_sqlsyntax($k) . ', ' . 
			convert_string_to_sqlsyntax($v) . ')', '');
	}
}

// -------------------------------------------------------------

function processSessParam($reqkey,$sesskey,$default,$isnum) {
	$result = '';
	if(isset($_REQUEST[$reqkey])) {
		$reqdata = stripTheSlashesIfNeeded(trim($_REQUEST[$reqkey]));
		$_SESSION[$sesskey] = $reqdata;
		$result = $reqdata;
	}
	elseif(isset($_SESSION[$sesskey])) {
		$result = $_SESSION[$sesskey];
	}
	else {
		$result = $default;
	}
	if($isnum) $result = (int)$result;
	return $result;
}

// -------------------------------------------------------------

function processDBParam($reqkey,$dbkey,$default,$isnum) {
	$result = '';
	$dbdata = getSetting($dbkey);
	if(isset($_REQUEST[$reqkey])) {
		$reqdata = stripTheSlashesIfNeeded(trim($_REQUEST[$reqkey]));
		saveSetting($dbkey,$reqdata);
		$result = $reqdata;
	}
	elseif($dbdata != '') {
		$result = $dbdata;
	}
	else {
		$result = $default;
	}
	if($isnum) $result = (int)$result;
	return $result;
}

// -------------------------------------------------------------

function validateLang($currentlang) {
	global $tbpref;
	if ($currentlang != '') {
		if (
			get_first_value(
				'select count(LgID) as value from ' . $tbpref . 'languages where LgID=' . 
				((int)$currentlang) 
			) == 0
		)  $currentlang = ''; 
	}
	return $currentlang;
}

// -------------------------------------------------------------

function validateText($currenttext) {
	global $tbpref;
	if ($currenttext != '') {
		if (
			get_first_value(
				'select count(TxID) as value from ' . $tbpref . 'texts where TxID=' . 
				((int)$currenttext) 
			) == 0
		)  $currenttext = ''; 
	}
	return $currenttext;
}

// -------------------------------------------------------------

function validateTag($currenttag,$currentlang) {
	global $tbpref;
	if ($currenttag != '' && $currenttag != -1) {
		if ($currentlang == '')
			$sql = "select (" . $currenttag . " in (select TgID from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID group by TgID order by TgText)) as value";
		else
			$sql = "select (" . $currenttag . " in (select TgID from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $currentlang . " group by TgID order by TgText)) as value";
		$r = get_first_value($sql);
		if ( $r == 0 ) $currenttag = ''; 
	}
	return $currenttag;
}

// -------------------------------------------------------------

function validateArchTextTag($currenttag,$currentlang) {
	global $tbpref;
	if ($currenttag != '' && $currenttag != -1) {
		if ($currentlang == '')
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID group by T2ID order by T2Text)) as value";
		else
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $currentlang . " group by T2ID order by T2Text)) as value";
		$r = get_first_value($sql);
		if ( $r == 0 ) $currenttag = ''; 
	}
	return $currenttag;
}

// -------------------------------------------------------------

function validateTextTag($currenttag,$currentlang) {
	global $tbpref;
	if ($currenttag != '' && $currenttag != -1) {
		if ($currentlang == '')
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID group by T2ID order by T2Text)) as value";
		else
			$sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $currentlang . " group by T2ID order by T2Text)) as value";
		$r = get_first_value($sql);
		if ( $r == 0 ) $currenttag = ''; 
	}
	return $currenttag;
}

// -------------------------------------------------------------

function getWordTagList($wid, $before=' ', $brack=1, $tohtml=1) {
	global $tbpref;
	$r = get_first_value("select ifnull(" . ($brack ? "concat('['," : "") . "group_concat(distinct TgText order by TgText separator ', ')" . ($brack ? ",']')" : "") . ",'') as value from ((" . $tbpref . "words left join " . $tbpref . "wordtags on WoID = WtWoID) left join " . $tbpref . "tags on TgID = WtTgID) where WoID = " . $wid);
	if ($r != '') $r = $before . $r;
	if ($tohtml) $r = tohtml($r);
	return $r;
}

// -------------------------------------------------------------

function get_last_key() {
	return get_first_value('SELECT LAST_INSERT_ID() as value');		
}

// -------------------------------------------------------------

function get_checked($value) {
	if (! isset($value)) return '';
	if ((int)$value != 0) return ' checked="checked" ';
	return '';
}

// -------------------------------------------------------------

function get_selected($value,$selval) {
	if (! isset($value)) return '';
	if ($value == $selval) return ' selected="selected" ';
	return '';
}

// -------------------------------------------------------------

function make_status_controls_test_table($score, $status, $wordid) {
	if ( $score < 0 ) 
		$scoret = '<span class="red2">' . get_status_abbr($status) . '</span>';
	else
		$scoret = get_status_abbr($status);
		
	if ( $status <= 5 || $status == 98 ) 
		$plus = '<img src="icn/plus.png" class="click" title="+" alt="+" onclick="changeTableTestStatus(' . $wordid .',true);" />';
	else
		$plus = '<img src="icn/placeholder.png" title="" alt="" />';
	if ( $status >= 1 ) 
		$minus = '<img src="icn/minus.png" class="click" title="-" alt="-" onclick="changeTableTestStatus(' . $wordid .',false);" />';
	else
		$minus = '<img src="icn/placeholder.png" title="" alt="" />';
	return ($status == 98 ? '' : $minus . ' ') . $scoret . ($status == 99 ? '' : ' ' . $plus);
}

// -------------------------------------------------------------

function get_languages_selectoptions($v,$dt) {
	global $tbpref;
	$sql = "select LgID, LgName from " . $tbpref . "languages order by LgName";
	$res = do_mysqli_query($sql);
	if ( ! isset($v) || trim($v) == '' ) {
		$r = "<option value=\"\" selected=\"selected\">" . $dt . "</option>";
	} else {
		$r = "<option value=\"\">" . $dt . "</option>";
	}
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["LgName"];
		if ( strlen($d) > 30 ) $d = substr($d,0,30) . "...";
		$r .= "<option value=\"" . $record["LgID"] . "\" " . get_selected($v,$record["LgID"]);
		$r .= ">" . tohtml($d) . "</option>";
	}
	mysqli_free_result($res);
	return $r;
}

// -------------------------------------------------------------

function get_languagessize_selectoptions($v) {
	if ( ! isset($v) ) $v = 100;
	$r = "<option value=\"100\"" . get_selected($v,100);
	$r .= ">100 %</option>";
	$r .= "<option value=\"150\"" . get_selected($v,150);
	$r .= ">150 %</option>";
	$r .= "<option value=\"200\"" . get_selected($v,200);
	$r .= ">200 %</option>";
	$r .= "<option value=\"250\"" . get_selected($v,250);
	$r .= ">250 %</option>";
	return $r;
}

// -------------------------------------------------------------

function get_wordstatus_radiooptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r = "";
	$statuses = get_statuses();
	foreach ($statuses as $n => $status) {
		$r .= '<span class="status' . $n . '" title="' . tohtml($status["name"]) . '">';
		$r .= '&nbsp;<input type="radio" name="WoStatus" value="' . $n . '"';
		if ($v == $n) $r .= ' checked="checked"';
		$r .= ' />' . tohtml($status["abbr"]) . "&nbsp;</span> ";
	}
	return $r;
}

// -------------------------------------------------------------

function get_wordstatus_selectoptions($v, $all, $not9899, $off=true) {
	if ( ! isset($v) ) {
		if ( $all ) $v = "";
		else $v = 1;
	}
	$r = "";
	if ($all && $off) {
		$r .= "<option value=\"\"" . get_selected($v,'');
		$r .= ">[Filter off]</option>";
	}
	$statuses = get_statuses();
	foreach ($statuses as $n => $status) {
		if ($not9899 && ($n == 98 || $n == 99)) continue;
		$r .= "<option value =\"" . $n . "\"" . get_selected($v,$n);
		$r .= ">" . tohtml($status['name']) . " [" . 
		tohtml($status['abbr']) . "]</option>";
	}
	if ($all) {
		$r .= '<option disabled="disabled">--------</option>';
		$status_1_name = tohtml($statuses[1]["name"]);
		$status_1_abbr = tohtml($statuses[1]["abbr"]);
		$r .= "<option value=\"12\"" . get_selected($v,12);
		$r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
		tohtml($statuses[2]["abbr"]) . "]</option>";
		$r .= "<option value=\"13\"" . get_selected($v,13);
		$r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
		tohtml($statuses[3]["abbr"]) . "]</option>";
		$r .= "<option value=\"14\"" . get_selected($v,14);
		$r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
		tohtml($statuses[4]["abbr"]) . "]</option>";
		$r .= "<option value=\"15\"" . get_selected($v,15);
		$r .= ">Learning/-ed [" . $status_1_abbr . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$status_2_name = tohtml($statuses[2]["name"]);
		$status_2_abbr = tohtml($statuses[2]["abbr"]);
		$r .= "<option value=\"23\"" . get_selected($v,23);
		$r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
		tohtml($statuses[3]["abbr"]) . "]</option>";
		$r .= "<option value=\"24\"" . get_selected($v,24);
		$r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
		tohtml($statuses[4]["abbr"]) . "]</option>";
		$r .= "<option value=\"25\"" . get_selected($v,25);
		$r .= ">Learning/-ed [" . $status_2_abbr . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$status_3_name = tohtml($statuses[3]["name"]);
		$status_3_abbr = tohtml($statuses[3]["abbr"]);
		$r .= "<option value=\"34\"" . get_selected($v,34);
		$r .= ">" . $status_3_name . " [" . $status_3_abbr . ".." . 
		tohtml($statuses[4]["abbr"]) . "]</option>";
		$r .= "<option value=\"35\"" . get_selected($v,35);
		$r .= ">Learning/-ed [" . $status_3_abbr . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$r .= "<option value=\"45\"" . get_selected($v,45);
		$r .= ">Learning/-ed [" .  tohtml($statuses[4]["abbr"]) . ".." . 
		tohtml($statuses[5]["abbr"]) . "]</option>";
		$r .= '<option disabled="disabled">--------</option>';
		$r .= "<option value=\"599\"" . get_selected($v,599);
		$r .= ">All known [" . tohtml($statuses[5]["abbr"]) . "+" . 
		tohtml($statuses[99]["abbr"]) . "]</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_paging_selectoptions($currentpage, $pages) {
	$r = "";
	for ($i=1; $i<=$pages; $i++) {
		$r .= "<option value=\"" . $i . "\"" . get_selected($i, $currentpage);
		$r .= ">$i</option>";
	}
	return $r;
}

// -------------------------------------------------------------

function get_wordssort_selectoptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Term A-Z</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">Translation A-Z</option>";
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">Newest first</option>";
	$r .= "<option value=\"7\"" . get_selected($v,7);
	$r .= ">Oldest first</option>";
	$r .= "<option value=\"4\"" . get_selected($v,4);
	$r .= ">Status</option>";
	$r .= "<option value=\"5\"" . get_selected($v,5);
	$r .= ">Score Value (%)</option>";
	$r .= "<option value=\"6\"" . get_selected($v,6);
	$r .= ">Word Count Active Texts</option>";
	return $r;
}

// -------------------------------------------------------------

function get_tagsort_selectoptions($v) {
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Tag Text A-Z</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">Tag Comment A-Z</option>";
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">Newest first</option>";
	$r .= "<option value=\"4\"" . get_selected($v,4);
	$r .= ">Oldest first</option>";
	return $r;
}

// -------------------------------------------------------------

function get_textssort_selectoptions($v) { 
	if ( ! isset($v) ) $v = 1;
	$r  = "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Title A-Z</option>";
	$r .= "<option value=\"2\"" . get_selected($v,2);
	$r .= ">Newest first</option>"; 
	$r .= "<option value=\"3\"" . get_selected($v,3);
	$r .= ">Oldest first</option>"; 
	return $r;
}

// -------------------------------------------------------------

function get_yesno_selectoptions($v) {
	if ( ! isset($v) ) $v = 0;
	$r  = "<option value=\"0\"" . get_selected($v,0);
	$r .= ">No</option>";
	$r .= "<option value=\"1\"" . get_selected($v,1);
	$r .= ">Yes</option>";
	return $r;
}

// -------------------------------------------------------------

function get_andor_selectoptions($v) {
	if ( ! isset($v) ) $v = 0;
	$r  = "<option value=\"0\"" . get_selected($v,0);
	$r .= ">... OR ...</option>";
	$r .= "<option value=\"1\"" . get_selected($v,1);
	$r .= ">... AND ...</option>";
	return $r;
}

// -------------------------------------------------------------

function get_set_status_option($n, $suffix = "") {
	return "<option value=\"s" . $n . $suffix . "\">Set Status to " .
		tohtml(get_status_name($n)) . " [" . tohtml(get_status_abbr($n)) .
		"]</option>";
}

// -------------------------------------------------------------

function get_status_name($n) {
	$statuses = get_statuses();
	return $statuses[$n]["name"];
}

// -------------------------------------------------------------

function get_status_abbr($n) {
	$statuses = get_statuses();
	return $statuses[$n]["abbr"];
}

// -------------------------------------------------------------

function get_colored_status_msg($n) {
	return '<span class="status' . $n . '">&nbsp;' . tohtml(get_status_name($n)) . '&nbsp;[' . tohtml(get_status_abbr($n)) . ']&nbsp;</span>';
}

// -------------------------------------------------------------

function get_multiplewordsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"test\">Test Marked Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"spl1\">Increase Status by 1 [+1]</option>";
	$r .= "<option value=\"smi1\">Reduce Status by 1 [-1]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= get_set_status_option(1);
	$r .= get_set_status_option(5);
	$r .= get_set_status_option(99);
	$r .= get_set_status_option(98);
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"today\">Set Status Date to Today</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"lower\">Set Marked Terms to Lowercase</option>";
	$r .= "<option value=\"cap\">Capitalize Marked Terms</option>";
	$r .= "<option value=\"delsent\">Delete Sentences of Marked Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtag\">Add Tag</option>";
	$r .= "<option value=\"deltag\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"exp\">Export Marked Terms (Anki)</option>";
	$r .= "<option value=\"exp2\">Export Marked Terms (TSV)</option>";
	$r .= "<option value=\"exp3\">Export Marked Terms (Flexible)</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"del\">Delete Marked Terms</option>";
	return $r;
}

// -------------------------------------------------------------

function get_multipletagsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option value=\"del\">Delete Marked Tags</option>";
	return $r;
}

// -------------------------------------------------------------

function get_allwordsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"testall\">Test ALL Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"spl1all\">Increase Status by 1 [+1]</option>";
	$r .= "<option value=\"smi1all\">Reduce Status by 1 [-1]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= get_set_status_option(1, "all");
	$r .= get_set_status_option(5, "all");
	$r .= get_set_status_option(99, "all");
	$r .= get_set_status_option(98, "all");
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"todayall\">Set Status Date to Today</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"lowerall\">Set ALL Terms to Lowercase</option>";
	$r .= "<option value=\"capall\">Capitalize ALL Terms</option>";
	$r .= "<option value=\"delsentall\">Delete Sentences of ALL Terms</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtagall\">Add Tag</option>";
	$r .= "<option value=\"deltagall\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"expall\">Export ALL Terms (Anki)</option>";
	$r .= "<option value=\"expall2\">Export ALL Terms (TSV)</option>";
	$r .= "<option value=\"expall3\">Export ALL Terms (Flexible)</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"delall\">Delete ALL Terms</option>";
	return $r;
}

// -------------------------------------------------------------

function get_alltagsactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option value=\"delall\">Delete ALL Tags</option>";
	return $r;
}

// -------------------------------------------------------------

function get_multipletextactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"test\">Test Marked Texts</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtag\">Add Tag</option>";
	$r .= "<option value=\"deltag\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"rebuild\">Reparse Texts</option>";
	$r .= "<option value=\"setsent\">Set Term Sentences</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"arch\">Archive Marked Texts</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"del\">Delete Marked Texts</option>";
	return $r;
}

// -------------------------------------------------------------

function get_multiplearchivedtextactions_selectoptions() {
	$r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"addtag\">Add Tag</option>";
	$r .= "<option value=\"deltag\">Remove Tag</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"unarch\">Unarchive Marked Texts</option>";
	$r .= "<option disabled=\"disabled\">------------</option>";
	$r .= "<option value=\"del\">Delete Marked Texts</option>";
	return $r;
}

// -------------------------------------------------------------

function get_texts_selectoptions($lang,$v) {
	global $tbpref;
	if ( ! isset($v) ) $v = '';
	if ( ! isset($lang) ) $lang = '';	
	if ( $lang=="" ) 
		$l = "";	
	else 
		$l = "and TxLgID=" . $lang;
	$r = "<option value=\"\"" . get_selected($v,'');
	$r .= ">[Filter off]</option>";
	$sql = "select TxID, TxTitle, LgName from " . $tbpref . "languages, " . $tbpref . "texts where LgID = TxLgID " . $l . " order by LgName, TxTitle";
	$res = do_mysqli_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$d = $record["TxTitle"];
		if ( mb_strlen($d, 'UTF-8') > 30 ) $d = mb_substr($d,0,30, 'UTF-8') . "...";
		$r .= "<option value=\"" . $record["TxID"] . "\"" . get_selected($v,$record["TxID"]) . ">" . tohtml( ($lang!="" ? "" : ($record["LgName"] . ": ")) . $d) . "</option>";
	}
	mysqli_free_result($res);
	return $r;
}

// -------------------------------------------------------------

function makePager ($currentpage, $pages, $script, $formname, $inst) {
	if ($currentpage > 1) { 
?>
&nbsp; &nbsp;<a href="<?php echo $script; ?>?page=1"><img src="icn/control-stop-180.png" title="First Page" alt="First Page" /></a>&nbsp;
<a href="<?php echo $script; ?>?page=<?php echo $currentpage-1; ?>"><img  src="icn/control-180.png" title="Previous Page" alt="Previous Page" /></a>&nbsp;
<?php
	} else {
?>
&nbsp; &nbsp;<img src="icn/placeholder.png" alt="-" />&nbsp;
<img src="icn/placeholder.png" alt="-" />&nbsp;
<?php
	} 
?>
Page
<?php
	if ($pages==1) echo '1';
	else {
?>
<select name="page<?php echo $inst; ?>" onchange="{val=document.<?php echo $formname; ?>.page<?php echo $inst; ?>.options[document.<?php echo $formname; ?>.page<?php echo $inst; ?>.selectedIndex].value; location.href='<?php echo $script; ?>?page=' + val;}"><?php echo get_paging_selectoptions($currentpage, $pages); ?></select>
<?php
	}
	echo ' of ' . $pages . '&nbsp; ';
	if ($currentpage < $pages) { 
?>
<a href="<?php echo $script; ?>?page=<?php echo $currentpage+1; ?>"><img src="icn/control.png" title="Next Page" alt="Next Page" /></a>&nbsp;
<a href="<?php echo $script; ?>?page=<?php echo $pages; ?>"><img src="icn/control-stop.png" title="Last Page" alt="Last Page" /></a>&nbsp; &nbsp;
<?php 
	} else {
?>
<img src="icn/placeholder.png" alt="-" />&nbsp;
<img src="icn/placeholder.png" alt="-" />&nbsp; &nbsp; 
<?php
	}
}

// -------------------------------------------------------------

function makeStatusCondition($fieldname, $statusrange) {
	if ($statusrange >= 12 && $statusrange <= 15) {
		return '(' . $fieldname . ' between 1 and ' . ($statusrange % 10) . ')';
	} elseif ($statusrange >= 23 && $statusrange <= 25) {
		return '(' . $fieldname . ' between 2 and ' . ($statusrange % 10) . ')';
	} elseif ($statusrange >= 34 && $statusrange <= 35) {
		return '(' . $fieldname . ' between 3 and ' . ($statusrange % 10) . ')';
	} elseif ($statusrange == 45) {
		return '(' . $fieldname . ' between 4 and 5)';
	} elseif ($statusrange == 599) {
		return $fieldname . ' in (5,99)';
	} else {
		return $fieldname . ' = ' . $statusrange;
	}
}

// -------------------------------------------------------------

function checkStatusRange($currstatus, $statusrange) {
	if ($statusrange >= 12 && $statusrange <= 15) {
		return ($currstatus >= 1 && $currstatus <= ($statusrange % 10));
	} elseif ($statusrange >= 23 && $statusrange <= 25) {
		return ($currstatus >= 2 && $currstatus <= ($statusrange % 10));
	} elseif ($statusrange >= 34 && $statusrange <= 35) {
		return ($currstatus >= 3 && $currstatus <= ($statusrange % 10));
	} elseif ($statusrange == 45) {
		return ($currstatus == 4 || $currstatus == 5);
	} elseif ($statusrange == 599) {
		return ($currstatus == 5 || $currstatus == 99);
	} else {
		return ($currstatus == $statusrange);
	}
}

// -------------------------------------------------------------

function makeStatusClassFilter($status) {
	if ($status == '') return '';
	$liste = array(1,2,3,4,5,98,99);
	if ($status == 599) {
		makeStatusClassFilterHelper(5,$liste);
		makeStatusClassFilterHelper(99,$liste);
	} elseif ($status < 6 || $status > 97) { 
		makeStatusClassFilterHelper($status,$liste);
	} else {
		$from = (int) ($status / 10);
		$to = $status - ($from*10);
		for ($i = $from; $i <= $to; $i++)
			makeStatusClassFilterHelper($i,$liste);
	}
	$r = '';
	foreach ($liste as $v) {
		if($v != -1) $r .= ':not(.status' . $v . ')';
	}
	return $r;
}

// -------------------------------------------------------------

function makeStatusClassFilterHelper($status,&$array) {
	$pos = array_search($status,$array);
	if ($pos !== FALSE) $array[$pos] = -1;
}

// -------------------------------------------------------------

function createTheDictLink($u,$t) {
	// Case 1: url without any ###: append UTF-8-term
	// Case 2: url with one ###: substitute UTF-8-term
	// Case 3: url with two ###enc###: substitute enc-encoded-term
	// see http://php.net/manual/en/mbstring.supported-encodings.php for supported encodings
	$url = trim($u);
	$trm = trim($t);
	$pos = stripos ($url, '###');
	if ($pos !== false) {  // ### found
		$pos2 = strripos ($url, '###');
		if ( ($pos2-$pos-3) > 1 ) {  // 2 ### found
			$enc = trim(substr($url, $pos+3, $pos2-$pos-3));
			$r = substr($url, 0, $pos);
			$r .= urlencode(mb_convert_encoding($trm, $enc, 'UTF-8'));
			if (($pos2+3) < strlen($url)) $r .= substr($url, $pos2+3);
		} 
		elseif ( $pos == $pos2 ) {  // 1 ### found
			$r = str_replace("###", ($trm == '' ? '+' : urlencode($trm)), $url);
		}
	}
	else  // no ### found
		$r = $url . urlencode($trm);
	return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin($lang,$word,$sentctljs,$openfirst) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	mysqli_free_result($res);
	$r ='';
	if ($openfirst) {
		$r .= '<script type="text/javascript">';
		$r .= "\n//<![CDATA[\n";
		$r .= makeOpenDictStrJS(createTheDictLink($wb1,$word));
		$r .= "//]]>\n</script>\n";
	}
	$r .= 'Lookup Term: ';
	$r .= makeOpenDictStr(createTheDictLink($wb1,$word), "Dict1"); 
	if ($wb2 != "") 
		$r .= makeOpenDictStr(createTheDictLink($wb2,$word), "Dict2"); 
	if ($wb3 != "") 
		$r .= makeOpenDictStr(createTheDictLink($wb3,$word), "GTr") . ' | Sent.: ' . makeOpenDictStrDynSent($wb3, $sentctljs, "GTr"); 
	return $r;
}

// -------------------------------------------------------------

function makeOpenDictStr($url, $txt) {
	$r = '';
	if ($url != '' && $txt != '') {
		if(substr($url,0,1) == '*') {
			$r = ' <span class="click" onclick="owin(' . prepare_textdata_js(substr($url,1)) . ');">' . tohtml($txt) . '</span> ';
		} 
		else {
			$r = ' <a href="' . $url . '" target="ru">' . tohtml($txt) . '</a> ';
		} 
	}
	return $r;
}

// -------------------------------------------------------------

function makeOpenDictStrJS($url) {
	$r = '';
	if ($url != '') {
		if(substr($url,0,1) == '*') {
			$r = "owin(" . prepare_textdata_js(substr($url,1)) . ");\n";
		} 
		else {
			$r = "top.frames['ru'].location.href=" . prepare_textdata_js($url) . ";\n";
		} 
	}
	return $r;
}

// -------------------------------------------------------------

function makeOpenDictStrDynSent($url, $sentctljs, $txt) {
	$r = '';
	if ($url != '') {
		if(substr($url,0,1) == '*') {
			$r = '<span class="click" onclick="translateSentence2(' . prepare_textdata_js(substr($url,1)) . ',' . $sentctljs . ');">' . tohtml($txt) . '</span>';
		} 
		else {
			$r = '<span class="click" onclick="translateSentence(' . prepare_textdata_js($url) . ',' . $sentctljs . ');">' . tohtml($txt) . '</span>';
		} 
	}
	return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin2($lang,$sentctljs,$wordctljs) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	if(substr($wb1,0,1) == '*') $wb1 = substr($wb1,1);
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	if(substr($wb2,0,1) == '*') $wb2 = substr($wb2,1);
	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	if(substr($wb3,0,1) == '*') $wb3 = substr($wb3,1);
	mysqli_free_result($res);
	$r ='';
	$r .= 'Lookup Term: ';
	$r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb1) . ',' . $wordctljs . ');">Dict1</span> ';
	if ($wb2 != "") 
		$r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb2) . ',' . $wordctljs . ');">Dict2</span> ';
	if ($wb3 != "") 
		$r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb3) . ',' . $wordctljs . ');">GTr</span> | Sent.: <span class="click" onclick="translateSentence2(' . prepare_textdata_js($wb3) . ',' . $sentctljs . ');">GTr</span>'; 
	return $r;
}

// -------------------------------------------------------------

function makeDictLinks($lang,$wordctljs) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	if(substr($wb1,0,1) == '*') $wb1 = substr($wb1,1);
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	if(substr($wb2,0,1) == '*') $wb2 = substr($wb2,1);
	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	if(substr($wb3,0,1) == '*') $wb3 = substr($wb3,1);
	mysqli_free_result($res);
	$r ='<span class="smaller">';
	$r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb1) . ',' . $wordctljs . ');">[1]</span> ';
	if ($wb2 != "") 
		$r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb2) . ',' . $wordctljs . ');">[2]</span> ';
	if ($wb3 != "") 
		$r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb3) . ',' . $wordctljs . ');">[G]</span>'; 
	$r .= '</span>';
	return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin3($lang,$sentctljs,$wordctljs) {
	global $tbpref;
	$sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	if(substr($wb1,0,1) == '*') 
		$f1 = 'translateWord2(' . prepare_textdata_js(substr($wb1,1));
	else 
		$f1 = 'translateWord(' . prepare_textdata_js($wb1);
		
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	if(substr($wb2,0,1) == '*') 
		$f2 = 'translateWord2(' . prepare_textdata_js(substr($wb2,1));
	else 
		$f2 = 'translateWord(' . prepare_textdata_js($wb2);

	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	if(substr($wb3,0,1) == '*') {
		$f3 = 'translateWord2(' . prepare_textdata_js(substr($wb3,1));
		$f4 = 'translateSentence2(' . prepare_textdata_js(substr($wb3,1));
	} else {
		$f3 = 'translateWord(' . prepare_textdata_js($wb3);
		$f4 = 'translateSentence(' . prepare_textdata_js($wb3);
	}

	mysqli_free_result($res);
	$r ='';
	$r .= 'Lookup Term: ';
	$r .= '<span class="click" onclick="' . $f1 . ',' . $wordctljs . ');">Dict1</span> ';
	if ($wb2 != "") 
		$r .= '<span class="click" onclick="' . $f2 . ',' . $wordctljs . ');">Dict2</span> ';
	if ($wb3 != "") 
		$r .= '<span class="click" onclick="' . $f3 . ',' . $wordctljs . ');">GTr</span> | Sent.: <span class="click" onclick="' . $f4 . ',' . $sentctljs . ');">GTr</span>'; 
	return $r;
}

// -------------------------------------------------------------

function checkTest($val, $name) {
	if (! isset($_REQUEST[$name])) return ' ';
	if (! is_array($_REQUEST[$name])) return ' ';
	if (in_array($val,$_REQUEST[$name])) return ' checked="checked" ';
	else return ' ';
}

// -------------------------------------------------------------

function strToHex($string)
{
  $hex='';
  for ($i=0; $i < strlen($string); $i++)
  {
  		$h = dechex(ord($string[$i]));
  		if ( strlen($h) == 1 ) 
  			$hex .= "0" . $h;
  		else
  		  $hex .= $h;
  }
  return strtoupper($hex);
}

// -------------------------------------------------------------

function strToClassName($string)
{
	// escapes everything to "¤xx" but not 0-9, a-z, A-Z, and unicode >= (hex 00A5, dec 165)
	$l = mb_strlen ($string, 'UTF-8');
	$r = '';
  for ($i=0; $i < $l; $i++)
  {
  	$c = mb_substr($string,$i,1, 'UTF-8');
  	$o = ord($c);
  	if (
  		($o < 48) || 
  		($o > 57 && $o < 65) || 
  		($o > 90 && $o < 97) || 
  		($o > 122 && $o < 165)
  		)
  		$r .= '¤' . strToHex($c);
  	else 
  		$r .= $c;
  }
  return $r;
}

// -------------------------------------------------------------

function anki_export($sql) {
	// WoID, LgRightToLeft, LgRegexpWordCharacters, LgName, WoText, WoTranslation, WoRomanization, WoSentence, taglist
	$res = do_mysqli_query($sql);
	$x = '';
	while ($record = mysqli_fetch_assoc($res)) {
		$rtlScript = $record['LgRightToLeft'];
		$span1 = ($rtlScript ? '<span dir="rtl">' : '');
		$span2 = ($rtlScript ? '</span>' : '');
		$lpar = ($rtlScript ? ']' : '[');
		$rpar = ($rtlScript ? '[' : ']');
		$sent = tohtml(repl_tab_nl($record["WoSentence"]));
		$sent1 = str_replace("{", '<span style="font-weight:600; color:#0000ff;">' . $lpar, str_replace("}", $rpar . '</span>', 
			mask_term_in_sentence($sent,$record["LgRegexpWordCharacters"])
		));
		$sent2 = str_replace("{", '<span style="font-weight:600; color:#0000ff;">', str_replace("}", '</span>', $sent));
		$x .= $span1 . tohtml(repl_tab_nl($record["WoText"])) . $span2 . "\t" . 
		tohtml(repl_tab_nl($record["WoTranslation"])) . "\t" . 
		tohtml(repl_tab_nl($record["WoRomanization"])) . "\t" . 
		$span1 . $sent1 . $span2 . "\t" . 
		$span1 . $sent2 . $span2 . "\t" . 
		tohtml(repl_tab_nl($record["LgName"])) . "\t" . 
		tohtml($record["WoID"]) . "\t" . 
		tohtml($record["taglist"]) .  
		"\r\n";
	}
	mysqli_free_result($res);
	header('Content-type: text/plain; charset=utf-8');
	header("Content-disposition: attachment; filename=lwt_anki_export_" . date('Y-m-d-H-i-s') . ".txt");
	echo $x;
	exit();
}

// -------------------------------------------------------------

function tsv_export($sql) {
	// WoID, LgName, WoText, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
	$res = do_mysqli_query($sql);
	$x = '';
	while ($record = mysqli_fetch_assoc($res)) {
		$x .= repl_tab_nl($record["WoText"]) . "\t" . 
		repl_tab_nl($record["WoTranslation"]) . "\t" . 
		repl_tab_nl($record["WoSentence"]) . "\t" . 
		repl_tab_nl($record["WoRomanization"]) . "\t" . 
		$record["WoStatus"] . "\t" . 
		repl_tab_nl($record["LgName"]) . "\t" . 
		$record["WoID"] . "\t" . 
		$record["taglist"] . "\r\n";
	}
	mysqli_free_result($res);
	header('Content-type: text/plain; charset=utf-8');
	header("Content-disposition: attachment; filename=lwt_tsv_export_" . date('Y-m-d-H-i-s') . ".txt");
	echo $x;
	exit();
}

// -------------------------------------------------------------

function flexible_export($sql) {
	// WoID, LgName, LgExportTemplate, LgRightToLeft, WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
	$res = do_mysqli_query($sql);
	$x = '';
	while ($record = mysqli_fetch_assoc($res)) {
		if (isset($record['LgExportTemplate'])) {
			$woid = $record['WoID'] + 0;
			$langname = repl_tab_nl($record['LgName']);
			$rtlScript = $record['LgRightToLeft'];
			$span1 = ($rtlScript ? '<span dir="rtl">' : '');
			$span2 = ($rtlScript ? '</span>' : '');
			$term = repl_tab_nl($record['WoText']);
			$term_lc = repl_tab_nl($record['WoTextLC']);
			$transl = repl_tab_nl($record['WoTranslation']);
			$rom = repl_tab_nl($record['WoRomanization']);
			$sent_raw = repl_tab_nl($record['WoSentence']);
			$sent = str_replace('{','',str_replace('}','',$sent_raw));
			$sent_c = mask_term_in_sentence_v2($sent_raw);
			$sent_d = str_replace('{','[',str_replace('}',']',$sent_raw));
			$sent_x = str_replace('{','{{c1::',str_replace('}','}}',$sent_raw));
			$sent_y = str_replace('{','{{c1::',str_replace('}','::' . $transl . '}}',$sent_raw));
			$status = $record['WoStatus'] + 0;
			$taglist = trim($record['taglist']);
			$xx = repl_tab_nl($record['LgExportTemplate']);	
			$xx = str_replace('%w',$term,$xx);		
			$xx = str_replace('%t',$transl,$xx);		
			$xx = str_replace('%s',$sent,$xx);		
			$xx = str_replace('%c',$sent_c,$xx);		
			$xx = str_replace('%d',$sent_d,$xx);		
			$xx = str_replace('%r',$rom,$xx);		
			$xx = str_replace('%a',$status,$xx);		
			$xx = str_replace('%k',$term_lc,$xx);		
			$xx = str_replace('%z',$taglist,$xx);		
			$xx = str_replace('%l',$langname,$xx);		
			$xx = str_replace('%n',$woid,$xx);		
			$xx = str_replace('%%','%',$xx);		
			$xx = str_replace('$w',$span1 . tohtml($term) . $span2,$xx);		
			$xx = str_replace('$t',tohtml($transl),$xx);		
			$xx = str_replace('$s',$span1 . tohtml($sent) . $span2,$xx);		
			$xx = str_replace('$c',$span1 . tohtml($sent_c) . $span2,$xx);		
			$xx = str_replace('$d',$span1 . tohtml($sent_d) . $span2,$xx);		
			$xx = str_replace('$x',$span1 . tohtml($sent_x) . $span2,$xx);		
			$xx = str_replace('$y',$span1 . tohtml($sent_y) . $span2,$xx);		
			$xx = str_replace('$r',tohtml($rom),$xx);		
			$xx = str_replace('$k',$span1 . tohtml($term_lc) . $span2,$xx);		
			$xx = str_replace('$z',tohtml($taglist),$xx);		
			$xx = str_replace('$l',tohtml($langname),$xx);		
			$xx = str_replace('$$','$',$xx);		
			$xx = str_replace('\\t',"\t",$xx);		
			$xx = str_replace('\\n',"\n",$xx);		
			$xx = str_replace('\\r',"\r",$xx);		
			$xx = str_replace('\\\\','\\',$xx);		
			$x .= $xx;
		}
	}
	mysqli_free_result($res);
	header('Content-type: text/plain; charset=utf-8');
	header("Content-disposition: attachment; filename=lwt_flexible_export_" . date('Y-m-d-H-i-s') . ".txt");
	echo $x;
	exit();
}

// -------------------------------------------------------------

function mask_term_in_sentence_v2($s) {
	$l = mb_strlen($s,'utf-8');
	$r = '';
	$on = 0;
	for ($i=0; $i < $l; $i++) {
		$c = mb_substr($s, $i, 1, 'UTF-8');
		if ($c == '}') { 
			$on = 0;
			continue;
		}
		if ($c == '{') {
			$on = 1;
			$r .= '[...]';
			continue;
		}
		if ($on == 0) {
			$r .= $c;
		}
	}
	return $r;
}

// -------------------------------------------------------------

function repl_tab_nl($s) {
	$s = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $s);
	$s = preg_replace('/\s/u', ' ', $s);
	$s = preg_replace('/\s{2,}/u', ' ', $s);
	return trim($s);
}

// -------------------------------------------------------------

function mask_term_in_sentence($s,$regexword) {
	$l = mb_strlen($s,'utf-8');
	$r = '';
	$on = 0;
	for ($i=0; $i < $l; $i++) {
		$c = mb_substr($s, $i, 1, 'UTF-8');
		if ($c == '}') $on = 0;
		if ($on) {
			if (preg_match('/[' . $regexword . ']/u', $c)) {
   			$r .= '•';
			} else {
   			$r .= $c;
			}	
		}
		else {
			$r .= $c;
		}
		if ($c == '{') $on = 1;
	}
	return $r;
}

// -------------------------------------------------------------

function textwordcount($text) {
	global $tbpref;
	return get_first_value('select count(distinct TiTextLC) as value from ' . $tbpref . 'textitems where TiIsNotWord = 0 and TiWordCount = 1 and TiTxID = ' . $text);
}

// -------------------------------------------------------------

function textexprcount($text) {
	global $tbpref;
	return get_first_value('select count(distinct TiTextLC) as value from ' . $tbpref . 'textitems left join ' . $tbpref . 'words on TiTextLC = WoTextLC where TiWordCount > 1 and TiIsNotWord = 0 and TiTxID = ' . $text . ' and WoID is not null and TiLgID = WoLgID');
}

// -------------------------------------------------------------

function textworkcount($text) {
	global $tbpref;
	return get_first_value('select count(distinct TiTextLC) as value from ' . $tbpref . 'textitems left join ' . $tbpref . 'words on TiTextLC = WoTextLC where TiWordCount = 1 and TiIsNotWord = 0 and TiTxID = ' . $text . ' and WoID is not null and TiLgID = WoLgID');
}

// -------------------------------------------------------------

function texttodocount($text) {
	return '<span title="To Do" class="status0">&nbsp;' . 
	(textwordcount($text) - textworkcount($text)) . '&nbsp;</span>';
}

// -------------------------------------------------------------

function texttodocount2($text) {
	$c = textwordcount($text) - textworkcount($text);
	if ($c > 0 ) 
		return '<span title="To Do" class="status0">&nbsp;' . $c . '&nbsp;</span>&nbsp;&nbsp;&nbsp;<input type="button" onclick="iknowall(' . $text . ');" value=" I KNOW ALL " />';
	else
		return '<span title="To Do" class="status0">&nbsp;' . $c . '&nbsp;</span>';
}

// -------------------------------------------------------------

function getSentence($seid, $wordlc,$mode) {
	global $tbpref;
	$txtid = get_first_value('select SeTxID as value from ' . $tbpref . 'sentences where SeID = ' . $seid);
	$seidlist = $seid;
	if ($mode > 1) {
		$prevseid = get_first_value('select SeID as value from ' . $tbpref . 'sentences where SeID < ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') order by SeID desc");
		if (isset($prevseid)) $seidlist .= ',' . $prevseid;
		if ($mode > 2) {
			$nextseid = get_first_value('select SeID as value from ' . $tbpref . 'sentences where SeID > ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') order by SeID asc");
			if (isset($nextseid)) $seidlist .= ',' . $nextseid;
		}
	}
	$sql2 = 'SELECT TiText, TiTextLC, TiWordCount, TiIsNotWord FROM ' . $tbpref . 'textitems WHERE TiSeID in (' . $seidlist . ') and TiTxID=' . $txtid . ' order by TiOrder asc, TiWordCount desc';
	$res2 = do_mysqli_query($sql2);
	$sejs=''; 
	$se='';
	$notfound = 1;
	$jump=0;
	while ($record2 = mysqli_fetch_assoc($res2)) {
		if ($record2['TiIsNotWord'] == 1) {
			$jump--;
			if ($jump < 0) {
				$sejs .= $record2['TiText']; 
				$se .= tohtml($record2['TiText']);
			} 
		}	else {
			if (($jump-1) < 0) {
				if ($notfound) {
					if ($record2['TiTextLC'] == $wordlc) { 
						$sejs.='{'; 
						$se.='<b>'; 
						$sejs .= $record2['TiText']; 
						$se .= tohtml($record2['TiText']); 
						$sejs.='}'; 
						$se.='</b>';
						$notfound = 0;
						$jump=($record2['TiWordCount']-1)*2; 
					}
				}
				if ($record2['TiWordCount'] == 1) {
					if ($notfound) {
						$sejs .= $record2['TiText']; 
						$se .= tohtml($record2['TiText']);
						$jump=0;  
					}	else {
						$notfound = 1;
					}
				}
			} else {
				if ($record2['TiWordCount'] == 1) $jump--; 
			}
		}
	}
	mysqli_free_result($res2);
	return array($se,$sejs); // [0]=html, word in bold
	                         // [1]=text, word in {} 
}

// -------------------------------------------------------------

function get20Sentences($lang, $wordlc, $jsctlname, $mode) {
	global $tbpref;
	$r = '<p><b>Sentences in active texts with <i>' . tohtml($wordlc) . '</i></b></p><p>(Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> to copy sentence into above term)</p>';
	$sql = 'SELECT DISTINCT SeID, SeText FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems WHERE TiTextLC = ' . convert_string_to_sqlsyntax($wordlc) . ' AND SeID = TiSeID AND SeLgID = ' . $lang . ' order by CHAR_LENGTH(SeText), SeText limit 0,20';
	$res = do_mysqli_query($sql);
	$r .= '<p>';
	$last = '';
	while ($record = mysqli_fetch_assoc($res)) {
		if ($last != $record['SeText']) {
			$sent = getSentence($record['SeID'], $wordlc,$mode);
			$r .= '<span class="click" onclick="{' . $jsctlname . '.value=' . prepare_textdata_js($sent[1]) . '; makeDirty();}"><img src="icn/tick-button.png" title="Choose" alt="Choose" /></span> &nbsp;' . $sent[0] . '<br />';
		}
		$last = $record['SeText'];
	}
	mysqli_free_result($res);
	$r .= '</p>';
	return $r;
}

// -------------------------------------------------------------

function getsqlscoreformula ($method) {
	// $method = 2 (today)
	// $method = 3 (tomorrow)
	// Formula: {{{2.4^{Status}+Status-Days-1} over Status -2.4} over 0.14325248}
		
	if ($method == 3) return 'CASE WHEN WoStatus > 5 THEN 100 ELSE (((POWER(2.4,WoStatus) + WoStatus - DATEDIFF(NOW(),WoStatusChanged) - 2) / WoStatus - 2.4) / 0.14325248) END';

	elseif ($method == 2) return 'CASE WHEN WoStatus > 5 THEN 100 ELSE (((POWER(2.4,WoStatus) + WoStatus - DATEDIFF(NOW(),WoStatusChanged) - 1) / WoStatus - 2.4) / 0.14325248) END';
	
	else return '0';
	
}

// -------------------------------------------------------------

function AreUnknownWordsInSentence ($sentno) {
	global $tbpref;
	$x = get_first_value("SELECT distinct ifnull(WoTextLC,'') as value FROM (" . $tbpref . "textitems left join " . $tbpref . "words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) where TiSeID = " . $sentno . " AND TiWordCount = 1 AND TiIsNotWord = 0 order by WoTextLC asc limit 1");
	// echo $sentno . '/' . isset($x) . '/' . $x . '/';
	if ( isset($x) ) {
		if ( $x == '' ) return true;
	}
	return false;
}

// -------------------------------------------------------------

function get_statuses() {
	static $statuses;
	if (!$statuses) {
		$statuses = array(
				 1 => array("abbr" =>   "1", "name" => "Learning"),
				 2 => array("abbr" =>   "2", "name" => "Learning"),
				 3 => array("abbr" =>   "3", "name" => "Learning"),
				 4 => array("abbr" =>   "4", "name" => "Learning"),
				 5 => array("abbr" =>   "5", "name" => "Learned"),
				99 => array("abbr" => "WKn", "name" => "Well Known"),
				98 => array("abbr" => "Ign", "name" => "Ignored"),
		);
	}
	return $statuses;
}

// -------------------------------------------------------------

function get_languages() {
	global $tbpref;
	$langs = array();
	$sql = "select LgID, LgName from " . $tbpref . "languages";
	$res = do_mysqli_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$langs[$record['LgName']] = $record['LgID'];
	}
	mysqli_free_result($res);
	return $langs;
}

// -------------------------------------------------------------

function get_setting_data() {
	static $setting_data;
	if (! $setting_data) {
		$setting_data = array(
		'set-text-h-frameheight-no-audio' => 
		array("dft" => '140', "num" => 1, "min" => 10, "max" => 999),
		'set-text-h-frameheight-with-audio' => 
		array("dft" => '200', "num" => 1, "min" => 10, "max" => 999),
		'set-text-l-framewidth-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-text-r-frameheight-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-test-h-frameheight' => 
		array("dft" => '140', "num" => 1, "min" => 10, "max" => 999),
		'set-test-l-framewidth-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-test-r-frameheight-percent' => 
		array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
		'set-test-main-frame-waiting-time' => 
		array("dft" => '0', "num" => 1, "min" => 0, "max" => 9999),
		'set-test-edit-frame-waiting-time' => 
		array("dft" => '500', "num" => 1, "min" => 0, "max" => 99999999),
		'set-test-sentence-count' => 
		array("dft" => '1', "num" => 0),
		'set-term-sentence-count' => 
		array("dft" => '1', "num" => 0),
		'set-archivedtexts-per-page' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-texts-per-page' => 
		array("dft" => '10', "num" => 1, "min" => 1, "max" => 9999),
		'set-terms-per-page' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-tags-per-page' => 
		array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
		'set-show-text-word-counts' => 
		array("dft" => '1', "num" => 0),
		'set-text-visit-statuses-via-key' => 
		array("dft" => '', "num" => 0),
		'set-term-translation-delimiters' => 
		array("dft" => '/;|', "num" => 0),
		'set-mobile-display-mode' => 
		array("dft" => '0', "num" => 0),
		'set-similar-terms-count' => 
		array("dft" => '0', "num" => 1, "min" => 0, "max" => 9)
		);
	}
	return $setting_data;
}

// -------------------------------------------------------------

function reparse_all_texts() {
	global $tbpref;
	runsql('TRUNCATE ' . $tbpref . 'sentences','');
	runsql('TRUNCATE ' . $tbpref . 'textitems','');
	adjust_autoincr('sentences','SeID');
	adjust_autoincr('textitems','TiID');
	$sql = "select TxID, TxLgID from " . $tbpref . "texts";
	$res = do_mysqli_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$id = $record['TxID'];
		splitCheckText(
			get_first_value('select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), $record['TxLgID'], $id );
	}
	mysqli_free_result($res);
}

// -------------------------------------------------------------

function getLanguage($lid) {
	global $tbpref;
	if ( ! isset($lid) ) return '';
	if ( trim($lid) == '' ) return '';
	if ( ! is_numeric($lid) ) return '';
	$r = get_first_value("select LgName as value from " . $tbpref . "languages where LgID='" . $lid . "'");
	if ( isset($r) ) return $r;
	return '';
}

// -------------------------------------------------------------

function getScriptDirectionTag($lid) {
	global $tbpref;
	if ( ! isset($lid) ) return '';
	if ( trim($lid) == '' ) return '';
	if ( ! is_numeric($lid) ) return '';
	$r = get_first_value("select LgRightToLeft as value from " . $tbpref . "languages where LgID='" . $lid . "'");
	if ( isset($r) ) {
		if ($r) return ' dir="rtl" '; 
	}
	return '';
}

// -------------------------------------------------------------

function echodebug($var,$text) {
	global $debug;
	if (! $debug ) return;
	echo "<pre> **DEBUGGING** " . tohtml($text) . ' = [[[';
	print_r($var);
	echo "]]]\n--------------</pre>";
}

// -------------------------------------------------------------

function splitCheckText($text, $lid, $id) {   
	// $id = -1     => Check, return protocol
	// $id = -2     => Only return sentence array
	// $id = TextID => Split: insert sentences/textitems entries in DB
	global $tbpref;
	$r = '';
	$sql = "select * from " . $tbpref . "languages where LgID=" . $lid;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	if ($record == FALSE) my_die("Language data not found: $sql");
	$removeSpaces = $record['LgRemoveSpaces'];
	$splitEachChar = $record['LgSplitEachChar'];
	$splitSentence = $record['LgRegexpSplitSentences'];
	$noSentenceEnd = $record['LgExceptionsSplitSentences'];
	$termchar = $record['LgRegexpWordCharacters'];
	$replace = explode("|",$record['LgCharacterSubstitutions']);
	$rtlScript = $record['LgRightToLeft'];
	mysqli_free_result($res);
	$s = prepare_textdata($text);
	$s = str_replace("\n", " ¶ ", $s);
	$s = str_replace("\t", " ", $s);
	$s = trim($s);
	if ($splitEachChar) {
		$s = preg_replace('/([^\s])/u', "$1 ", $s);
	}
	$s = preg_replace('/\s{2,}/u', ' ', $s);
	if ($id == -1) $r .= "<div style=\"margin-right:50px;\"><h4>Text</h4><p " .  ($rtlScript ? 'dir="rtl"' : '') . ">" . str_replace("¶", "<br /><br />", tohtml($s)). "</p>";

	$s = str_replace('{', '[', $s);	// because of sent. spc. char
	$s = str_replace('}', ']', $s);	
	foreach ($replace as $value) {
		$fromto = explode("=",trim($value));
		if(count($fromto) >= 2) {
  		$s = str_replace(trim($fromto[0]), trim($fromto[1]), $s);
		}
	}
	$s = trim($s);
	
	if ($noSentenceEnd != '') $s = preg_replace('/(' . $noSentenceEnd . ')\s/u', '$1‧', $s);
	$s = preg_replace('/([' . $splitSentence . '¶])\s/u', "$1\n", $s);
	$s = str_replace(" ¶\n", "\n¶\n", $s);
	$s = str_replace('‧', ' ', $s);
	
	if ($s=='') {
		$textLines = array($s);
	} else {
		$s = explode("\n",$s);
		$l = count($s);
		for ($i=0; $i<$l; $i++) {
  		$s[$i] = trim($s[$i]);
  		if ($s[$i] != '') {
	  		$pos = strpos($splitSentence, $s[$i]);
	  		while ($pos !== false && $i > 0) {
	  			$s[$i-1] .= " " . $s[$i];
	  			for ($j=$i+1; $j<$l; $j++) $s[$j-1] = $s[$j];
	  			array_pop($s);
	  			$l = count($s);
	  			$pos = strpos($splitSentence, $s[$i]);
	  		}
  		}
		}
		$l = count($s);
		$textLines = array();
		for ($i=0; $i<$l; $i++) {
			$zz = trim($s[$i]);
			if ($zz != '' ) $textLines[] = $zz;
		}
	}
	
	
	if ($id == -2) {
	
		////////////////////////////////////
		// Only return sentence array
		
		return $textLines;
		
	}

	$lineWords = array();
		
	if ($id == -1) {
	
		////////////////////////////////////
		// Check, return protocol
		
		$wordList = array();
		$wordSeps = array();
		$r .= "<h4>Sentences</h4><ol>";
		$sentNumber = 0;
		foreach ($textLines as $value) { 
			$r .= "<li " .  ($rtlScript ? 'dir="rtl"' : '') . ">" . tohtml(remove_spaces($value, $removeSpaces)) . "</li>";
			$lineWords[$sentNumber] = preg_split('/([^' . $termchar . ']{1,})/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE );
			$l = count($lineWords[$sentNumber]);
			for ($i=0; $i<$l; $i++) {
				$term = mb_strtolower($lineWords[$sentNumber][$i], 'UTF-8');
				if ($term != '') {
					if ($i % 2 == 0) {
						if(array_key_exists($term,$wordList)) {
							$wordList[$term][0]++;
							$wordList[$term][1][] = $sentNumber;
						}
						else {
							$wordList[$term] = array(1, array($sentNumber));
						}
					} else {
						$ww = remove_spaces($term, $removeSpaces);
						if(array_key_exists($ww,$wordSeps))
							$wordSeps[$ww]++;
						else	
							$wordSeps[$ww]=1;
					}
				}
			}
			$sentNumber += 1;
		} 
		$r .= "</ol><h4>Word List <span class=\"red2\">(red = already saved)</span></h4><ul>";
		ksort($wordList); 
		$anz = 0;
		foreach ($wordList as $key => $value) {
			$trans = get_first_value("select WoTranslation as value from " . $tbpref . "words where WoLgID = " . $lid . " and WoTextLC = " . convert_string_to_sqlsyntax($key));
			if (! isset($trans)) $trans="";
			if ($trans == "*") $trans="";
			if ($trans != "") 
				$r .= "<li " .  ($rtlScript ? 'dir="rtl"' : '') . "><span class=\"red2\">[" . tohtml($key) . "] — " . $value[0] . " - " . tohtml(repl_tab_nl($trans)) . "</span></li>";
			else
				$r .= "<li " .  ($rtlScript ? 'dir="rtl"' : '') . ">[" . tohtml($key) . "] — " . $value[0] . "</li>";	
			$anz++;
		} 
		$r .= "</ul><p>TOTAL: " . $anz . "</p><h4>Non-Word List</h4><ul>";
		if(array_key_exists('',$wordSeps)) unset($wordSeps['']);
		ksort($wordSeps); 
		$anz = 0;
		foreach ($wordSeps as $key => $value) { 
			$r .= "<li>[" . str_replace(" ", "<span class=\"backgray\">&nbsp;</span>", tohtml($key)) . "] — " . $value . "</li>";
			$anz++;
		} 
		$r .=  "</ul><p>TOTAL: " . $anz . "</p></div>"; 
		return $r;
	}
	
	////////////////////////////////////
	// Split: insert sentences/textitems entries in DB
	
	$sentNumber = 0;
	$lfdnr =0;

	foreach ($textLines as $value) { 
		
		$dummy = runsql('INSERT INTO ' . $tbpref . 'sentences (SeLgID, SeTxID, SeOrder, SeText) VALUES (' . $lid . ',' .  $id . ',' .  ($sentNumber+1) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($value . ' ', $removeSpaces)) . ')', ' ');
		$sentid = get_last_key();
		$lineWords[$sentNumber] = preg_split('/([^' . $termchar . ']+)/u', $value . ' ', null, PREG_SPLIT_DELIM_CAPTURE );
		$l = count($lineWords[$sentNumber]);
		$sqltext = 'INSERT INTO ' . $tbpref . 'textitems (TiLgID, TiTxID, TiSeID, TiOrder, TiWordCount, TiText, TiTextLC, TiIsNotWord) VALUES ';
		$lfdnr1=0;
		for ($i=0; $i<$l; $i++) {
			$term = mb_strtolower($lineWords[$sentNumber][$i], 'UTF-8');
			$rest2 = '';
			$rest3 = '';
			$rest4 = '';
			$rest5 = '';
			$rest6 = '';
			$rest7 = '';
			$rest8 = '';
			$rest9 = '';
			$restlc2 = '';
			$restlc3 = '';
			$restlc4 = '';
			$restlc5 = '';
			$restlc6 = '';
			$restlc7 = '';
			$restlc8 = '';
			$restlc9 = '';
			if ($term != '') {
				if ($i % 2 == 0) {
					$isnotwort=0;
					$rest = $lineWords[$sentNumber][$i];
					$cnt = 0;
					for ($j=$i+1; $j<$l; $j++) {
						if ($lineWords[$sentNumber][$j] != '') {
							$rest .= $lineWords[$sentNumber][$j]; $cnt++;
							if($cnt == 2) { $rest2 = $rest; $restlc2 = mb_strtolower($rest, 'UTF-8'); }
							if($cnt == 4) { $rest3 = $rest; $restlc3 = mb_strtolower($rest, 'UTF-8'); }
							if($cnt == 6) { $rest4 = $rest; $restlc4 = mb_strtolower($rest, 'UTF-8'); }
							if($cnt == 8) { $rest5 = $rest; $restlc5 = mb_strtolower($rest, 'UTF-8'); }
							if($cnt == 10) { $rest6 = $rest; $restlc6 = mb_strtolower($rest, 'UTF-8'); }
							if($cnt == 12) { $rest7 = $rest; $restlc7 = mb_strtolower($rest, 'UTF-8'); }
							if($cnt == 14) { $rest8 = $rest; $restlc8 = mb_strtolower($rest, 'UTF-8'); }
							if($cnt == 16) { $rest9 = $rest; $restlc9 = mb_strtolower($rest, 'UTF-8'); break; }
						}
					}
				} else {
					$isnotwort=1;
				}
				
				$lfdnr++;
				$lfdnr1++;
				if ($lfdnr1 > 1) $sqltext .= ',';
				$sqltext .= '(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 1, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($lineWords[$sentNumber][$i], $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($term, $removeSpaces)) . ',' . $isnotwort . ')';
				if ($isnotwort==0) {
					if ($rest2 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 2, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest2, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc2, $removeSpaces)) . ',' . $isnotwort . ')';
					if ($rest3 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 3, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest3, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc3, $removeSpaces)) . ',' . $isnotwort . ')';
					if ($rest4 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 4, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest4, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc4, $removeSpaces)) . ',' . $isnotwort . ')';
					if ($rest5 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 5, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest5, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc5, $removeSpaces)) . ',' . $isnotwort . ')';
					if ($rest6 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 6, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest6, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc6, $removeSpaces)) . ',' . $isnotwort . ')';
					if ($rest7 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 7, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest7, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc7, $removeSpaces)) . ',' . $isnotwort . ')';
					if ($rest8 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 8, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest8, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc8, $removeSpaces)) . ',' . $isnotwort . ')';
					if ($rest9 != '') $sqltext .= ',(' . $lid . ',' .  $id . ',' .  $sentid . ',' . $lfdnr . ', 9, ' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($rest9, $removeSpaces)) . ',' . convert_string_to_sqlsyntax_notrim_nonull(remove_spaces($restlc9, $removeSpaces)) . ',' . $isnotwort . ')';
				}
			}
		}
		if ($lfdnr > 0) $dummy = runsql($sqltext,'');
		$sentNumber += 1;
	} 

}

// -------------------------------------------------------------

function restore_file($handle, $title) {
	global $tbpref;
	$message = "";
	$lines = 0;
	$ok = 0;
	$errors = 0;
	$drops = 0;
	$inserts = 0;
	$creates = 0;
	$start = 1;
	while (! gzeof($handle)) {
		$sql_line = trim(
			str_replace("\r","",
			str_replace("\n","",
			gzgets($handle, 99999))));
		if ($sql_line != "") {
			if($start) {
				if (strpos($sql_line,"-- lwt-backup-") === false ) {
					$message = "Error: Invalid " . $title . " Restore file (possibly not created by LWT backup)";
					$errors = 1;
					break;
				}
				$start = 0;
				continue;
			}
			if ( substr($sql_line,0,3) !== '-- ' ) {
				$res = mysqli_query($GLOBALS['DBCONNECTION'], insert_prefix_in_sql($sql_line));
				$lines++;
				if ($res == FALSE) $errors++;
				else {
					$ok++;
					if (substr($sql_line,0,11) == "INSERT INTO") $inserts++;
					elseif (substr($sql_line,0,10) == "DROP TABLE") $drops++;
					elseif (substr($sql_line,0,12) == "CREATE TABLE") $creates++;
				}
				// echo $ok . " / " . tohtml(insert_prefix_in_sql($sql_line)) . "<br />";
			}
		}
	} // while (! feof($handle))
	gzclose ($handle);
	if ($errors == 0) {
		reparse_all_texts();
		optimizedb();
		get_tags($refresh = 1);
		get_texttags($refresh = 1);
		$message = "Success: " . $title . " restored - " .
		$lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
	} else {
		if ($message == "") {
			$message = "Error: " . $title . " NOT restored - " .
			$lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
		}
	}
	return $message;
}

// -------------------------------------------------------------

function recreate_save_ann($textid, $oldann) {
	global $tbpref;
	$newann = create_ann($textid);
	// Get the translations from $oldann:
	$oldtrans = array();
	$olditems = preg_split('/[\n]/u', $oldann);
	foreach ($olditems as $olditem) {
		$oldvals = preg_split('/[\t]/u', $olditem);
		if ($oldvals[0] > -1) {
			$trans = '';
			if (count($oldvals) > 3) $trans = $oldvals[3];
			$oldtrans[$oldvals[0] . "\t" . $oldvals[1]] = $trans;
		}
	}
	// Reset the translations from $oldann in $newann and rebuild in $ann:
	$newitems = preg_split('/[\n]/u', $newann);
	$ann = '';
	foreach ($newitems as $newitem) {
		$newvals = preg_split('/[\t]/u', $newitem);
		if ($newvals[0] > -1) {
			$key = $newvals[0] . "\t";
			if (isset($newvals[1])) $key .= $newvals[1];
			if (array_key_exists($key, $oldtrans)) {
				$newvals[3] = $oldtrans[$key];
			}
			$item = implode("\t", $newvals);
		} else {
			$item = $newitem;
		}
		$ann .= $item . "\n";
	}
	$dummy = runsql('update ' . $tbpref . 'texts set ' .
		'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' where TxID = ' . $textid, "");
	return get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
}

// -------------------------------------------------------------

function create_ann($textid) {
	global $tbpref;
	$ann = '';
	$sql = 'select TiWordCount as Code, TiText, TiOrder, TiIsNotWord, WoID, WoTranslation from (' . $tbpref . 'textitems left join ' . $tbpref . 'words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) where TiTxID = ' . $textid . ' and (not (TiWordCount > 1 and WoID is null)) order by TiOrder asc, TiWordCount desc';
	$savenonterm = '';
	$saveterm = '';
	$savetrans = '';
	$savewordid = '';
	$until = 0;
	$res = do_mysqli_query($sql);
	while ($record = mysqli_fetch_assoc($res)) {
		$actcode = $record['Code'] + 0;
		$order = $record['TiOrder'] + 0;
		if ( $order <= $until ) {
			continue;
		}
		if ( $order > $until ) {
			$ann = $ann . process_term($savenonterm, $saveterm, $savetrans, $savewordid, $order);
			$savenonterm = '';
			$saveterm = '';
			$savetrans = '';
			$savewordid = '';
			$until = $order;
		}
		if ($record['TiIsNotWord'] != 0) {
			$savenonterm = $savenonterm . $record['TiText'];
		}
		else {
			$until = $order + 2 * ($actcode-1);
			$saveterm = $record['TiText'];
			$savetrans = '';
			if(isset($record['WoID'])) {
				$savetrans = $record['WoTranslation'];
				$savewordid = $record['WoID'];
			}
		}
	} // while
	mysqli_free_result($res);
	$ann .= process_term($savenonterm, $saveterm, $savetrans, $savewordid, $order);
	return $ann;
}

// -------------------------------------------------------------

function str_replace_first ($needle, $replace, $haystack) {
	if ($needle === '') 
		return $haystack;
	$pos = strpos($haystack,$needle);
	if ($pos !== false) {
    return substr_replace($haystack,$replace,$pos,strlen($needle));
	}
	return $haystack;
}

// -------------------------------------------------------------

function annotation_to_json ($ann) {
	if ($ann == '') return "{}";
	$arr = array();
	$items = preg_split('/[\n]/u', $ann);
	foreach ($items as $item) {
		$vals = preg_split('/[\t]/u', $item);
		if (count($vals) > 3 && $vals[0] >= 0 && $vals[2] > 0) {
			$arr[$vals[0]-1] = array($vals[1],$vals[2],$vals[3]);
		}
	}
	return json_encode($arr);
}

// -------------------------------------------------------------

function LWTTableCheck () {
	if (mysqli_num_rows(do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")) == 0) {
		runsql("CREATE TABLE IF NOT EXISTS _lwtgeneral ( LWTKey varchar(40) NOT NULL, LWTValue varchar(40) DEFAULT NULL, PRIMARY KEY (LWTKey) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
		if (mysqli_num_rows(do_mysqli_query("SHOW TABLES LIKE '\\_lwtgeneral'")) == 0) my_die("Unable to create table '_lwtgeneral'!");
	}
}

// -------------------------------------------------------------

function LWTTableSet ($key, $val) {
	LWTTableCheck ();
	runsql("INSERT INTO _lwtgeneral (LWTKey, LWTValue) VALUES (" . convert_string_to_sqlsyntax($key) . ", " . convert_string_to_sqlsyntax($val) . ") ON DUPLICATE KEY UPDATE LWTValue = " . convert_string_to_sqlsyntax($val),'');
}

// -------------------------------------------------------------

function LWTTableGet ($key) {
	LWTTableCheck ();
	return get_first_value("SELECT LWTValue as value FROM _lwtgeneral WHERE LWTKey = " . convert_string_to_sqlsyntax($key));
}

// -------------------------------------------------------------

function insert_prefix_in_sql ($sql_line) {
	global $tbpref;
	//                                 123456789012345678901
	if     (substr($sql_line,0,12) == "INSERT INTO ")
		return substr($sql_line,0,12) . $tbpref . substr($sql_line,12);
	elseif (substr($sql_line,0,21) == "DROP TABLE IF EXISTS ")
		return substr($sql_line,0,21) . $tbpref . substr($sql_line,21);
	elseif (substr($sql_line,0,14) == "CREATE TABLE `")
		return substr($sql_line,0,14) . $tbpref . substr($sql_line,14);
	elseif (substr($sql_line,0,13) == "CREATE TABLE ")
		return substr($sql_line,0,13) . $tbpref . substr($sql_line,13);
	else
		return $sql_line;
}

// -------------------------------------------------------------

function create_save_ann($textid) {
	global $tbpref;
	$ann = create_ann($textid);
	$dummy = runsql('update ' . $tbpref . 'texts set ' .
		'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' where TxID = ' . $textid, "");
	return get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
}

// -------------------------------------------------------------

function process_term($nonterm, $term, $trans, $wordid, $line) {
	$r = '';
	if ($nonterm != '') $r = $r . "-1\t" . $nonterm . "\n";
	if ($term != '') $r = $r . $line . "\t" . $term . "\t" . trim($wordid) . "\t" . get_first_translation($trans) . "\n";
	return $r;
}

// -------------------------------------------------------------

function get_first_translation($trans) {
	$arr = preg_split('/[' . get_sepas()  . ']/u', $trans);
	if (count($arr) < 1) return '';
	$r = trim($arr[0]);
	if ($r == '*') $r ="";
	return $r;
}

// -------------------------------------------------------------

function get_annotation_link($textid) {
	global $tbpref;
	if ( get_first_value('select length(TxAnnotatedText) as value from ' . $tbpref . 'texts where TxID=' . $textid) > 0) 
	return ' &nbsp;<a href="print_impr_text.php?text=' . $textid . '" target="_top"><img src="icn/tick.png" title="Annotated Text" alt="Annotated Text" /></a>';
	else 
		return '';
}

// -------------------------------------------------------------

function trim_value(&$value) 
{ 
	$value = trim($value); 
}

// -------------------------------------------------------------

function makeAudioPlayer($audio) {
	if ($audio != '') {
		$playerskin = "jplayer.blue.monday.modified";
		$repeatMode = getSettingZeroOrOne('currentplayerrepeatmode',0);
?>
<link type="text/css" href="css/jplayer_skin/<?php echo $playerskin; ?>.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
<table align="center" style="margin-top:5px;" cellspacing="0" cellpadding="0">
<tr>
<td class="center borderleft" style="padding-left:10px;">
<span id="do-single" class="click<?php echo ($repeatMode ? '' : ' hide'); ?>"><img src="icn/arrow-repeat.png" alt="Toggle Repeat (Now ON)" title="Toogle Repeat (Now ON)" style="width:24px;height:24px;" /></span><span id="do-repeat" class="click<?php echo ($repeatMode ? ' hide' : ''); ?>"><img src="icn/arrow-norepeat.png" alt="Toggle Repeat (Now OFF)" title="Toggle Repeat (Now OFF)" style="width:24px;height:24px;" /></span>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="bordermiddle">
<div id="jquery_jplayer_1" class="jp-jplayer">
</div>
<div id="jp_container_1" class="jp-audio">
	<div class="jp-type-single">
		<div class="jp-gui jp-interface">
			<ul class="jp-controls">
				<li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
				<li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
				<li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
				<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
				<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
			</ul>
			<div class="jp-progress">
				<div class="jp-seek-bar">
					<div class="jp-play-bar"></div>
				</div>
			</div>
			<div class="jp-volume-bar">
				<div class="jp-volume-bar-value"></div>
			</div>
			<div class="jp-time-holder">
				<div class="jp-current-time"></div>
				<div class="jp-duration"></div>
			</div>
		</div>
	</div>
</div>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="center bordermiddle">
<?php
$currentplayerseconds = getSetting('currentplayerseconds');
if($currentplayerseconds == '') $currentplayerseconds = 5;
?>
<select id="backtime" name="backtime"><?php echo get_seconds_selectoptions($currentplayerseconds); ?></select><br />
<span id="backbutt" class="click"><img src="icn/arrow-circle-225-left.png" alt="Rewind n seconds" title="Rewind n seconds" /></span>&nbsp;&nbsp;<span id="forwbutt" class="click"><img src="icn/arrow-circle-315.png" alt="Forward n seconds" title="Forward n seconds" /></span>
<span id="playTime" class="hide"></span>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="center borderright" style="padding-right:10px;">
<?php
$currentplaybackrate = getSetting('currentplaybackrate');
if($currentplaybackrate == '') $currentplaybackrate = 10;
?>
<select id="playbackrate" name="playbackrate"><?php echo get_playbackrate_selectoptions($currentplaybackrate); ?></select><br />
<span id="slower" class="click"><img src="icn/minus.png" alt="Slower" title="Slower" style="margin-top:3px" /></span>&nbsp;<span id="stdspeed" class="click"><img src="icn/status-away.png" alt="Normal" title="Normal" style="margin-top:3px" /></span>&nbsp;<span id="faster" class="click"><img src="icn/plus.png" alt="Faster" title="Faster" style="margin-top:3px" /></span>
</td>
</tr>
<script type="text/javascript">
//<![CDATA[

function new_pos(p) {
	$("#jquery_jplayer_1").jPlayer("playHead", p);
}

function set_new_playerseconds() {
	var newval = ($("#backtime :selected").val());
	do_ajax_save_setting('currentplayerseconds',newval); 
	// console.log("set_new_playerseconds="+newval);
}

function set_new_playbackrate() {
	var newval = ($("#playbackrate :selected").val());
	do_ajax_save_setting('currentplaybackrate',newval); 
	$("#jquery_jplayer_1").jPlayer("option","playbackRate", newval*0.1);
	// console.log("set_new_playbackrate="+newval);
}

function set_current_playbackrate() {
	var val = ($("#playbackrate :selected").val());
	$("#jquery_jplayer_1").jPlayer("option","playbackRate", val*0.1);
	// console.log("set_current_playbackrate="+val);
}

function click_single() {
	$("#jquery_jplayer_1").unbind($.jPlayer.event.ended + ".jp-repeat");
	$("#do-single").addClass('hide');
	$("#do-repeat").removeClass('hide');
	do_ajax_save_setting('currentplayerrepeatmode','0');
	return false;
}

function click_repeat() {
	$("#jquery_jplayer_1").bind($.jPlayer.event.ended + ".jp-repeat", function(event) { 
		$(this).jPlayer("play"); 
	});
	$("#do-repeat").addClass('hide');
	$("#do-single").removeClass('hide');
	do_ajax_save_setting('currentplayerrepeatmode','1');
	return false;
}

function click_back() {
	var t = parseInt($("#playTime").text(),10);
	var b = parseInt($("#backtime").val(),10);
	var nt = t - b;
	if (nt < 0) nt = 0;
	$("#jquery_jplayer_1").jPlayer("play", nt);
}

function click_forw() {
	var t = parseInt($("#playTime").text(),10);
	var b = parseInt($("#backtime").val(),10);
	var nt = t + b;
	$("#jquery_jplayer_1").jPlayer("play", nt);
}

function click_stdspeed() {
	$("#playbackrate").val(10);
	set_new_playbackrate();
}

function click_slower() {
	var val = ($("#playbackrate :selected").val());
	if (val > 5) {
		val--;
		$("#playbackrate").val(val);
		set_new_playbackrate();
	}
}

function click_faster() {
	var val = ($("#playbackrate :selected").val());
	if (val < 15) {
		val++;
		$("#playbackrate").val(val);
		set_new_playbackrate();
	}
}

$(document).ready(function(){
	  $("#jquery_jplayer_1").jPlayer({
    ready: function () {
      $(this).jPlayer("setMedia", { <?php 
	$audio = trim($audio);
	if (strcasecmp(substr($audio,-4), '.mp3') == 0) { 
  	echo 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  } elseif (strcasecmp(substr($audio,-4), '.ogg') == 0) { 
  	echo 'oga: ' . prepare_textdata_js(encodeURI($audio))  . ", " . 
  			 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  } elseif (strcasecmp(substr($audio,-4), '.wav') == 0) {
  	echo 'wav: ' . prepare_textdata_js(encodeURI($audio))  . ", " . 
  			 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  } else {
  	echo 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
  }
?> });
    },
    swfPath: "js",
    noVolume: {ipad: /^no$/, iphone: /^no$/, ipod: /^no$/, android_pad: /^no$/, android_phone: /^no$/, blackberry: /^no$/, windows_ce: /^no$/, iemobile: /^no$/, webos: /^no$/, playbook: /^no$/}
  });
  
  $("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate, function(event) { 
  	$("#playTime").text(Math.floor(event.jPlayer.status.currentTime));
	});
  
  $("#jquery_jplayer_1").bind($.jPlayer.event.play, function(event) { 
  	set_current_playbackrate();
  	// console.log("play");
	});
  
  $("#slower").click(click_slower);
  $("#faster").click(click_faster);
  $("#stdspeed").click(click_stdspeed);
  $("#backbutt").click(click_back);
  $("#forwbutt").click(click_forw);
  $("#do-single").click(click_single);
  $("#do-repeat").click(click_repeat);
  $("#playbackrate").change(set_new_playbackrate);
  $("#backtime").change(set_new_playerseconds);
  
  <?php echo ($repeatMode ? "click_repeat();\n" : ''); ?>
});
//]]>
</script>
<?php
	} // if (isset($audio))
}

// -------------------------------------------------------------

function make_score_random_insert_update($type) {  // $type='iv'/'id'/'u'
	if ($type == 'iv') {
		return ' WoTodayScore, WoTomorrowScore, WoRandom ';
	} elseif ($type == 'id') {
		return ' ' . getsqlscoreformula(2) . ', ' . getsqlscoreformula(3) . ', RAND() ';
	} elseif ($type == 'u') {
		return ' WoTodayScore = ' . getsqlscoreformula(2) . ', WoTomorrowScore = ' . getsqlscoreformula(3) . ', WoRandom = RAND() ';
	} else {
		return '';
	}
}

// -------------------------------------------------------------

function refreshText($word,$tid) {
	global $tbpref;
	// $word : only sentences with $word
	// $tid : textid
	// only to be used when $showAll = 0 !
	$out = '';
	$wordlc = trim(mb_strtolower($word, 'UTF-8'));
	if ( $wordlc == '') return '';
	$sql = 'SELECT distinct TiSeID FROM ' . $tbpref . 'textitems WHERE TiIsNotWord = 0 and TiTextLC = ' . convert_string_to_sqlsyntax($wordlc) . ' and TiTxID = ' . $tid . ' order by TiSeID';
	$res = do_mysqli_query($sql);
	$inlist = '(';
	while ($record = mysqli_fetch_assoc($res)) { 
		if ($inlist == '(') 
			$inlist .= $record['TiSeID'];
		else
			$inlist .= ',' . $record['TiSeID'];
	}
	mysqli_free_result($res);
	if ($inlist == '(') 
		return '';
	else
		$inlist =  ' where TiSeID in ' . $inlist . ') ';
	$sql = 'select TiWordCount as Code, TiOrder, TiIsNotWord, WoID from (' . $tbpref . 'textitems left join ' . $tbpref . 'words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) ' . $inlist . ' order by TiOrder asc, TiWordCount desc';

	$res = do_mysqli_query($sql);		

	$hideuntil = -1;
	$hidetag = "removeClass('hide');";

	while ($record = mysqli_fetch_assoc($res)) {  // MAIN LOOP
		$actcode = $record['Code'] + 0;
		$order = $record['TiOrder'] + 0;
		$notword = $record['TiIsNotWord'] + 0;
		$termex = isset($record['WoID']);
		$spanid = 'ID-' . $order . '-' . $actcode;

		if ( $hideuntil > 0 ) {
			if ( $order <= $hideuntil )
				$hidetag = "addClass('hide');";
			else {
				$hideuntil = -1;
				$hidetag = "removeClass('hide');";
			}
		}

		if ($notword != 0) {  // NOT A TERM
			$out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
		}  

		else {   // A TERM
			if ($actcode > 1) {   // A MULTIWORD FOUND
				if ($termex) {  // MULTIWORD FOUND - DISPLAY 
					if ($hideuntil == -1) $hideuntil = $order + ($actcode - 1) * 2;
					$out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
				}
				else {  // MULTIWORD PLACEHOLDER - NO DISPLAY 
					$out .= "$('#" . $spanid . "',context).addClass('hide');\n";
				}  
			} // ($actcode > 1) -- A MULTIWORD FOUND

			else {  // ($actcode == 1)  -- A WORD FOUND
				$out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
			}  
		}
	} //  MAIN LOOP
	mysqli_free_result($res);
	return $out;
}

// -------------------------------------------------------------

function check_update_db() {
	global $debug, $tbpref;
	$tables = array();
	
	$res = do_mysqli_query(str_replace('_',"\\_","SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull($tbpref . '%')));
  while ($row = mysqli_fetch_row($res)) 
  	$tables[] = $row[0];
	mysqli_free_result($res);
	
	$count = 0;  // counter for cache rebuild
	
	// Rebuild Tables if missing (current versions!)
	
	if (in_array($tbpref . 'archivedtexts', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding archivedtexts</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "archivedtexts ( AtID int(11) unsigned NOT NULL AUTO_INCREMENT, AtLgID int(11) unsigned NOT NULL, AtTitle varchar(200) NOT NULL, AtText text NOT NULL, AtAnnotatedText longtext NOT NULL, AtAudioURI varchar(200) DEFAULT NULL, AtSourceURI varchar(1000) DEFAULT NULL, PRIMARY KEY (AtID), KEY AtLgID (AtLgID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'languages', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding languages</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "languages ( LgID int(11) unsigned NOT NULL AUTO_INCREMENT, LgName varchar(40) NOT NULL, LgDict1URI varchar(200) NOT NULL, LgDict2URI varchar(200) DEFAULT NULL, LgGoogleTranslateURI varchar(200) DEFAULT NULL, LgExportTemplate varchar(1000) DEFAULT NULL, LgTextSize int(5) unsigned NOT NULL DEFAULT '100', LgCharacterSubstitutions varchar(500) NOT NULL, LgRegexpSplitSentences varchar(500) NOT NULL, LgExceptionsSplitSentences varchar(500) NOT NULL, LgRegexpWordCharacters varchar(500) NOT NULL, LgRemoveSpaces int(1) unsigned NOT NULL DEFAULT '0', LgSplitEachChar int(1) unsigned NOT NULL DEFAULT '0', LgRightToLeft int(1) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (LgID), UNIQUE KEY LgName (LgName) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'sentences', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding sentences</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "sentences ( SeID int(11) unsigned NOT NULL AUTO_INCREMENT, SeLgID int(11) unsigned NOT NULL, SeTxID int(11) unsigned NOT NULL, SeOrder int(11) unsigned NOT NULL, SeText text, PRIMARY KEY (SeID), KEY SeLgID (SeLgID), KEY SeTxID (SeTxID), KEY SeOrder (SeOrder) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
		$count++;
	}
	
	if (in_array($tbpref . 'settings', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding settings</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "settings ( StKey varchar(40) NOT NULL, StValue varchar(40) DEFAULT NULL, PRIMARY KEY (StKey) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'textitems', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding textitems</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "textitems ( TiID int(11) unsigned NOT NULL AUTO_INCREMENT, TiLgID int(11) unsigned NOT NULL, TiTxID int(11) unsigned NOT NULL, TiSeID int(11) unsigned NOT NULL, TiOrder int(11) unsigned NOT NULL, TiWordCount int(1) unsigned NOT NULL, TiText varchar(250) NOT NULL, TiTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, TiIsNotWord tinyint(1) NOT NULL, PRIMARY KEY (TiID), KEY TiLgID (TiLgID), KEY TiTxID (TiTxID), KEY TiSeID (TiSeID), KEY TiOrder (TiOrder), KEY TiTextLC (TiTextLC), KEY TiIsNotWord (TiIsNotWord) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
		$count++;
	}
	
	if (in_array($tbpref . 'texts', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding texts</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "texts ( TxID int(11) unsigned NOT NULL AUTO_INCREMENT, TxLgID int(11) unsigned NOT NULL, TxTitle varchar(200) NOT NULL, TxText text NOT NULL, TxAnnotatedText longtext NOT NULL, TxAudioURI varchar(200) DEFAULT NULL, TxSourceURI varchar(1000) DEFAULT NULL, PRIMARY KEY (TxID), KEY TxLgID (TxLgID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'words', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding words</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "words ( WoID int(11) unsigned NOT NULL AUTO_INCREMENT, WoLgID int(11) unsigned NOT NULL, WoText varchar(250) NOT NULL, WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, WoStatus tinyint(4) NOT NULL, WoTranslation varchar(500) NOT NULL DEFAULT '*', WoRomanization varchar(100) DEFAULT NULL, WoSentence varchar(1000) DEFAULT NULL, WoCreated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, WoStatusChanged timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', WoTodayScore double NOT NULL DEFAULT '0', WoTomorrowScore double NOT NULL DEFAULT '0', WoRandom double NOT NULL DEFAULT '0', PRIMARY KEY (WoID), UNIQUE KEY WoLgIDTextLC (WoLgID,WoTextLC), KEY WoLgID (WoLgID), KEY WoStatus (WoStatus), KEY WoTextLC (WoTextLC), KEY WoTranslation (WoTranslation(333)), KEY WoCreated (WoCreated), KEY WoStatusChanged (WoStatusChanged), KEY WoTodayScore (WoTodayScore), KEY WoTomorrowScore (WoTomorrowScore), KEY WoRandom (WoRandom) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'tags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding tags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "tags ( TgID int(11) unsigned NOT NULL AUTO_INCREMENT, TgText varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, TgComment varchar(200) NOT NULL DEFAULT '', PRIMARY KEY (TgID), UNIQUE KEY TgText (TgText) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'wordtags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding wordtags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "wordtags ( WtWoID int(11) unsigned NOT NULL, WtTgID int(11) unsigned NOT NULL, PRIMARY KEY (WtWoID,WtTgID), KEY WtTgID (WtTgID), KEY WtWoID (WtWoID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'tags2', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding tags2</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "tags2 ( T2ID int(11) unsigned NOT NULL AUTO_INCREMENT, T2Text varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, T2Comment varchar(200) NOT NULL DEFAULT '', PRIMARY KEY (T2ID), UNIQUE KEY T2Text (T2Text) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'texttags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding texttags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "texttags ( TtTxID int(11) unsigned NOT NULL, TtT2ID int(11) unsigned NOT NULL, PRIMARY KEY (TtTxID,TtT2ID), KEY TtTxID (TtTxID), KEY TtT2ID (TtT2ID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if (in_array($tbpref . 'archtexttags', $tables) == FALSE) {
		if ($debug) echo '<p>DEBUG: rebuilding archtexttags</p>';
		runsql("CREATE TABLE IF NOT EXISTS " . $tbpref . "archtexttags ( AgAtID int(11) unsigned NOT NULL, AgT2ID int(11) unsigned NOT NULL, PRIMARY KEY (AgAtID,AgT2ID), KEY AgAtID (AgAtID), KEY AgT2ID (AgT2ID) ) ENGINE=MyISAM DEFAULT CHARSET=utf8",'');
	}
	
	if ($count > 0) {		
		// Rebuild Text Cache if cache tables new
		if ($debug) echo '<p>DEBUG: rebuilding cache tables</p>';
		reparse_all_texts();
	}
	
	// DB Version
	
	$currversion = get_version_number();
	
	$res = mysqli_query($GLOBALS['DBCONNECTION'], "select StValue as value from " . $tbpref . "settings where StKey = 'dbversion'");
	if (mysqli_errno($GLOBALS['DBCONNECTION']) != 0) my_die('There is something wrong with your database ' . $dbname . '. Please reinstall.');
	$record = mysqli_fetch_assoc($res);	
	if ($record) {
		$dbversion = $record["value"];
	} else {  
		$dbversion = 'v001000000';
	}
	mysqli_free_result($res);
	
	// Do DB Updates if tables seem to be old versions
	
	if ( $dbversion < $currversion ) {
		if ($debug) echo "<p>DEBUG: do DB updates: $dbversion --&gt; $currversion</p>";
		runsql("ALTER TABLE " . $tbpref . "words ADD WoTodayScore DOUBLE NOT NULL DEFAULT 0, ADD WoTomorrowScore DOUBLE NOT NULL DEFAULT 0, ADD WoRandom DOUBLE NOT NULL DEFAULT 0", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "words ADD INDEX WoTodayScore (WoTodayScore), ADD INDEX WoTomorrowScore (WoTomorrowScore), ADD INDEX WoRandom (WoRandom)", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "languages ADD LgRightToLeft INT(1) UNSIGNED NOT NULL DEFAULT  0", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "texts ADD TxAnnotatedText LONGTEXT NOT NULL AFTER TxText", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "archivedtexts ADD AtAnnotatedText LONGTEXT NOT NULL AFTER AtText", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "tags CHANGE TgComment TgComment VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "tags2 CHANGE T2Comment T2Comment VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "languages CHANGE LgGoogleTTSURI LgExportTemplate VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "texts ADD TxSourceURI VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", '', $sqlerrdie = FALSE);
		runsql("ALTER TABLE " . $tbpref . "archivedtexts ADD AtSourceURI VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", '', $sqlerrdie = FALSE);
		// set to current.
		saveSetting('dbversion',$currversion);
		saveSetting('lastscorecalc','');  // do next section, too
	}
	
	// Do Scoring once per day, clean Word/Texttags, and optimize db
	
	$lastscorecalc = getSetting('lastscorecalc');
	$today = date('Y-m-d');
	if ($lastscorecalc != $today) {
		if ($debug) echo '<p>DEBUG: Doing score recalc. Today: ' . $today . ' / Last: ' . $lastscorecalc . '</p>';
		runsql("UPDATE " . $tbpref . "words SET " . make_score_random_insert_update('u'),'');
		runsql("DELETE " . $tbpref . "wordtags FROM (" . $tbpref . "wordtags LEFT JOIN " . $tbpref . "tags on WtTgID = TgID) WHERE TgID IS NULL",'');
		runsql("DELETE " . $tbpref . "wordtags FROM (" . $tbpref . "wordtags LEFT JOIN " . $tbpref . "words on WtWoID = WoID) WHERE WoID IS NULL",'');
		runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL",'');
		runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL",'');
		runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "tags2 on AgT2ID = T2ID) WHERE T2ID IS NULL",'');
		runsql("DELETE " . $tbpref . "archtexttags FROM (" . $tbpref . "archtexttags LEFT JOIN " . $tbpref . "archivedtexts on AgAtID = AtID) WHERE AtID IS NULL",'');
		optimizedb();
		saveSetting('lastscorecalc',$today);
	}
}

// -------------------------------------------------------------

//////////////////  S T A R T  /////////////////////////////////

// Start Timer

if ($dspltime) get_execution_time();

// Connection, @ suppresses messages from function

$DBCONNECTION = @mysqli_connect($server, $userid, $passwd, $dbname);

if ((! $DBCONNECTION) && mysqli_connect_errno() == 1049) {
	$DBCONNECTION = @mysqli_connect($server, $userid, $passwd);
	if (! $DBCONNECTION) my_die('DB connect error (MySQL not running or connection parameters are wrong; start MySQL and/or correct file "connect.inc.php"). Please read the documentation: http://lwt.sf.net [Error Code: ' . mysqli_connect_errno() . ' / Error Message: ' . mysqli_connect_error() . ']');
	runsql("CREATE DATABASE `" . $dbname . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",'');
	mysqli_close($DBCONNECTION);
	$DBCONNECTION = @mysqli_connect($server, $userid, $passwd, $dbname);
}

if (! $DBCONNECTION) my_die('DB connect error (MySQL not running or connection parameters are wrong; start MySQL and/or correct file "connect.inc.php"). Please read the documentation: http://lwt.sf.net [Error Code: ' . mysqli_connect_errno() . ' / Error Message: ' . mysqli_connect_error() . ']');

@mysqli_query($DBCONNECTION, "SET NAMES 'utf8'");

// @mysqli_query($DBCONNECTION, "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
@mysqli_query($DBCONNECTION, "SET SESSION sql_mode = ''");

// *** GLOBAL VARAIABLES ***
// $tbpref = Current Table Prefix
// $fixed_tbpref = Table Prefix is fixed, no changes possible
// *** GLOBAL VARAIABLES ***

// Is $tbpref set in connect.inc.php? Take it and $fixed_tbpref=1.
// If not: $fixed_tbpref=0. Is it set in table "_lwtgeneral"? Take it.
// If not: Use $tbpref = '' (no prefix, old/standard behaviour).

if (! isset($tbpref)) {
	$fixed_tbpref = 0;             
	$p = LWTTableGet("current_table_prefix");
	if (isset($p)) 
		$tbpref = $p;
	else {
		$tbpref = '';
	}
} 
else
	$fixed_tbpref = 1;

$len_tbpref = strlen($tbpref); 
if ($len_tbpref > 0) {
	if ($len_tbpref > 20) my_die('Table prefix/set "' . $tbpref . '" longer than 20 digits or characters. Please fix in "connect.inc.php".');
	for ($i=0; $i < $len_tbpref; $i++) 
		if (strpos("_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", substr($tbpref,$i,1)) === FALSE) my_die('Table prefix/set "' . $tbpref . '" contains characters or digits other than 0-9, a-z, A-Z or _. Please fix in "connect.inc.php".'); 
}

if (! $fixed_tbpref) 
	LWTTableSet ("current_table_prefix", $tbpref);

// *******************************************************************
// IF PREFIX IS NOT '', THEN ADD A '_', TO ENSURE NO IDENTICAL NAMES
if ( $tbpref !== '') $tbpref .= "_";
// *******************************************************************

// check/update db
check_update_db();

// -------------------------------------------------------------

?>