<?php
/**
* @package contrib
* @version $Id:
* @copyright (c) 2009-2013 Jiri Smika (Smix) http://phpbb3.smika.net
* @note umil installer added by Andreas Vandenberghe (sajaki@bbdkp.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('UMIL_AUTO', true);
define('IN_PHPBB', true);
define('ADMIN_START', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();

$error = array();


// Check for required extensions
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
    $error[] = 'No usable PHP function found - simplexml, cURL or fopen is required to run this .MOD and none was found. ';
}

// check if constants file was edited to add smix 
if (!defined('SFNC_FEEDS'))
{
    $error[] = 'Please follow all install instructions from install_mod.xml file and run this install script again please.';
}

//check if installer was launched from correct directory
if (!file_exists($phpbb_root_path . 'install/index.' . $phpEx))
{
    $error[] = 'Warning! Install directory has wrong name. it must be ‘install‘. Please rename it and launch again.';
}

// check if Umil exists
if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	$error[] = 'Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>';
}

if(count($error) > 0)
{
	trigger_error(implode($error, ' <br />'));
}

// We only allow a founder install this MOD
if ($user->data['user_type'] != USER_FOUNDER)
{
	if ($user->data['user_id'] == ANONYMOUS)
	{
		login_box('', 'LOGIN');
	}

	trigger_error('NOT_AUTHORISED', E_USER_WARNING);
}

// The name of the mod to be displayed during installation.
$mod_name = 'sfnc';

/*
* The name of the config variable which will hold the currently installed version
* You do not need to set this yourself, UMIL will handle setting and updating the version itself.
*/
$version_config_name = 'sfnc_version';

/*
* The language file which will be included when installing
* Language entries that should exist in the language file for UMIL (replace $mod_name with the mod's name you set to $mod_name above)
* $mod_name
* 'INSTALL_' . $mod_name
* 'INSTALL_' . $mod_name . '_CONFIRM'
* 'UPDATE_' . $mod_name
* 'UPDATE_' . $mod_name . '_CONFIRM'
* 'UNINSTALL_' . $mod_name
* 'UNINSTALL_' . $mod_name . '_CONFIRM'
*/
$language_file = 'mods/info_acp_sfnc';

/*
* Options to display to the user (this is purely optional, if you do not need the options you do not have to set up this variable at all)
* Uses the acp_board style of outputting information, with some extras (such as the 'default' and 'select_user' options)

$options = array(
	'test_username'	=> array('lang' => 'TEST_USERNAME', 'type' => 'text:40:255', 'explain' => true, 'default' => $user->data['username'], 'select_user' => true),
	'test_boolean'	=> array('lang' => 'TEST_BOOLEAN', 'type' => 'radio:yes_no', 'default' => true),
);
*/

/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(

	// if version 0.4.0c already installed
	'0.4.0c' => array(

		// add new configs
		'config_add' => array(
			array('sfnc_download_function', $default_function, true),
			array('sfnc_cron_init', '0', true),
			array('sfnc_cron_posting', '0', true),
			array('sfnc_index_init', '0', true),
			array('sfnc_index_posting', '0', true),
		), 
			
		'table_add' => array(
				//add the main smixmods table
				array($table_prefix . 'SFNC_FEEDS', array(
						'COLUMNS'				=> array(
								'id'			=> array('UINT', NULL, 'auto_increment'),
								'feed_name'		=> array('VCHAR:255', ''),
								'url'			=> array('VCHAR:255', ''),
								'feed_type'		=> array('VCHAR:10', ''),
								'next_update'	=> array('VCHAR:10', ''),
								'last_update'	=> array('VCHAR:20', ''),
								'available_feed_attributes'	=> array('VCHAR:255', ''),
								'available_item_attributes'	=> array('VCHAR:255', ''),
								'encoding'	=> array('VCHAR:255', ''),
								'refresh_after'	=> array('VCHAR_UNI:5', ''),
								'template_for_displaying'	=> array('VCHAR:255', ''),
								'template_for_posting'	=> array('VCHAR:255', ''),
								'poster_id'	=> array('UINT', 0),
								'poster_forum_destination_id'	=> array('UINT', 0),
								'poster_topic_destination_id'	=> array('UINT', 0),
								'posting_limit'	=> array('USINT', 0),
								'enabled_posting'	=> array('BOOL', 0),
								'enabled_displaying'	=> array('BOOL', 0),
						),
						'PRIMARY_KEY'    => 'id',
				))),
			
		'module_add' => array(
				//hook up the ACP_SFNC module to .MODS
		  		array('acp', 'ACP_CAT_DOT_MODS', 'ACP_SFNC'),
				
				// hook up the modes defined in the language file to the new module
				array('acp', 'ACP_SFNC', array(
	           		'module_basename'	=> 'sfnc',
					'modes'				=> array('manage', 'config')
					),
	           	 )),
			),
		
		
		// ... then do nothing
		'0.4.1' => array(
				
				), 


);

// We include the UMIF Auto file and everything else will be handled automatically.
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);


?>