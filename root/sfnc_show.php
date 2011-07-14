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

/* This page will be called with AJAX 
 * 
 * TODO :
 * - will return data for feed
 * - if it is in forum :
 *     - check user permissions, if he has the permissions for viewing that forum
 *     - check if exists any feed assigned to this feed
 *	   - this forum might be also disabled for viewing the feed
 *	   - else show one random feed data
 */

// Start session management
$user->session_begin();
$auth->acl($user->data);

// possibly only a dev file !
$sfnc = new sfnc();

$feed_id = 1; // dev hardcoded setup ... TODO request_var(...
$feed_data = '';
$feed_data = $sfnc->show_feed($feed_id);

?>