<?php
/**
*
* @author PoPoutdoor
*
* @package RSS_input
* @version $Id:$
* @copyright (c) 2008-2013 PoPoutdoor
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/


/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}


global $user;
//global $phpbb_root_path;

$user->add_lang('find_posting');

$html_filter = $text_filter = $url_filter = array();
/*
	FIND - Custom filters for rss:description/atom:content

	Custom filters use function preg_replace(), with user defined $search, $replace
	and the haystack will be one of text/html/url
	
	param: $(text|html|url)_filter[] = array($search, $replace);

	$text_filter works with rss_filter()
	$url_filter works with fix_url()
	$html_filter works with html2bb()

	Note: To use html filter, enable ACP feed html setting first!
*/
/* --------------------------------
   [DO NOT MODIFY LINES ABOVE!]
   ------------------------------*/


// temp solution for phpbb.com quote
$html_filter[] = array('#</blockquote>#', " <\n\n");
// filter restricted emotional icons from doyouhike.net
$html_filter[] = array('#<img[^>]*(?:src="http://static.doyouhike.net/images/emoticons/).+?\.png.+?/>#is', ' [emotion]');
// strip restricted channel image
$url_filter[] = array('#http://static.doyouhike.net/files/.+?$#is', '');
// filter inline js img tag from doyouhike.net
$html_filter[] = array('#<img\s+class="attach"[^>]*(?:src="http://static.doyouhike.net/files/.+?)/>#is', $user->lang['IMG_RESTRICT']);

// strip off '[dd:dd]$' - mingpao.com
$text_filter[] = array('#(.*?)\[\d\d\:\d\d\]$#', '\\1');


/* --------------------------------
   [DO NOT MODIFY LINES BELOW!]
   ------------------------------*/



/**
* Convert CR to LF, strip extra LFs and spaces, multiple newlines to 2
*
* @param $text
* @param $is_html (booleans), option to preserve html tags
* @return string
*/
function rss_filter($text, $is_html = false, $newline = false)
{
	global $text_filter;

	$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");

	if ($is_html)
	{
		$text = str_replace('&nbsp;', ' ', $text);
		$text = str_replace('&#32', ' ', $text);
	}
	else
	{
		$text = strip_tags($text);

		// Apply custom filters
		if (!empty($text_filter))
		{
			foreach ($text_filter as $filter)
			{
				$text = preg_replace($filter[0], $filter[1], $text);
			}
		}
	}

	$text = str_replace("\r", "\n", $text);
	$text = preg_replace("/(\p{Zs}|\t)+/u", '\\1', $text);
	$text = preg_replace("#(^|\n)(?:\p{Zs}|\t)+?#u", '\\1', $text);
	$text = ($newline) ? preg_replace("/\n{3,}/", "\n\n", $text) : str_replace("\n", '', $text);

	return trim($text);
}



/**
*	URL filter
*/
function fix_url($url)
{
	global $url_filter;
	
	$url = htmlspecialchars_decode($url);
	
	// validate url is prefixed with (ht|f)tp(s)?
	if (!preg_match('#^https?://(.*?\.)*?[a-z0-9\-]+\.[a-z]{2,4}#i', $url))
	{
		return;
	}

	// apply custom filters
	if (!empty($url_filter))
	{
		foreach ($url_filter as $filter)
		{
			$url = preg_replace($filter[0], $filter[1], $url);
		}
	}

	return trim($url);
}


/**
* Fix CJK full-width punct-alpnum spacing
*
* utf8 ncr values for CJK full-width symbols:
*	12288 - 12290, 12298 - 12318
*	65281 - 65312
*	65313 - 65338		excludes english capital letters
*	65339 - 65344
*	65345 - 65370		excludes english letters
*	65371 - 65377
*	65504 - 65510
*
*	HK font				37032, 24419, 22487
*/
function cjk_tidy($text)
{
	// decode first!
	$text = utf8_decode_ncr($text);

	// Preserve space around [] , for posting with bbcode tags
	$text = preg_replace('#\] +([[:punct:]])#', ']&#32;\\1', $text);
	$text = preg_replace('#([[:punct:]]) +\[#', '\\1&#32;[', $text);
	// encolsed words with spaces
	$text = preg_replace('#([[:alnum:][:punct:]\-\+]+)#', '&#32;\\1&#32;', $text);

	$text = utf8_encode_ncr($text);

	$text = preg_replace('/(?:(&#[0-9]{5};)(\w+)|(\w+)(&#[0-9]{5};))/', '\\1 \\2', $text);
	$text = preg_replace('/\]&#32;(&#[0-9]{5};)/', ']\\1', $text);
	$text = preg_replace('/(&#[0-9]{5};)&#32;\[/', '\\1[', $text);
	//FIXME:  trim full-width spaces
	//$text = preg_replace('/^\p{Zs}+/u', '', $text);	// not works
	//$text = preg_replace('/\p{Zs}+$/u', '', $text);

	// restore space
	$text = str_replace('&#32;', ' ', $text);

	// process spacings
	$val = 12287;
	while ($val <= 12318)
	{
		$val++;
		if ($val > 12290 && $val < 12298)
		{
			continue;
		}

		$text = preg_replace(array("/(&#$val;) +/", "/ +(&#$val;)/"), '\\1', $text);
	}

	$val = 65280;
	while ($val <= 65378)
	{
		$val++;
		if ( ($val > 65312 && $val < 65339) || ($val > 65344 && $val < 65371) )
		{
			// skip Full-width letters and part not in range
			continue;
		}

		$text = preg_replace(array("/(&#$val;) +/", "/ +(&#$val;)/"), '\\1', $text);
	}

	$val = 65503;
	while ($val <= 65510)
	{
		$val++;

		$text = preg_replace(array("/(&#$val;) +/", "/ +(&#$val;)/"), '\\1', $text);
	}

	$text = utf8_decode_ncr($text);

	return $text;
}


?>
