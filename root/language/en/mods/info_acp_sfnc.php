<?php

/**
 * SmiX.MODs Feed News Center [English]
 *
 * @package sfnc
 * @version $Id: $
 * @copyright (c) 2009-2011 Jiri Smika (Smix) http://phpbb3.smika.net
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */
/**
 * DO NOT CHANGE
 */
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
	// titles
	'ACP_SFNC' => 'SmiX.MODs Feed News Center',
	'ACP_SFNC_FEEDS' => 'Feeds list',
	'ACP_SFNC_FEEDS_DESCRIPTION' => 'There you can choose a feed to edit, delete or add a new one.',
	'ACP_SFNC_ADD_NEW' => 'ADD new feed',
	'ACP_SFNC_AVAILABLE_ATRIBUTES' => 'Available atributes',
	'ACP_SFNC_BASIC' => 'Basic settings',
	'ACP_SFNC_DOWNLOAD_FUNCTION' => 'Download function',
	'ACP_SFNC_DOWNLOAD_FUNCTION_DESCRIPTION' => 'Which function is used to download the feed? (simplexml recommended)',
	'ACP_SFNC_ENCODING' => 'Encoding',
	'ACP_SFNC_ENCODING_DESCRIPTION' => 'Encoding of the feed',
	'ACP_SFNC_FEED_ENABLED_POSTING_SHORT' => 'Posting',
	'ACP_SFNC_FEED_ENABLED_POSTING' => 'Post content of the feed',
	'ACP_SFNC_FEED_ENABLED_POSTING_DESCRIPTION' => 'If enabled, downloaded content will be posted to board',
	'ACP_SFNC_FEED_ENABLED_DISPLAYING_SHORT' => 'Displaying',
	'ACP_SFNC_FEED_ENABLED_DISPLAYING' => 'Displaying feed on the board',
	'ACP_SFNC_FEED_ENABLED_DISPLAYING_DESCRIPTION' => 'If enabled, cached content will be displayed in news ticker',
	'ACP_SFNC_FEED_NAME' => 'Name',
	'ACP_SFNC_FEED_NAME_DESCRIPTION' => 'Name of the feed',
	'ACP_SFNC_FEED_TYPE' => 'Type',
	'ACP_SFNC_FEED_TYPE_DESCRIPTION' => 'Type of the feed (ATOM/RSS/RDF)',
	'ACP_SFNC_FEED_URL' => 'URL',
	'ACP_SFNC_FEED_URL_DESCRIPTION' => 'URL of the feed',
	'ACP_SFNC_FEEDS_UPD_BEFORE_HOUR' => 'checked %s hours ago',
	'ACP_SFNC_FEEDS_UPD_NEVER' => 'never',
	'ACP_SFNC_LAST_UPDATE' => 'Latest update',
	'ACP_SFNC_LAST_UPDATE_DESCRIPTION' => 'Time, when feed was updated',
	'ACP_SFNC_NEWS_TICKER' => 'News ticker',
	'ACP_SFNC_POSTING' => 'Posting',
	'ACP_SFNC_POSTER_ID' => 'Poster',
	'ACP_SFNC_POSTER_ID_DESCRIPTION' => 'User ID, who will be a poster of the message',
	'ACP_SFNC_POSTER_FORUM_DESTINATION_ID' => 'Forum ID',
	'ACP_SFNC_POSTER_FORUM_DESTINATION_ID_DESCRIPTION' => 'Id of the forum to be posted in',
	'ACP_SFNC_POSTER_TOPIC_DESTINATION_ID' => 'Topic ID',
	'ACP_SFNC_POSTER_TOPIC_DESTINATION_ID_DESCRIPTION' => 'Id of the topic to be posted in',
	'ACP_SFNC_POSTING_LIMIT' => 'Messages limit',
	'ACP_SFNC_POSTING_LIMIT_DESCRIPTION' => 'How many latest messages may be checked and posted',
	'ACP_SFNC_REFRESH_AFTER' => 'Check feed after',
	'ACP_SFNC_REFRESH_AFTER_DESCRIPTION' => 'After how much time, the feed might be checked for a new content again? (Note : If using CRON mode, all feeds are checked while CRON is inited)',
	'ACP_SFNC_REFRESH_AFTER_HOURS' => 'hours',
	'ACP_SFNC_REFRESH_AFTER_MINUTES' => 'minutes',
	// templates
	'ACP_SFNC_TEMPLATES' => 'Templates',
	'ACP_SFNC_TEMPLATES_DESCRIPTION' => 'There you can edit templates used for posting or displaying on your board',
	'ACP_SFNC_TEMPLATE_FOR_POSTING' => 'Post template',
	'ACP_SFNC_TEMPLATE_FOR_POSTING_DESCRIPTION' => 'Template used for post, if posting is enabled',
	'ACP_SFNC_TEMPLATE_FOR_DISPLAYING' => 'Display template',
	'ACP_SFNC_TEMPLATE_FOR_DISPLAYING_DESCRIPTION' => 'Template used for news ticker, if displaying is enabled',
	// log mesages
	'LOG_ERROR_SFNC_FEED_PARSER_NO_FEED_TYPE' => 'Unable to detect feed type for feed "%s"',
	// messages
	'ACP_SFNC_ACTION_ERROR_DB' => 'There was an error during inserting values',
	'ACP_SFNC_ACTION_SUCCESS' => 'Action was performed successfully',
	'ACP_SFNC_ACTION_ERROR_VALUES' => 'Not enough informations were posted',
	// configuration
	'ACP_SFNC_INDEX_INIT' => 'Index init',
	'ACP_SFNC_INDEX_INIT_DESCRIPTION' => 'If enabled, index.php will init downloading of ONE feed',
	'ACP_SFNC_INDEX_POSTING' => 'Index posting',
	'ACP_SFNC_INDEX_POSTING_DESCRIPTION' => 'If enabled, after initiation on index.php, content will be posted to forum',
	'ACP_SFNC_CRON_INIT' => 'Cron init',
	'ACP_SFNC_CRON_INIT_DESCRIPTION' => 'Enable, if you´ll use a CRON job for updating the feeds (update_feeds.php in phpBB ROOT directory). If enabled, index.php will never init downloading or posting of feed and ALL feeds will be updated.',
	'ACP_SFNC_CRON_POSTING' => 'Cron posting',
	'ACP_SFNC_CRON_POSTING_DESCRIPTION' => 'If enabled, after initiation on cron.php, content will be posted to forum',
	'ACP_SFNC_CRON_POSTING_LIMIT' => 'Posting limit',
	'ACP_SFNC_CRON_POSTING_LIMIT_DESCRIPTION' => 'How many items should be checked for posting',
));
?>