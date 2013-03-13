<?php
/**
 *
 * @package smixmods_feed_news_center
 * @version $Id: $
 * @copyright (c) 2009-2011 Jiri Smika (Smix) http://phpbb3.smika.net
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
* @package module_install
*/
class acp_smixmods_feed_news_center_info
{
    function module()
    {
		return array(
			'filename'	=> 'smixmods_feed_news_center',
			'title'		=> 'ACP_SMIXMODS_FEED_NEWS_CENTER',
			'version'	=> '0.3.5',
			'modes'		=> array(
				'manage'	=> array(
					'title' => 'ACP_SMIXMODS_FEED_NEWS_CENTER_FEEDS',
					'auth'	=> 'acl_a_board',
					'cat'	=> array('ACP_SMIXMODS_FEED_NEWS_CENTER')),
				'config'	=> array(
					'title'	=> 'ACP_GENERAL_CONFIGURATION',
					'auth'	=> 'acl_a_board',
					'cat'	=> array('ACP_SMIXMODS_FEED_NEWS_CENTER')
				),
            ),
        );

    }

    function install()
    {
    }

    function uninstall()
    {
    }

}
?>