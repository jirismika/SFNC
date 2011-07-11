<?php

/**
 *
 * @package sfnc
 * @version $Id: $
 * @copyright (c) 2009-2011 Jiri Smika (Smix) http://phpbb3.smika.net
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */
class acp_sfnc
{

	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template;
		global $config, $cache, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		// TODO rework language file - to this one
//		$user->add_lang('mods/sfnc');
		// Set up the page
		$this->tpl_name = 'acp_sfnc';
		$this->page_title = 'ACP_SFNC';

		// prepare global data
		// is some feed chosen ? - get the id
		$id = request_var('id', 0);
		$action = request_var('action', 'list');

		// get some unused index
		$next_id = 0;

		// get all data for all feeds
		$list = array();
		$sql = 'SELECT id, feed_name, url, feed_type,
					next_update, last_update, refresh_after,
					available_feed_atributes, available_item_atributes,
					encoding, enabled_posting, enabled_displaying,
					template_for_displaying, template_for_posting,
					poster_id, poster_forum_destination_id, poster_topic_destination_id, posting_limit
				FROM ' . SFNC_FEEDS . '
				WHERE 1
				ORDER BY id ASC';

		if (!$result = $db->sql_query($sql))
		{
			// TODO lang file
			trigger_error('Could not get list of feeds!' . adm_back_link($this->u_action), E_USER_WARNING);
		}
		else
		{
			while ($row = $db->sql_fetchrow($result))
			{
				$list[$row['id']] = $row;
				$next_id = ($next_id < $row['id']) ? $row['id'] : $next_id;
			}
		}
		// ... this really gets unused index (we don´t need exact id)
		$next_id++;

		$template->assign_vars(array(
			'CHOSEN' => ($id) ? true : false,
			'ACTION' => $mode,
			'SFNC_VERSION' => ($config['sfnc_version']) ? $config['sfnc_version'] : '',
			'U_NEW' => append_sid("{$phpbb_admin_path}index.$phpEx", "i=sfnc&amp;mode=manage&amp;action=new&amp;id=" . $next_id),
			)
		);

		if ($action == 'delete')
		{
			$sql = 'DELETE FROM ' . SFNC_FEEDS . '
					WHERE id = ' . $id;

			if (!($result = $db->sql_query($sql)))
			{
				trigger_error($user->lang['ACP_SFNC_ACTION_ERROR_DB'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			trigger_error($user->lang['ACP_SFNC_ACTION_SUCCESS'] . adm_back_link($this->u_action), E_USER_NOTICE);
		}

		switch ($mode)
		{
			case 'config' :

				if (isset($_POST['submit']))
				{
					// Set up general vars
					$cron_init = request_var('cron_init', 0);
					$cron_posting = request_var('cron_posting', 0);
					$index_init = request_var('index_init', 0);
					$index_posting = request_var('index_posting', 0);
					$download_function = request_var('download_function', 'simplexml');

					// update config
					set_config('sfnc_index_init', ($cron_init) ? 0 : $index_init);  // if cron enabled, no index inits
					set_config('sfnc_index_posting', ($cron_init) ? 0 : $index_posting); // if cron enabled, no index inits
					set_config('sfnc_cron_init', $cron_init);
					set_config('sfnc_cron_posting', $cron_posting);
					set_config('sfnc_download_function', $download_function);

					// refresh cached configuration
					$cache->destroy('config');
					$cache->purge();
					trigger_error($user->lang['ACP_SFNC_ACTION_SUCCESS'] . adm_back_link($this->u_action), E_USER_NOTICE);
				}
				else
				{
					$template->assign_vars(array(
						'INDEX_INIT' => $config['sfnc_index_init'],
						'INDEX_POSTING' => $config['sfnc_index_posting'],
						'CRON_INIT' => $config['sfnc_cron_init'],
						'CRON_POSTING' => $config['sfnc_cron_posting'],
						'DOWNLOAD_FUNCTION' => $config['sfnc_download_function'],
							)
					);
				}

				break;

			// try to download the feed and make setup the feed better
			case 'download' :

				if (!$id)
				{
					// TODO lang string
					trigger_error('No feed selected! ' . adm_back_link($this->u_action), E_USER_NOTICE);
				}

				var_dump($list);

				if (isset($list[$id]))
				{
					var_dump($list[$id]);
				}

				$sfnc = new sfnc_feed_parser();

				// TODO we'll need a new public function - dev_download();
				$sfnc->cron_init();

				die();


				break;

			// manage existing feeds in $list
			case 'manage' :

				if (!$id)
				{
					// feed is not chosen - show list
					foreach ($list as $feed_id => $row)
					{
						$template->assign_block_vars('feed_list_row', array(
							// main info
							'FEED_ID' => $row['id'],
							'FEED_NAME' => $row['feed_name'],
							'ENABLED_POSTING' => $row['enabled_posting'],
							'ENABLED_DISPLAYING' => $row['enabled_displaying'],
							// times
							'LAST_UPDATE' => ($row['last_update']) ? sprintf($user->lang['ACP_SFNC_FEEDS_UPD_BEFORE_HOUR'], round((time() - $row['last_update']) / 3600)) : sprintf($user->lang['ACP_SFNC_FEEDS_UPD_NEVER']),
							// links
							'U_MANAGE' => append_sid("{$phpbb_admin_path}index.$phpEx", "i=sfnc&amp;mode=manage&amp;action=manage&amp;id=" . (int) $row['id']),
							'U_DELETE' => append_sid("{$phpbb_admin_path}index.$phpEx", "i=sfnc&amp;mode=manage&amp;action=delete&amp;id=" . (int) $row['id']),
						));
					}
				}
				else
				{
					// feed IS chosen !
					if (isset($_POST['submit']) && $_POST['submit'])
					{
						$feed_name = htmlspecialchars_decode(utf8_normalize_nfc(request_var('feed_name', '', true)));
						$feed_type = htmlspecialchars_decode(utf8_normalize_nfc(request_var('feed_type', '', true)));
						$feed_url = htmlspecialchars_decode(utf8_normalize_nfc(request_var('feed_url', '', true)));
						$encoding = htmlspecialchars_decode(utf8_normalize_nfc(request_var('encoding', '', true)));
						$template_for_displaying = htmlspecialchars_decode(utf8_normalize_nfc(request_var('template_for_displaying', '', true)));
						$template_for_posting = htmlspecialchars_decode(utf8_normalize_nfc(request_var('template_for_posting', '', true)));
						$enabled_posting = request_var('enabled_posting', 0);
						$enabled_displaying = request_var('enabled_displaying', 0);
						// poster
						$poster_id = request_var('poster_id', 0);
						$poster_forum_destination_id = request_var('poster_forum_destination_id', 0);
						$poster_topic_destination_id = request_var('poster_topic_destination_id', 0);
						// refresh_after
						$refresh_after_hours = request_var('refresh_after_hours', 0) * 3600;
						$refresh_after_minutes = request_var('refresh_after_minutes', 0) * 60;
						$refresh_after = $refresh_after_hours + $refresh_after_minutes;
						// ... if set to 0 hours & 0 minutes - set to default value 1 hour
						$refresh_after = ($refresh_after > 0) ? $refresh_after : 3600;
						$posting_limit = request_var('posting_limit', 1);

						// if we have all informations ... update
						if ($feed_name && $feed_url)
						{
							$sql_ary = array(
								'feed_name' => $feed_name,
								'feed_type' => $feed_type,
								'url' => $feed_url,
								'encoding' => $encoding,
								'enabled_posting' => $enabled_posting,
								'enabled_displaying' => $enabled_displaying,
								'template_for_posting' => $template_for_posting,
								'template_for_displaying' => $template_for_displaying,
								'poster_id' => $poster_id,
								'poster_forum_destination_id' => $poster_forum_destination_id,
								'poster_topic_destination_id' => $poster_topic_destination_id,
								'refresh_after' => $refresh_after,
								'posting_limit' => $posting_limit,
								'next_update' => 0, // forces update
							);

							// prepare SQL for different actions
							if ($action == 'new')
							{
								$sql = 'INSERT INTO ' . SFNC_FEEDS . ' ' . $db->sql_build_array('INSERT', $sql_ary);
							}
							else
							{
								$sql = 'UPDATE ' . SFNC_FEEDS . '
										SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
										WHERE id = ' . $id;
							}

							if (!($result = $db->sql_query($sql)))
							{
								trigger_error($user->lang['ACP_SFNC_ACTION_ERROR_DB'] . adm_back_link($this->u_action), E_USER_WARNING);
							}

							trigger_error($user->lang['ACP_SFNC_ACTION_SUCCESS'] . adm_back_link($this->u_action), E_USER_NOTICE);
						}
						else
						{
							trigger_error($user->lang['ACP_SFNC_ACTION_ERROR_VALUES'] . adm_back_link($this->u_action), E_USER_WARNING);
						}
					}
					else
					{
						// make_forum_select($select_id, $ignore_id, $ignore_acl, $ignore_nonpost, $ignore_emptycat, $only_acl_post, $return_array)
						$forum_list = make_forum_select(false, false, true, true, true, false, true);

						$selected_forum = isset($list[$id]['poster_forum_destination_id']) ? $list[$id]['poster_forum_destination_id'] : 0;

						// TODO rework this with templating
						$forum_list_select_box = '<select id="poster_forum_destination_id" name="poster_forum_destination_id" size="1">';

						foreach ($forum_list as $f_id => $row)
						{
							$forum_list_select_box .= '<option value="' . $f_id . '"' . (($selected_forum == $f_id) ? ' selected="selected"' : '') . ($row['disabled'] ? ' disabled="disabled" class="disabled-option"' : '') . '>' . $row['padding'] . $row['forum_name'] . '</option>';
						}
						$forum_list_select_box .= '</select>';

						$template->assign_vars(array(
							// main info
							'FEED_ID' => $id,
							'FEED_NAME' => isset($list[$id]['feed_name']) ? $list[$id]['feed_name'] : '',
							'FEED_TYPE' => isset($list[$id]['feed_type']) ? $list[$id]['feed_type'] : '',
							'URL' => isset($list[$id]['url']) ? $list[$id]['url'] : '',
							'ENABLED_POSTING' => isset($list[$id]['enabled_posting']) ? $list[$id]['enabled_posting'] : '',
							'ENABLED_DISPLAYING' => isset($list[$id]['enabled_displaying']) ? $list[$id]['enabled_displaying'] : 0,
							'ENCODING' => isset($list[$id]['encoding']) ? strtoupper($list[$id]['encoding']) : '',
							// times
							'NEXT_UPDATE_HOURS' => isset($list[$id]['next_update']) ? ($list[$id]['next_update'] > 3600) ? $list[$id]['next_update'] : $list[$id]['next_update']  : '',
							'NEXT_UPDATE_MINUTES' => isset($list[$id]['next_update']) ? ($list[$id]['next_update'] > 3600) ? $list[$id]['next_update'] : $list[$id]['next_update']  : '',
							'NEXT_UPDATE' => isset($list[$id]['next_update']) ? $list[$id]['next_update'] : '',
							// recount back refresh_after to hours & minutes
							'REFRESH_AFTER_HOURS' => (isset($list[$id]['refresh_after'])) ? ($list[$id]['refresh_after'] < 3600) ? 0 : floor($list[$id]['refresh_after'] / 3600)  : '',
							'REFRESH_AFTER_MINUTES' => (isset($list[$id]['refresh_after'])) ? ($list[$id]['refresh_after'] < 3600) ? 0 : ceil($list[$id]['refresh_after'] % 3600 / 60)  : '',
							// atributes
							'AVAILABLE_FEED_ATRIBUTES' => isset($list[$id]['available_feed_atributes']) ? $list[$id]['available_feed_atributes'] : '',
							'AVAILABLE_ITEM_ATRIBUTES' => isset($list[$id]['available_item_atributes']) ? $list[$id]['available_item_atributes'] : '',
							// templates
							'TEMPLATE_FOR_POSTING' => isset($list[$id]['template_for_posting']) ? $list[$id]['template_for_posting'] : '',
							'TEMPLATE_FOR_DISPLAYING' => isset($list[$id]['template_for_displaying']) ? $list[$id]['template_for_displaying'] : '',
							// poster
							// TODO - choose from user-list (possible AJAX suggestion)
							'POSTER_ID' => isset($list[$id]['poster_id']) ? $list[$id]['poster_id'] : '',
							// TODO - build selectbox from forums - after forum select - build selectbox from topics
							'POSTER_FORUM_DESTINATION_ID' => isset($list[$id]['poster_forum_destination_id']) ? $list[$id]['poster_forum_destination_id'] : '',
							'POSTER_FORUM_DESTINATION_ID_SELECTBOX' => $forum_list_select_box, // TODO REWORK templating
							'POSTER_TOPIC_DESTINATION_ID' => isset($list[$id]['poster_topic_destination_id']) ? $list[$id]['poster_topic_destination_id'] : '',
							'POSTING_LIMIT' => isset($list[$id]['posting_limit']) ? $list[$id]['posting_limit'] : 1,
							// links
							'U_MANAGE' => append_sid("{$phpbb_admin_path}index.$phpEx", "i=sfnc&amp;mode=feeds&amp;action=manage&amp;id=" . $id),
							'U_DELETE' => append_sid("{$phpbb_admin_path}index.$phpEx", "i=sfnc&amp;mode=feeds&amp;action=delete&amp;id=" . $id),
							)
						);
					}
				}

				break;
		}
	}

}

?>