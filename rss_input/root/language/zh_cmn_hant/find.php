<?php
/**
*
* @author PoPoutdoor
*
* @package RSS_input
* @version $Id:$
* @copyright (c) 2008-2011 PoPoutdoor
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* @language Traditional Chinese [zh_cmn_hant]
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'BOT_NOT_ACTIVE'	=> '發佈機器人 [%s] 已被停用！',
	'CLOCK_ISSUE'		=> '[%s] 時鐘偏差問題：兩者時差超過 30 秒！可能是來源時間標記存在錯誤，或伺服器時鐘出現偏差。',
	'CURL_ERR'			=> '[%s] 從 %s 讀取資料失敗！HTTP 回應 %s：%s',
	'FILE_NULL'			=> '[%s] 來源 %s 傳回空白檔案！',
	'FOPEN_ERR'			=> '[%s] 從 %s 讀取資料失敗！',
	'NO_CHANNEL'		=> '[%s] 來源不符合標準：頻道訊息缺失！',
	'NO_ENCODINGS'		=> '[%s] 沒有編碼訊息。在 強制編碼 設定 “UTF-8” 後可能解決這來源方面的問題。',
	'NO_IDS'				=> '沒有提供新聞 id。',
	'NO_ITEM_INFO'		=> '[%s] 來源不符合標準：項目及內容訊息缺失！來源應該至少提供其中一個訊息.',
	'NO_ITEMS'			=> '[%s] 沒有新聞項目。可能是在讀取來源過程出現錯誤。',
	'NO_PHP_SUPPORT'	=> '網站 php 沒有安裝 <strong>cURl</strong> 程式庫 或 沒有啟用 <strong>allow_url_fopen</strong>！<br />這個外掛需要使用當中一個。',
	'NO_XML_TAG'		=> '[%s] 已經設定強制編碼來解決 xml 標簽缺失問題。',
	'NOT_ACTIVE'		=> '[%s] 已停用！',
	'NOT_VALID_XML'	=> '[%s] 來源不符合標準：xml 標簽缺失或來源不是 rss+xml 文件. 在強制編碼設定 "UTF-8" 可能解決這個問題。',

	// acp import results
	'FEED_ERR'		=> '[%s] XML 錯誤： %s，行 %d 列 %d 字節 %d。',
	'FEED_NONE'		=> '[%s] 沒有更新。',
	'FEED_OK'		=> '[%s] 有 %d 篇新文章。',
	'FEED_SKIP'		=> '[%s] 有 %d 篇過時文章。',

	# rss_import messages
	'HACK_ATTEMPT'	=> '偵測到可疑入侵。' . "\n",
	'IMPORT_ERR'	=> "\n" . '引進錯誤： ',
	'NO_PARAMETER'	=> '沒有參數。輸入為： [%s]' . "\n",
	'PARM_ERR'		=> '參數必需是數字符。輸入為： [%s]' . "\n" . '範例： %s?feed=[1,2,...,999 | 999]' . "\n",
));

?>
