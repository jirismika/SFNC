<?php
/**
*
* @package contrib
* @version $Id:
* @copyright (c) 2007 Jiri Smika (Smix) http://phpbb3.smika.net
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();

$sql_array = array();
// this release version info
$version = '0.3.4';

// only admin can install/update this mod - nobody else can run this script
if(!($user->data['user_type']) == USER_FOUNDER)
{
	trigger_error('Founder permissions are required to run this script.',  E_USER_ERROR);
}

// check if constants exists
if (!defined('SMIXMODS_FEED_NEWS_CENTER_FEEDS'))
{
	trigger_error('You\'ve didn\'t make all necessary file edits, check it again please.', E_USER_ERROR);
}

// is sfnc already installed ?
if (isset($config['sfnc_version']))
{
	// sfnc is already installed
	// version check
	if ($version == $config['sfnc_version'])
	{
		// this version is already installed - do nothing
		trigger_error('<span style="color:darkorange; font-weight:bold;">This version is already installed</span>');
	}
	elseif ($version > $config['sfnc_version'])
	{
		if ($config['sfnc_version'] < '0.3.2')
		{
			$sql_array[] = 'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_download_function\', \'simplexml\', \'0\');';
		}
		
		$sql_array[] = 'UPDATE ' . CONFIG_TABLE . ' SET config_value = "'.$version.'" WHERE config_name = "sfnc_version"';
	}
	// weird ? :-D ... do nothing
	elseif ($version < $config['sfnc_version'])
	{
	}
}
else
{
	// INSTALL latest version
	// If you have problems with this install script, insert this into db manually
	$sql_array = array(
		'DROP TABLE IF EXISTS ' . SMIXMODS_FEED_NEWS_CENTER_FEEDS,
		'CREATE TABLE ' . SMIXMODS_FEED_NEWS_CENTER_FEEDS . ' (
			id int(11) NOT NULL AUTO_INCREMENT,
			feed_name varchar(255) NOT NULL DEFAULT \'\',
			url varchar(255) NOT NULL DEFAULT \'\',
			feed_type varchar(10) NOT NULL DEFAULT \'\',
			next_update varchar(10) NOT NULL DEFAULT \'0\',
			last_update int(10) NOT NULL DEFAULT \'0\',
			available_feed_atributes varchar(255) NOT NULL DEFAULT \'\',
			available_item_atributes varchar(255) NOT NULL DEFAULT \'\',
			encoding varchar(255) NOT NULL DEFAULT \'\',
			refresh_after varchar(5) COLLATE utf8_bin NOT NULL DEFAULT \'3600\',
			template_for_displaying varchar(255) NOT NULL DEFAULT \'\',
			template_for_posting varchar(255) NOT NULL DEFAULT \'\',
			poster_id int(5) NOT NULL DEFAULT \'0\',
			poster_forum_destination_id int(5) NOT NULL DEFAULT \'0\',
			poster_topic_destination_id int(5) NOT NULL DEFAULT \'0\',
			posting_limit int(2) NOT NULL DEFAULT \'1\',
			enabled_posting int(1) NOT NULL DEFAULT \'0\',
			enabled_displaying int(1) NOT NULL DEFAULT \'0\',
			PRIMARY KEY (id)
		);',

		// I've decided to skip the default feed, because the settings can be faulty ...
//		'INSERT INTO ' . SMIXMODS_FEED_NEWS_CENTER_FEEDS . ' (id, feed_name, url, feed_type, next_update, last_update, available_feed_atributes, available_item_atributes, encoding, refresh_after, template_for_displaying, template_for_posting, poster_id, poster_forum_destination_id, poster_topic_destination_id, posting_limit, enabled_posting, enabled_displaying) VALUES (NULL, \'phpBB.com\', \'http://www.phpbb.com/community/feed.php?mode=news\', \'atom\', \'0\', \'0\', \'\', \'\', \'utf-8\', \'7200\', \'\', \'\', \'2\', \'2\', \'0\', \'5\', \'1\', \'0\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_download_function\', \'simplexml\', \'0\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_cron_init\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_cron_posting\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_index_init\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_index_posting\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_version\', \''.$version.'\', \'0\');',
	);
}

// DO THE INSTALL OR UPDATE
if ($sql_array)
{
	foreach ($sql_array as $sql)
	{
		if(!$db->sql_query($sql))
		{
			trigger_error('ERROR during SQL query : ' . $sql, E_USER_ERROR);
		}
		// refresh cache
		$cache->destroy('config');
	}
	trigger_error('<span style="color:green; font-weight:bold;">Installation of version '.$version.' was successfull</span>');
}
else
{
	trigger_error('Nothing to change in db', E_USER_ERROR);
}

?>