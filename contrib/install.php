<?php

/**
 *
 * @package contrib
 * @version $Id:
 * @copyright (c) 2009-2013 Jiri Smika (Smix) http://phpbb3.smika.net
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
$version = '0.4.0c';

define ('SFNC_FEEDS_OLD', $table_prefix . 'smixmods_feed_news_center');

// determine the best possible function
if (function_exists('simplexml_load_file'))
{
	$default_function = 'simplexml';
}
elseif (function_exists('curl_init'))
{
	$default_function = 'curl';
}
elseif (function_exists('fopen'))
{
	$default_function = 'fopen';
}
else
{
    // TODO add lang file string
    trigger_error('No usable PHP function found - simplexml, cURL or fopen is required to run this .MOD and none was d', E_USER_ERROR);
}


// only admin can install/update this mod - nobody else can run this script
if (!($user->data['user_type']) == USER_FOUNDER)
{
    trigger_error('Founder permissions are required to run this script.', E_USER_ERROR);
}

// check if constants exists
if (!defined('SFNC_FEEDS'))
{
    trigger_error('Please follow all install instructions from install_mod.xml file and run this install script again please.', E_USER_ERROR);
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
			$sql_array[] = 'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_download_function\', \''.$default_function.'\', \'0\');';
		}

		if ($config['sfnc_version'] < '0.4.0a')
		{
			$sql_array[] =	'RENAME TABLE ' . SFNC_FEEDS_OLD . ' TO ' . SFNC_FEEDS;
			$sql_array[] =	'ALTER TABLE ' . SFNC_FEEDS . '
						CHANGE available_feed_atributes available_feed_attributes varchar(255) NOT NULL DEFAULT \'\' AFTER last_update,
						CHANGE available_item_atributes available_item_attributes varchar(255) NOT NULL DEFAULT \'\' AFTER available_feed_attributes;';
		}


		$sql_array[] = 'UPDATE ' . CONFIG_TABLE . ' SET config_value = "' . $version . '" WHERE config_name = "sfnc_version"';
	}
	elseif ($version < $config['sfnc_version'])
	{
		// weird ? :-D ... do nothing
	}
}
else
{
	// INSTALL latest version
	// If you have problems with this install script, insert this into db manually
	$sql_array = array(
		'DROP TABLE IF EXISTS ' . SFNC_FEEDS,
		'CREATE TABLE ' . SFNC_FEEDS . ' (
			id int(11) NOT NULL AUTO_INCREMENT,
			feed_name varchar(255) NOT NULL DEFAULT \'\',
			url varchar(255) NOT NULL DEFAULT \'\',
			feed_type varchar(10) NOT NULL DEFAULT \'\',
			next_update varchar(10) NOT NULL DEFAULT \'0\',
			last_update int(10) NOT NULL DEFAULT \'0\',
			available_feed_attributes varchar(255) NOT NULL DEFAULT \'\',
			available_item_attributes varchar(255) NOT NULL DEFAULT \'\',
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
		// I've decided to skip the default feed, because the settings can be faulty on not vanilla boards ...
//		'INSERT INTO ' . SFNC_FEEDS . ' (id, feed_name, url, feed_type, next_update, last_update, available_feed_attributes, available_item_attributes, encoding, refresh_after, template_for_displaying, template_for_posting, poster_id, poster_forum_destination_id, poster_topic_destination_id, posting_limit, enabled_posting, enabled_displaying) VALUES (NULL, \'phpBB.com\', \'http://www.phpbb.com/community/feed.php?mode=news\', \'atom\', \'0\', \'0\', \'\', \'\', \'utf-8\', \'7200\', \'\', \'\', \'2\', \'2\', \'0\', \'5\', \'1\', \'0\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_download_function\', \''.$default_function.'\', \'0\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_cron_init\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_cron_posting\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_index_init\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_index_posting\', \'0\', \'1\');',
		'INSERT INTO ' . CONFIG_TABLE . ' VALUES (\'sfnc_version\', \'' . $version . '\', \'0\');',
	);
}

// DO THE INSTALL OR UPDATE
if ($sql_array)
{
	foreach ($sql_array as $sql)
	{
		if (!$db->sql_query($sql))
		{
			trigger_error('ERROR during SQL query : ' . $sql, E_USER_ERROR);
		}
		// refresh cached config
		$cache->destroy('config');
		//$cache->purge();
	}

	trigger_error('<span style="color:green; font-weight:bold;">Installation of version ' . $version . ' was successfull</span><br /><img src="http://phpbb3.smika.net/sfnc.png?install=' . $version . '" alt="SFNC latest version" />');
}
else
{
	trigger_error('Nothing to change in db', E_USER_ERROR);
}
?>
