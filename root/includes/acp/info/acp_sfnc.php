<?php

/**
 *
 * @package sfnc
 * @version $Id: $
 * @copyright (c) 2009-2011 Jiri Smika (Smix) http://phpbb3.smika.net
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @package module_install
 */
class acp_sfnc_info
{

	function module()
	{
		return array(
			'filename' => 'sfnc',
			'title' => 'ACP_SFNC',
			'version' => '0.4.1',
			'modes' => array(
				'manage' => array(
					'title' => 'ACP_SFNC_FEEDS',
					'auth' => 'acl_a_board',
					'cat' => array('ACP_SFNC')),
				'config' => array(
					'title' => 'ACP_SFNC_GENERALCONFIG',
					'auth' => 'acl_a_board',
					'cat' => array('ACP_SFNC')
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