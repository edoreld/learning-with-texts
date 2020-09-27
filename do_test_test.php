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
Call: do_test_test.php?type=[testtype]&lang=[langid]
Call: do_test_test.php?type=[testtype]&text=[textid]
Call: do_test_test.php?type=[testtype]&selection=1  
			(SQL via $_SESSION['testsql'])
Show test frame
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$p = '';

if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) {
	$testsql = $_SESSION['testsql']; 
}

elseif (isset($_REQUEST['lang'])) {
	$testsql = ' ' . $tbpref . 'words where WoLgID = ' . $_REQUEST['lang'] . ' '; 
}

elseif (isset($_REQUEST['text'])) {
	$testsql = ' ' . $tbpref . 'words, ' . $tbpref . 'textitems where TiLgID = WoLgID and TiTextLC = WoTextLC and TiTxID = ' . $_REQUEST['text'] . ' ';
}

else my_die("do_test_test.php called with wrong parameters");

$testtype = getreq('type') + 0;
if ($testtype < 1) $testtype=1;
if ($testtype > 5) $testtype=5;
$nosent = 0;
if ($testtype > 3) {
	$testtype = $testtype - 3;
	$nosent = 1;
}

$totaltests = $_SESSION['testtotal'];
$wrong = $_SESSION['testwrong'];
$correct = $_SESSION['testcorrect'];

pagestart_nobody('','html, body { width:100%; height:100%; } html {display:table;} body { display:table-cell; vertical-align:middle; } #body { max-width:95%; margin:0 auto; }');

$cntlang = get_first_value('select count(distinct WoLgID) as value from ' . $testsql);
if ($cntlang > 1) {
	echo '<p>Sorry - The selected terms are in ' . $cntlang . ' languages, but tests are only possible in one language at a time.</p>';
	pageend();
	exit();
}

?>
<div id="body">
<?php

$count = get_first_value('SELECT count(distinct WoID) as value FROM ' . $testsql . ' AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != \'\' AND WoTranslation != \'*\' AND WoTodayScore < 0');
if ($debug) echo 'DEBUG - COUNT TO TEST: ' . $count . '<br />';
$notyettested = $count;

if ($count <= 0) {

	$count2 = get_first_value('SELECT count(distinct WoID) as value FROM ' . $testsql . ' AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != \'\' AND WoTranslation != \'*\' AND WoTomorrowScore < 0');
	
	echo '<p class="center"><img src="img/ok.png" alt="Done!" /><br /><br /><span class="red2">Nothing ' . ($totaltests ? 'more ' : '') . 'to test here!<br /><br />Tomorrow you\'ll find here ' . $count2 . ' test' . ($count2 == 1 ? '' : 's') . '!</span></p></div>';
	$count = 0;

} else {

	$lang = get_first_value('select WoLgID as value from ' . $testsql . ' limit 1');
	
	$sql = 'select LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, LgTextSize, LgRemoveSpaces, LgRegexpWordCharacters, LgRightToLeft from ' . $tbpref . 'languages where LgID = ' . $lang;
	$res = do_mysqli_query($sql);
	$record = mysqli_fetch_assoc($res);
	$wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
	$wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
	$wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
	$textsize = $record['LgTextSize'];
	$removeSpaces = $record['LgRemoveSpaces'];
	$regexword = $record['LgRegexpWordCharacters'];
	$rtlScript = $record['LgRightToLeft'];
	$langname = $record['LgName'];
	mysqli_free_result($res);
	
	// Find the next word to test
	
	$pass = 0;
	$num = 0;
	while ($pass < 2) {
		$pass++;
		$sql = 'SELECT DISTINCT WoID, WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence, (ifnull(WoSentence,\'\') not like concat(\'%{\',WoText,\'}%\')) as notvalid, WoStatus, DATEDIFF( NOW( ), WoStatusChanged ) AS Days, WoTodayScore AS Score FROM ' . $testsql . ' AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != \'\' AND WoTranslation != \'*\' AND WoTodayScore < 0 ' . ($pass == 1 ? 'AND WoRandom > RAND()' : '') . ' order by WoTodayScore, WoRandom LIMIT 1';
		if ($debug) echo 'DEBUG TEST-SQL: ' . $sql . '<br />';
		$res = do_mysqli_query($sql);
		$record = mysqli_fetch_assoc($res);
		if ( $record ) {
			$num = 1;
			$wid = $record['WoID'];
			$word = $record['WoText'];
			$wordlc = $record['WoTextLC'];
			$trans = repl_tab_nl($record['WoTranslation']) . getWordTagList($wid,' ',1,0);
			$roman = $record['WoRomanization'];
			$sent = repl_tab_nl($record['WoSentence']);
			$notvalid = $record['notvalid'];
			$status = $record['WoStatus'];
			$days = $record['Days'];
			$score = $record['Score'];
			$pass = 2;
		}
		mysqli_free_result($res);
	}
	
	if ($num == 0) {
	
		// should not occur but...
		echo '<p class="center"><img src="img/ok.png" alt="Done!" /><br /><br /><span class="red2">Nothing to test here!</span></p></div>';
		$count = 0;
		
	} else {

		if ( $nosent)	{  // No sent. mode 4+5
			$num = 0;
			$notvalid = 1;
		}
		else { // $nosent == FALSE, mode 1-3
			$pass = 0;
			$sentexcl = '';
			while ( $pass < 3 ) {
				$pass++;
				if ($debug) echo "DEBUG search sent: pass: $pass <br />";
				$sql = 'SELECT DISTINCT SeID FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems WHERE TiTextLC = ' . convert_string_to_sqlsyntax($wordlc) . $sentexcl . ' AND SeID = TiSeID AND SeLgID = ' . $lang . ' order by rand() limit 1';
				$res = do_mysqli_query($sql);
				$record = mysqli_fetch_assoc($res);
				if ( $record ) {  // random sent found
					$num = 1;
					$seid = $record['SeID'];
					if (AreUnknownWordsInSentence ($seid)) {
						if ($debug) echo "DEBUG sent: $seid has unknown words<br />";
						$sentexcl = ' AND SeID != ' . $seid . ' ';
						$num = 0;
						// not yet found, $num == 0 (unknown words in sent)
					} else {
						// echo ' OK ';
						$sent = getSentence($seid, $wordlc,	(int) getSettingWithDefault('set-test-sentence-count'));
						$sent = $sent[1];
						if ($debug) echo "DEBUG sent: $seid OK: $sent <br />";
						$pass = 3;
						// found, $num == 1
					}
				} else {  // no random sent found
					$num = 0;
					$pass = 3;
					if ($debug) echo "DEBUG no random sent found<br />";
					// no sent. take term sent. $num == 0
				}
				mysqli_free_result($res);
			} // while ( $pass < 3 )
		}  // $nosent == FALSE
	
		if ($num == 0 ) {
			// take term sent. if valid
			if ($notvalid) $sent = '{' . $word . '}';
			if ($debug) echo "DEBUG not found, use sent = $sent<br />";
		}
		
		$cleansent = trim(str_replace("{", '', str_replace("}", '', $sent)));
		// echo $cleansent;
		
		echo '<p ' . ($rtlScript ? 'dir="rtl"' : '') . ' style="' . ($removeSpaces ? 'word-break:break-all;' : '') . 'font-size:' . $textsize . '%;line-height: 1.4; text-align:center; margin-bottom:300px;">';
		$l = mb_strlen($sent,'utf-8');
		$r = '';
		$save = '';
		$on = 0;
		
		for ($i=0; $i < $l; $i++) {  // go thru sent
			$c = mb_substr($sent, $i, 1, 'UTF-8');
			if ($c == '}') {
				$r .= ' <span style="word-break:normal;" class="click todo todosty word wsty word' . $wid . '" data_wid="' . $wid . '" data_trans="' . tohtml($trans) . '" data_text="' . tohtml($word) . '" data_rom="' . tohtml($roman) . '" data_sent="' . tohtml($cleansent) . '" data_status="' . $status . '" data_todo="1"';
				if ($testtype ==3) $r .= ' title="' . tohtml($trans) . '"'; 
				$r .= '>';
				if ($testtype == 2) {
					if ($nosent) $r .= tohtml($trans);
					else $r .= '<span dir="ltr">[' . tohtml($trans) . ']</span>';
				}
				elseif ($testtype == 3) 
					$r .= tohtml(str_replace("{", '[', str_replace("}", ']', 
					mask_term_in_sentence('{' . $save . '}',
					$regexword)	)));
				else 
					$r .= tohtml($save);
				$r .= '</span> ';
				$on = 0;
			}
			elseif ($c == '{') {
				$on = 1;
				$save = '';
			}
			else {
				if ( $on ) $save .= $c;
				else $r .= tohtml($c);
			}
		} // for: go thru sent
		
		echo $r;  // Show Sentence
	}
	
?>

<script type="text/javascript">
//<![CDATA[
WBLINK1 = '<?php echo $wb1; ?>';
WBLINK2 = '<?php echo $wb2; ?>';
WBLINK3 = '<?php echo $wb3; ?>';
SOLUTION = <?php echo prepare_textdata_js ( $testtype==1 ? ( $nosent ? ($trans) : (' [' . $trans . '] ')) : $save ); ?>;
OPENED = 0;
WID = <?php echo $wid; ?>;
$(document).ready( function() {
	$(document).keydown(keydown_event_do_test_test);
	$('.word').click(word_click_event_do_test_test);
});
//]]>
</script>

</p></div>

<?php

} 

$wrong = $_SESSION['testwrong'];
$correct = $_SESSION['testcorrect'];
$totaltests = $wrong + $correct + $notyettested;
$totaltestsdiv = 1;
if ($totaltests > 0) $totaltestsdiv = 1.0/$totaltests;
$l_notyet = round(($notyettested * $totaltestsdiv)*100,0);
$b_notyet = ($l_notyet == 0) ? '' : 'borderl';
$l_wrong = round(($wrong * $totaltestsdiv)*100,0);
$b_wrong = ($l_wrong == 0) ? '' : 'borderl';
$l_correct = round(($correct * $totaltestsdiv)*100,0);
$b_correct = ($l_correct == 0) ? 'borderr' : 'borderl borderr';

?>

<div id="footer">
<img src="icn/clock.png" title="Elapsed Time" alt="Elapsed Time" />
<span id="timer" title="Elapsed Time"></span>
&nbsp; &nbsp; &nbsp; 
<img class="<?php echo $b_notyet; ?>" src="icn/test_notyet.png" title="Not yet tested" alt="Not yet tested" height="10" width="<?php echo $l_notyet; ?>" /><img class="<?php echo $b_wrong; ?>" src="icn/test_wrong.png" title="Wrong" alt="Wrong" height="10" width="<?php echo $l_wrong; ?>" /><img class="<?php echo $b_correct; ?>" src="icn/test_correct.png" title="Correct" alt="Correct" height="10" width="<?php echo $l_correct; ?>" />
&nbsp; &nbsp; &nbsp; 
<span title="Total number of tests"><?php echo $totaltests; ?></span>
= 
<span class="todosty" title="Not yet tested"><?php echo $notyettested; ?></span>
+ 
<span class="donewrongsty" title="Wrong"><?php echo $wrong; ?></span>
+ 
<span class="doneoksty" title="Correct"><?php echo $correct; ?></span>
</div>

<script type="text/javascript">
//<![CDATA[
$(document).ready( function() {
	window.parent.frames['ru'].location.href='empty.htm';
<?php
$waittime = getSettingWithDefault('set-test-edit-frame-waiting-time') + 0;
if ($waittime <= 0 ) {
?>
	window.parent.frames['ro'].location.href='empty.htm';
<?php
} else {
?>
	setTimeout('window.parent.frames[\'ro\'].location.href=\'empty.htm\';', <?php echo $waittime; ?>);
<?php
}
?>
	new CountUp(<?php echo time() . ', ' . $_SESSION['teststart']; ?>, 
		'timer', <?php echo ($count ? 0 : 1); ?>);
});
//]]>
</script>

<?php

pageend();

?>