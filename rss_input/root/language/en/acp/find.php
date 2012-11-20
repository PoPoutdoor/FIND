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
	'ACTIVATE_FEED'	=> 'Enable Feed:<br /><br />%s',
	'DEACTIVATE_FEED'	=> 'Disable Feed:<br /><br />%s',
	'DELETE_FEED'		=> 'Delete Feed:<br /><br />%s<br /><br />%s',
	'FEED_ADDED'		=> 'Feed added successfully.',
	'FEED_DELETED'		=> 'Feed(s) deleted successfully.',
	'FEED_UPDATED'		=> 'Feed updated successfully.',
	'IMPORT_OK'			=> 'Feed fetch successfully: ',
	'IMPORT_SKIP'		=> "\n" . 'Feed without updates: ',
	'IMPORT_ERR'		=> "\n" . 'Feed with error: ',
	'NO_FEED'			=> 'You have to select at least one feed!',
	'NONE'				=> "None.\n",

	// form messages
	'IMPORT'				=> 'Import now',
	'POST_BOT'			=> 'Select Post Bot',
	'POST_FORUM'		=> 'Select Post Forum',
	'TOPIC_DAILY'		=> 'Daily',
	'TOPIC_ITEM'		=> 'Item',
	'TOPIC_MONTHLY'	=> 'Monthly',
	'TOPIC_WEEKLY'		=> 'Weekly',

	// form error
	'ENCODE_TOO_LONG'	=> 'Feed encoding string too long. Maximum 32 characters!',
	'NAME_TOO_LONG'	=> 'Feed name too long. Maximum 255 characters!',
	'NAME_TOO_SHORT'	=> 'Feed name too short. Requires 3 or more characters!',
	'NO_FEEDNAME'		=> 'You have to set the feed name!',
	'NO_FEED_URL'		=> 'You have to set the feed URL!',
	'NO_FORUM'			=> 'You have to select a forum!',
	'NO_USER'			=> 'You have to select the posting bot!',
	'URL_NOT_VALID'	=> 'Feed URL is not a valid address!',
	'URL_TOO_LONG'		=> 'Feed URL too long. Maximum 255 characters!',
	'URL_TOO_SHORT'	=> 'Feed URL too short. Requires 12 or more characters!',

	// main form
	'ADD_FEED'		=> 'Add Feed',
	'FEED_ID'		=> 'ID',
	'FEEDNAME'		=> 'Feed Name',
	'FIND'			=> 'RSS/Atom news Import',
	'FIND_EXPLAIN'	=> 'Import RSS/Atom news and auto posted to selected forum.',
	'NO_ENTRIES'	=> 'No feed. Click <em>Add Feed</em> to add one!' ,

	// add/edit form
	'EDIT_FEED'				=> 'Edit Feed',
	'EDIT_FEED_EXPLAIN'	=> 'The form below will allow you to customise the importing properties of this xml news feed.',

	'FEED_CAT'					=> 'Category info',
	'FEED_CAT_EXPLAIN'		=> 'If supplied, include the feed category information in post.',
	'FEED_CHANNEL'				=> 'Channel info',
	'FEED_CHANNEL_EXPLAIN'	=> 'If supplied, include the feed channel information in post.',
	'FEED_HTML'					=> 'HTML support',
	'FEED_HTML_EXPLAIN'		=> 'If set to "Yes", embedded HTML will be converted to supported BBCode.',
	'FEED_NAME'					=> 'Feed Name',
	'FEED_NAME_EXPLAIN'		=> 'Name of this feed. Used in ACP to identify feeds. Will be used as the fallback subject name of imported news post',
	'FEED_RECODE'				=> 'Force Encoding',
	'FEED_RECODE_EXPLAIN'	=> 'Leave blank unless posted text looks like corrupted!<br />This setting intended to fix incorrect/missing encoding info.',
	'FEED_URL'					=> 'Feed URL',
	'FEED_URL_EXPLAIN'		=> 'Validate <a href="http://www.feedvalidator.org/" onclick="window.open(this.href); return false"><em>here</em></a> first!<br />Enter the URL for the news feed (min. 12, max. 255 chars).',
	'FEEDNAME_TOPIC'				=> 'Feed Name as Subject',
	'FEEDNAME_TOPIC_EXPLAIN'	=> 'If supplied, the subject of post is automatic generated from the source channel information. Select "Yes" if you want to use <strong>Feed Name</strong> as the post subject.',

	'POST_FORUM_BOT'				=> 'Post Forum/Bot',
	'POST_FORUM_BOT_EXPLAIN'	=> 'Select posting forum and posting bot for the imported news.',
	'POST_ITEMS'					=> 'Items to post',
	'POST_ITEMS_EXPLAIN'			=> 'The number of articles to be posted for this feed. e.g. Set 5 to post the latest 5 articles, 0 to post every article from feed.',
	'POST_LIMITS'					=> 'Characters to post',
	'POST_LIMITS_EXPLAIN'		=> 'Limit the number of characters posted for each article. Set 300 to post a few lines, 0 to post full contents provided.',

	'TOPIC_MODE'			=> 'Post Mode',
	'TOPIC_TTL'				=> 'New topic intervals',
	'TOPIC_TTL_EXPLAIN'	=> 'Set intervals in calendar days or month to post as new topic.<br /><em>Note:</em> If you select <strong>Item</strong>, each item of the feed will be posted as new topic, and the <strong>Feed Name as Subject</strong> settings below will be ignored!',
));

?>
