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
	'ACTIVATE_FEED'	=> '啟用來源：<br /><br />%s',
	'DEACTIVATE_FEED'	=> '停用來源：<br /><br />%s',
	'DELETE_FEED'		=> '<br />刪除來源：<br /><br />%s<br /><br />%s',
	'FEED_ADDED'		=> '已經加入新聞來源。',
	'FEED_DELETED'		=> '新聞來源已經刪除。',
	'FEED_UPDATED'		=> '新聞來源已經更新。',
	'IMPORT_ERR'		=> "\n" . '引進錯誤： ',
	'IMPORT_OK'			=> '引進成功：',
	'IMPORT_SKIP'		=> "\n" . '沒有更新： ',
	'NO_FEED'			=> '您必須選擇至少一個來源。',
	'NONE'				=> "沒有。\n",

	// form messages
	'IMPORT'				=> '引進',
	'POST_BOT'			=> '選擇發佈機器人',
	'POST_FORUM'		=> '選擇發佈版面',
	'TOPIC_DAILY'		=> '每天',
	'TOPIC_ITEM'		=> '文章',
	'TOPIC_MONTHLY'	=> '每月',
	'TOPIC_WEEKLY'		=> '每週',

	// form error
	'NAME_TOO_LONG'	=> '新聞來源名稱太長：至多 255 個字符！',
	'NAME_TOO_SHORT'	=> '新聞來源名稱太短：至少需要 3 個字符！',
	'NO_FEED_URL'		=> '你必需設定新聞來源地址！',
	'NO_FEEDNAME'		=> '你必需設定新聞來源名稱！',
	'NO_FORUM'			=> '你必需選擇新聞發佈版面！',
	'NO_USER'			=> '你必需選擇新聞發佈人！',
	'URL_NOT_VALID'	=> '新聞來源地址輸入錯誤！',
	'URL_TOO_LONG'		=> '新聞來源地址太長：至多 255 個字符！',
	'URL_TOO_SHORT'	=> '新聞來源地址太短：至少需要 12 個字符！',

	// feed list
	'ADD_FEED'		=> '新增來源',
	'FEED_ID'		=> 'ID',
	'FIND'			=> 'RSS/Atom 新聞來源',
	'FIND_EXPLAIN'	=> '把新聞發佈到指定的論壇',
	'NO_ENTRIES'	=> '沒有設定新聞來源，請點擊‘新增來源’來設定一個！',
	'FEED_CHECK'	=> '驗證',
	'LAST_POST'		=> '最後發佈更新',

	// add/edit form
	'CHECK_URL_EXPLAIN'	=> '<br />輸入的地址將會發送到 <a href="validator.w3.org">validator.w3.org</a> 檢測是否符合規格。<em>注意</em>：你可以在檢視錯誤頁面之後加入未能通過檢測的地址。如果新聞沒有正常發佈，不要尋求支援！',
	'EDIT_FEED'				=> '編輯來源',
	'EDIT_FEED_EXPLAIN'	=> '設定定新聞來源地址及特性。',

	'FEED_CAT'				=> '分類訊息',
	'FEED_CAT_EXPLAIN'	=> '設定是否在文章顯示分類訊息（如果來源包含）。',
	'FEED_INFO'				=> '來源訊息',
	'FEED_INFO_EXPLAIN'	=> '設定是否在文章顯示來源訊息（如果來源包含）。',
	'FEED_HTML'				=> 'HTML 支援',
	'FEED_HTML_EXPLAIN'	=> '如果啟用，來源包含的 HTML 就會在發表的文章中轉換成支援的 BBCode。',
	'FEED_NAME'				=> '新聞來源名稱',
	'FEED_NAME_EXPLAIN'	=> 'ACP 顯示的新聞來源名稱（若引進時來源沒提供主題，所發佈文章的主題自動使用這個名稱。',
	'FEED_URL'				=> '新聞來源地址',
	'FEED_URL_EXPLAIN'	=> '輸入新聞來源地址 (12-255 字符)。',
	'FEED_NOT_VALIDATE'			=> '[新聞來源未能通過檢測]<br />點擊 <a href="%s" onclick="window.open(this.href); return false;">這裡</a> 查看檢測頁上發現的問題。<br /><br />',
	'FEED_VALIDATED'				=> '[<strong>新聞來源通過檢測</strong>]',
	'FEEDNAME_TOPIC'				=> '新聞來源名稱做發佈主題',
	'FEEDNAME_TOPIC_EXPLAIN'	=> '如果來源包含主題訊息，發表的主題是自動產生的。如果選擇“是”，預設就會採用上面設定的‘新聞來源名稱’做發佈內容的主題。',
	'FEEDNAME_NOT_PROVIDED'		=> '新聞來源沒有提供名稱，請輸入<b>來源名稱</b>及在<b>來源名稱做主題</b>選擇“是”。',

	'POST_FORUM_BOT'				=> '發佈版面和機器人',
	'POST_FORUM_BOT_EXPLAIN'	=> '選擇這個新聞來源的發佈版面和發佈機器人。',
	'POST_ITEMS'					=> '文章上限',
	'POST_ITEMS_EXPLAIN'			=> '設定發佈的文章上限。例如：設為 10 只會發佈最新的 10 篇文章，設為 0 代表發佈所有的文章。',
	'POST_LIMITS'					=> '內容字數',
	'POST_LIMITS_EXPLAIN'		=> '設定每篇文章的字數上限。設為 300 只會發佈頭幾行的內容，0 是發佈所有提供的內容。',
	'PROMPT'					=> '訊息<br /><br />',
	'SELECT_FORUM_BOT'	=> '已自動生成發佈設定。請選擇發佈版面和機器人，檢示後再發送。',

	'TOPIC_MODE'			=> '發佈模式',
	'TOPIC_TTL'				=> '新主題時限',
	'TOPIC_TTL_EXPLAIN'	=> '設定發佈新主題的期限。<br /><em>注意：</em>選擇“文章”表示永遠以文章標題作為新主題發表，同時讓設置 <strong>名稱做主題</strong> 無效！',
));

?>
