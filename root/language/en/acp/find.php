<?php
/**
*
* @author PoPoutdoor
*
* @package FIND
* @version $Id:$
* @copyright (c) 2008-2013 PoPoutdoor
* @license http://opensource.org/licenses/GPL-2.0
*
* @language English [en]
* @translator (c) ( PoPoutdoor )
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
	'NO_PHP_SUPPORT'	=> 'Installed PHP does not have <strong>SimpleXML</strong> support or <strong>allow_url_fopen</strong> activated!<br />This Mod needs both to work.',
	'MOD_INFO'			=> '<br /><br /><div align="center" /><p class="small">FIND version 1.1.0 (BD3) by <a href="https://www.phpbb.com/community/memberlist.php?mode=viewprofile&un=PoPoutdoor">PoPoutdoor</a> &copy; 2008-2013</p></div>',
	// action messages
	'ACTIVATE_FEED'	=> 'Enable Feed:<br /><br />%s',
	'DEACTIVATE_FEED'	=> 'Disable Feed:<br /><br />%s',
	'DELETE_FEED'		=> 'Delete Feed:<br /><br />%s<br /><br />%s',
	'IMPORT_ERR'		=> "\n" . 'Feed with error: ',
	'IMPORT_OK'			=> 'Feed fetch successfully: ',
	'IMPORT_SKIP'		=> "\n" . 'Feed without updates: ',
	'NO_FEED'			=> 'You have to select at least one feed!',
	'NONE'				=> "None.\n",
	// add/edit action message
	'CHECK_URL_EXPLAIN'		=> 'Supplied URL will be submitted to <a href="http://validator.w3.org/feed" onclick="window.open(this.href); return false;">validator.w3.org</a> for compliance check.<br /><br /><em>Note</em>: You may add not validated feed after viewing the error page. <br /><br />Do not ask for support if not import properly!',
	'FEEDNAME_NOT_PROVIDED'	=> 'Feed name not provided, please set feed name and enable feed name topic.',
	'FEED_NOT_VALIDATE'		=> '[<strong>Feed is not validated</strong>]<br /><br />click <a href="%s" onclick="window.open(this.href); return false;">HERE</a> to view detected error(s) on valdiation page.<br /><br />FYI: <a href="http://cyber.law.harvard.edu/rss/rss.html" onclick="window.open(this.href); return false;">RSS 2.0 Specification</a>, <a href="http://www.ietf.org/rfc/rfc4287.txt" onclick="window.open(this.href); return false;">Atom 1.0 Specification</a><br /><br />',
	'FEED_VALIDATED'		=> '[<strong>Feed validated</strong>]<br />',
	//'NONE'					=> "None.\n",
	'PROMPT'					=> 'Information<br />',
	'REVIEW_SUBMIT'		=> 'Review settings and click <strong>submit</strong><br />',
	'SELECT_FORUM_BOT'	=> 'Settings auto dectected<br /><br />Please select post Forum/Bot first!<br />',
	// log messages
	'FEED_ADDED'		=> 'Feed added successfully.',
	'FEED_DELETED'		=> 'Feed(s) deleted successfully.',
	'FEED_UPDATED'		=> 'Feed updated successfully.',
	// add/edit form data error
	'NAME_TOO_LONG'	=> 'Feed name too long. Maximum 255 characters!',
	'NAME_TOO_SHORT'	=> 'Feed name too short. Requires 3 or more characters!',
	'NO_FEEDNAME'		=> 'You have to set the feed name!',
	'NO_FEED_URL'		=> 'You have to set the feed URL!',
	'NO_FORUM'			=> 'You have to select a forum!',
	'NO_USER'			=> 'You have to select the posting bot!',
	'URL_NOT_VALID'	=> 'Feed URL is not a valid address!',
	'URL_TOO_LONG'		=> 'Feed URL too long. Maximum 255 characters!',
	'URL_TOO_SHORT'	=> 'Feed URL too short. Requires 12 or more characters!',
	// feed list
	'FEED_ADD'		=> 'Add Feed',
	'FEED_ID'		=> 'ID',
	'FEED_NAME'		=> 'Feed Name',
	'FIND'			=> 'RSS/Atom news feed',
	'FIND_EXPLAIN'	=> 'Delivers RSS/Atom news to selected forum.',
	'IMPORT'			=> 'Import',
	'LAST_UPDATE'	=> 'Last update',
	'NO_ENTRIES'	=> 'No feed. Click <em>Add Feed</em> to add one!' ,
	// add/edit
	'ARTICLE_CAT'				=> 'Category',
	'ARTICLE_CAT_EXPLAIN'	=> 'If supplied, include the feed category information in post.',
	'ARTICLE_HTML_EXPLAIN'	=> 'If set to "Yes", embedded HTML will be converted to supported BBCode.',
	'ARTICLE_HTML'				=> 'HTML support',
	//'FEED_ADD'			=> 'Add Feed',
	'FEED_EDIT'				=> 'Edit Feed',
	'FEED_EDIT_EXPLAIN'	=> 'Set news feed and post properties.',
	'FEED_INFO'				=> 'Feed info',
	'FEED_INFO_EXPLAIN'	=> 'If supplied, include the feed information in post.',
	//'FEED_NAME'			=> 'Feed Name',
	'FEED_NAME_EXPLAIN'	=> 'Name of this feed source(used to identify feeds on ACP, also used as the fallback subject to post)',
	'FEED_NAME_SUBJECT'				=> 'Feed Name as Subject',
	'FEED_NAME_SUBJECT_EXPLAIN'	=> 'If supplied, the subject of post is automatic generated from the source data. Select "Yes" if you want to use <strong>Feed Name</strong> set above as default post subject.',
	'FEED_RECHECK'			=> 'Validate feed &amp; reconfigure properties',
	'FEED_URL'				=> 'Feed URL',
	'FEED_URL_EXPLAIN'	=> 'Enter URL of feed source(min. 12, max. 255 chars).',
	'FILTER'					=> 'Custom Filters',
	'FILTER_DEL'			=> 'Delete entries',
	'FILTER_EXPLAIN'		=> '<strong>You don\'t need to set custom filters for most feed sources.</strong><br /><br />This is for dealing with, for example: protected image, url you want restricted access to, or modify sensitive phrases upon post.<br /><br />Custom filter use PHP <a href="http://www.php.net/manual/en/ref.pcre.php" onclick="window.open(this.href); return false;">PCRE Functions</a>. The longer row for <em>search</em> preg, the shoter row for <em>replace</em> (if applicable).<br />Supported <em>haystacks</em> as listed below:',
	'FILTER_RST'			=> 'Clear entries',
	'HTML_FILTER_EXPLAIN'	=> 'Filter all html data type.<br /><em>Note:</em> Have to enable HTML support first!',
	'HTML_FILTER'				=> 'HTML filter',
	'MAX_ARTICLES'				=> 'Articles to post',
	'MAX_ARTICLES_EXPLAIN'	=> 'The maximum number of articles to be posted for this feed. e.g. Set 10 to post the latest 10 articles, 0 to post ALL articles from feed.',
	'MAX_CONTENTS'				=> 'Content Limits',
	'MAX_CONTENTS_EXPLAIN'	=> 'The maximum number of characters allowed for each article. Set 300 to post a few lines, 0 to post full contents provided.',
	'NEWPOST_MODE'				=> 'Post Mode',
	'POST_BOT'					=> 'Select Post Bot',
	'POST_FORUM'				=> 'Select Post Forum',
	'POST_FORUM_BOT'				=> 'Post Forum/Bot',
	'POST_FORUM_BOT_EXPLAIN'	=> 'Select posting forum and posting bot for this news source.',
	'POST_MODE'				=> 'New topic intervals',
	'POST_MODE_EXPLAIN'	=> 'Set intervals in calendar days or month to post as new topic.<br /><em>Note:</em> If you select <strong>Article</strong>, each article will be posted as new topic, and setting of <strong>Feed Name as Subject</strong> will be ignored!',
	'SRC_FILTER'			=> 'Source filter',
	'SRC_FILTER_EXPLAIN'	=> 'Skip particular source contents.<br /><em>Note:</em> This filter is for RSS only.',
	'TEXT_FILTER_EXPLAIN'	=> 'Filter all text data type.<br /><em>Note:</em> Please try phpBB\'s <strong>Word censoring</strong> feature first!',
	'TEXT_FILTER'				=> 'Text filter',
	'TOPIC_ARTICLE'	=> 'Article',
	'TOPIC_DAILY'		=> 'Daily',
	'TOPIC_MONTHLY'	=> 'Monthly',
	'TOPIC_WEEKLY'		=> 'Weekly',
	'URL_FILTER_EXPLAIN'	=> 'Filter all URLs data type.<br /><em>Note:</em> May need to enable HTML support.',
	'URL_FILTER'			=> 'URL filter',
));

?>
