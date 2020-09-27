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
Language Settings for Wizard
***************************************************************/

// "Name" => ("glosbeIso", "googleIso", biggerFont, "wordCharRegExp",
//           "sentSplRegExp", makeCharacterWord, removeSpaces, rightToLeft)

$langDefs = array(

"Afrikaans" => 
	array("af", "af", false, 
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ",	
	".!?:;", 
	false, false, false),

"Arabic" => 
	array("ar", "ar", true, 
	"\\x{0600}-\\x{061A}\\x{0620}-\\x{06FF}" .
	"\\x{0750}-\\x{077F}\\x{FB50}-\\x{FDFF}" .
	"\\x{FE70}-\\x{FEFF}", 
	".!?:;\\x{061B}\\x{061F}", 
	false, false, true),

"Belarusian" => 
	array("be", "be", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Bulgarian" => 
	array("bg", "bg", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Catalan" => 
	array("ca", "ca", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Chinese (Simplified)" => 
	array("zh", "zh-CN", true,
	"\\x{4E00}-\\x{9FFF}\\x{F900}-\\x{FAFF}", 
	".!?:;。！？：；", 
	true, true, false),

"Chinese (Traditional)" => 
	array("zh", "zh-TW", true,
	"\\x{4E00}-\\x{9FFF}\\x{F900}-\\x{FAFF}" .
	"\\x{3100}-\\x{312F}", 
	".!?:;。！？：；", 
	true, true, false),

"Croatian" => 
	array("hr", "hr", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Czech" => 
	array("cs", "cs", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Danish" => 
	array("da", "da", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Dutch" => 
	array("nl", "nl", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"English" => 
	array("en", "en", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Esperanto" => 
	array("eo", "eo", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Estonian" => 
	array("et", "et", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Finnish" => 
	array("fi", "fi", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"French" => 
	array("fr", "fr", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"German" => 
	array("de", "de", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Greek (Modern)" => 
	array("el", "el", false,
	"\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}", 
	".!?:;", 
	false, false, false),

"Hebrew" => 
	array("he", "iw", true, 
	"\\x{0590}-\\x{05FF}", 
	".!?:;", 
	false, false, true),

"Hungarian" => 
	array("hu", "hu", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Italian" => 
	array("it", "it", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Japanese" => 
	array("ja", "ja", true, 
	"\\x{4E00}-\\x{9FFF}\\x{F900}-\\x{FAFF}" . 
	"\\x{3040}-\\x{30FF}\\x{31F0}-\\x{31FF}", 
	".!?:;。！？：；", 
	true, true, false),

"Korean" => 
	array("ko", "ko", true, 
	"\\x{4E00}-\\x{9FFF}\\x{F900}-\\x{FAFF}\\x{1100}-\\x{11FF}" .
	"\\x{3130}-\\x{318F}\\x{AC00}-\\x{D7A0}", 
	".!?:;。！？：；", 
	false, false, false),

"Latin" => 
	array("la", "la", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Latvian" => 
	array("lv", "lv", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Lithuanian" => 
	array("lt", "lt", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Macedonian" => 
	array("mk", "mk", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Norwegian Bokmål" => 
	array("nb", "no", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Polish" => 
	array("pl", "pl", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Portuguese" => 
	array("pt", "pt", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Romanian" => 
	array("ro", "ro", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Russian" => 
	array("ru", "ru", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Serbian" => 
	array("sr", "sr", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Slovak" => 
	array("sk", "sk", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Spanish" => 
	array("es", "es", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Swedish" => 
	array("sv", "sv", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Thai" => 
	array("th", "th", true,
	"\\x{0E00}-\\x{0E7F}", 
	".!?:;", 
	false, false, false),

"Turkish" => 
	array("tr", "tr", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false),

"Ukrainian" => 
	array("uk", "uk", false,
	"\\-\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ", 
	".!?:;", 
	false, false, false)

);

?>
