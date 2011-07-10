<?php
/**
 *
 * @package sfnc
 * @version $Id: $
 * @copyright (c) 2009-2011 Jiri Smika (Smix) http://phpbb3.smika.net
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);

$sfnc_feed_parser = new sfnc_feed_parser();

$sfnc_feed_parser->cron_init();

// TODO lang file ? simple die ? trigger_error ? ...
trigger_error("Page loaded completely.<br>Note : This message doesn't mean, that there wasn't some problems during the run.", E_USER_WARNING);
?>