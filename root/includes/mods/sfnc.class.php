<?php

/**
 *
 * @package sfnc
 * @version $Id: $
 * @copyright (c) 2009-2010 Jiri Smika (Smix) http://phpbb3.smika.net
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */
/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

class sfnc
{

	// config
	private $download_function = 'simplexml';
	// from db
	private $feed_id = 0;
	private $feed_type = '';
	private $feed_name = '';
	private $encoding = 'UTF-8'; // (if encoding is not parsed, try UTF-8)
	private $url = '';
	private $refresh_after = 3600; // time in seconds
	private $next_update = 0;
	private $last_update = 0;
	// downloaded data
	private $data = '';
	// parsed data array => feed items / entries ...
	private $items = array();
	// download settings
	private $enabled_posting = 0;
	private $enabled_displaying = 0;
	private $cron_init = false;  // forces download
	private $cron_posting = false; // post in cron run
	private $index_init = true;  // init on index.php
	private $index_posting = true; // init on index.php
	// some informations
	private $channel_info = array();
	private $available_feed_atributes = array();
	private $available_item_atributes = array();
	// templates
	private $template_for_posting = '';
	private $template_for_displaying = '';
	// posting bot
	private $poster_id = 0;   // 2;
	private $poster_forum_destination_id = 0; // 2;
	private $poster_topic_destination_id = 0; // 0;
	private $posting_limit = 3;  // 3;

	/**
	 * Caches feed items
	 *
	 * @global cache $cache
	 */
	private function cache_store_feed()
	{
		global $cache;

		$cache->_write('sfnc_feed_' . md5($this->url), $this->items, time());

		// update latest_update info
		$this->feed_updated();
	}

	/**
	 * Loads cached feed items
	 *
	 * @global cache $cache
	 * @return array
	 */
	private function cache_load_feed()
	{
		global $cache;

		return $cache->_read('sfnc_feed_' . md5($this->url));
	}

	/**
	 * Adds feed index into array of indexes, if not already added
	 *
	 * @param string $index
	 */
	private function check_feed_atributes($index)
	{
		$available_attributes = ($this->available_feed_atributes) ? array_flip($this->available_feed_atributes) : array();

		if (!isset($available_attributes[$index]))
		{
			$this->available_feed_atributes[] = $index;
		}
	}

	/**
	 * Adds item index into array of indexes, if not already added
	 *
	 * @param string $index
	 */
	private function check_item_atributes($index)
	{
		$available_attributes = ($this->available_item_atributes) ? array_flip($this->available_item_atributes) : array();

		if (!isset($available_attributes[$index]))
		{
			$this->available_item_atributes[] = $index;
		}
	}

	/**
	 * Gets data from URL
	 *
	 * @return xml
	 */
	private function get_file()
	{
		if ($this->download_function == 'simplexml')
		{
			$content = @simplexml_load_file($this->url, 'SimpleXMLElement', LIBXML_NOCDATA);
		}
		elseif ($this->download_function == 'curl')
		{
			$content = $this->get_file_curl($this->url);

			$content = @simplexml_load_string($content['content']);
		}
		else
		{
			$content = $this->get_file_fopen($this->url);

			$content = @simplexml_load_string($content['content']);
		}

		if (!$content)
		{
			// TODO add lang entry to error log lang file
			add_log('critical', 'LOG_ERROR_SFNC_ERROR_URL', $this->url);
		}

		return $content;
	}

	/**
	 * Gets remote file using cURL function
	 *
	 * @param string $url
	 * @return string
	 */
	private function get_file_curl($url)
	{
		// initiate and set options
		$ch = @curl_init($url);
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		@curl_setopt($ch, CURLOPT_HEADER, 0);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		//@curl_setopt( $ch, CURLOPT_ENCODING, '');
		@curl_setopt($ch, CURLOPT_USERAGENT, 'SFNC'); // TOTHINK changeable via ACP?
		// initial connection timeout
		@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		// setting this to higher means longer time for loading the page for user!
		@curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		@curl_setopt($ch, CURLOPT_MAXREDIRS, 0);

		// get content
		$content['content'] = @curl_exec($ch);
		$content['errno'] = @curl_errno($ch);
		$content['errmsg'] = @curl_error($ch);
		$content['getinfo'] = @curl_getinfo($ch);
		@curl_close($ch);

		return $content;
	}

	/**
	 * Gets remote file using fopen fopen
	 *
	 * @param string $url
	 * @return string
	 */
	private function get_file_fopen($url)
	{
		$content['content'] = '';

		if ($f = @fopen($url, 'r'))
		{
			while (!feof($f))
			{
				@$content['content'] .= fgets($f, 4096);
			}
			fclose($f);
		}

		return $content;
	}

	/**
	 * HTML to BBCode replacement
	 *
	 * @param string $string
	 * @return string
	 */
	private function html_to_bbcode($string)
	{
		$html = array(
			"/\<b\>(.*?)\<\/b\>/is",
			"/\<i\>(.*?)\<\/i\>/is",
			"/\<u\>(.*?)\<\/u\>/is",
			"/\<ul\>(.*?)\<\/ul\>/is",
			"/\<li\>(.*?)\<\/li\>/is",
			"/\<img(.*?) src=\"(.*?)\" (.*?)\>/is",
			"/\<div\>(.*?)\<\/div\>/is",
			"/\<br(.*?)\>/is",
			"/\<strong\>(.*?)\<\/strong\>/is",
			"/\<a href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/is",
		);

		// Replace with
		$bb = array(
			"[b]$1[/b]",
			"[i]$1[/i]",
			"[u]$1[/u]",
			"[list]$1[/list]",
			"[*]$1",
			"[img]$2[/img]",
			"$1",
			"\n",
			"[b]$1[/b]",
			"[url=$1]$3[/url]",
		);

		// Replace $html in $text with $bb
		$string = preg_replace($html, $bb, $string);

		// Strip all other HTML tags
		$string = strip_tags($string);

		return $string;
	}

	/**
	 * Is downloaded feed in RSS format?
	 *
	 * @param xml object $xml
	 * @return bool
	 */
	private function is_rss()
	{
		return ($this->data->channel->item) ? true : false;
	}

	/**
	 * Main parsing function for RSS format
	 */
	private function parse_rss()
	{
		// list all channel tags, which are available
		foreach ($this->data->channel as $k => $v)
		{
			foreach ($v as $attribute => $attribute_value)
			{
				$this->check_feed_atributes($attribute);
			}
		}

		$i = 0;
		// list all item tags, which are available
		foreach ($this->data->channel->item as $item)
		{
			foreach ($item as $k => $v)
			{
				$this->items[$i][utf8_recode($k, $this->encoding)] = (string) utf8_recode($v, $this->encoding);
				$this->check_item_atributes($k);
			}
			$i++;
		}
	}

	/**
	 * Is downloaded feed in RDF format?
	 *
	 * @param xml object $xml
	 * @return bool
	 */
	private function is_rdf()
	{
		return ($this->data->item) ? true : false;
	}

	/**
	 * Main parsing function for RDF format
	 */
	private function parse_rdf()
	{
		// list all channel tags, which are available
		foreach ($this->data->channel as $k => $v)
		{
			foreach ($v as $at => $av)
			{
				$this->check_feed_atributes($at);
			}
		}

		$i = 0;
		// list all item tags, which are available
		foreach ($this->data->item as $item)
		{
			foreach ($item as $k => $v)
			{
				$this->items[$i][utf8_recode($k, $this->encoding)] = (string) utf8_recode($v, $this->encoding);
				$this->check_item_atributes($k);
			}
			$i++;
		}
	}

	/**
	 * Is downloaded feed in ATOM format?
	 *
	 * @param xml object $xml
	 * @return bool
	 */
	private function is_atom()
	{
		return ($this->data->entry) ? true : false;
	}

	/**
	 * Main parsing function for ATOM format
	 */
	private function parse_atom()
	{
		// get root
		$root = $this->data->children('http://www.w3.org/2005/Atom');

		// get feed data
		foreach ($root as $ak => $av)
		{
			if ($ak != 'entry')
			{
				$this->check_feed_atributes($ak);
			}
		}

		// do we have some data ?
		if (isset($root->entry))
		{
			$i = 0;
			foreach ($root->entry as $entry)
			{
				$details = $entry->children('http://www.w3.org/2005/Atom');

				foreach ($details as $k => $v)
				{
					$this->items[$i][utf8_recode($k, $this->encoding)] = (string) utf8_recode($v, $this->encoding);
					$this->check_item_atributes($k);
				}
				$i++;
			}
		}
	}

	/**
	 * Downloads new data if it's time to do it and prepare feed items for later use
	 *
	 * @param integer $id feed_id
	 */
	private function populate($id)
	{
		// get cached data
		if ($this->cron_init || ( $this->index_init && ($this->next_update < time() ) ))
		{
			if (!preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $this->url))
			{
				// it might be a new feed = no data
				if ($this->url)
				{
					// TODO add lang entry to error log lang file
					add_log('critical', 'LOG_ERROR_SFNC_ERROR_URL', $this->url);
				}

				return;
			}

			// this feed will be actually checked and updated,
			// don´t wait until it ends,to prevent multiple loading of the same ...
			$this->feed_checked();

			$this->data = $this->get_file($this->url);

			// switch parsing by data type
			if ($this->data)
			{
				if ($this->is_atom() || $this->feed_type == 'atom')
				{
					$this->feed_type = 'atom';
					$this->parse_atom();
				}
				elseif ($this->is_rss() || $this->feed_type == 'rss')
				{
					$this->feed_type = 'rss';
					$this->parse_rss();
				}
				elseif ($this->is_rdf() || $this->feed_type == 'rdf')
				{
					$this->feed_type = 'rdf';
					$this->parse_rdf();
				}
				else
				{
					// TODO add lang entry to error log lang file
					add_log('critical', 'LOG_ERROR_SFNC_NO_FEED_TYPE', $this->name);
				}

				// if download was successful
				if (!empty($this->items))
				{
					$this->cache_store_feed();

					$this->autosave_settings();
				}
			}
			else
			{
				// TODO add lang entry to error log lang file
				add_log('critical', 'LOG_ERROR_SFNC_PARSER', 'No data downloaded from the feed ' . $this->url);
			}
		}
		else
		{
			// load data from cache
			$this->items = $this->cache_load_feed();
		}
	}

	/**
	 * Main configuration for the parser
	 */
	private function setup()
	{
		global $config;

		$this->download_function = $config['sfnc_download_function'];
		$this->cron_init = $config['sfnc_cron_init'];
		$this->cron_posting = $config['sfnc_cron_posting'];
		$this->index_init = $config['sfnc_index_init'];
		$this->index_posting = $config['sfnc_index_posting'];
	}

	private function reset_feed()
	{
		// from db
		$this->feed_id = 0;
		$this->feed_type = '';
		$this->feed_name = '';
		$this->encoding = 'UTF-8'; // if encoding is unknown, try UTF-8 instead
		$this->url = '';

		// setting
		$this->enabled_posting = 0;
		$this->enabled_displaying = 0;

		// downloaded data
		$this->data = '';
		// parsed data array => feed items / entries ...
		$this->items = array();

		// download settings
		$this->refresh_after = 3600; // time in seconds
		$this->next_update = 0;
		$this->last_update = 0;

		// some informations
		$this->channel_info = array();
		$this->available_feed_atributes = array();
		$this->available_item_atributes = array();

		// templates
		$this->template_for_posting = '';
		$this->template_for_displaying = '';

		// posting bot
		$this->poster_id = 0;  // 2;
		$this->poster_forum_destination_id = 0; // 2;
		$this->poster_topic_destination_id = 0; // 2;
		$this->posting_limit = 1; // 1
	}

	/**
	 * Changes latest feed check time
	 *
	 * @global db $db
	 */
	private function feed_checked()
	{
		global $db;

		$sql = 'UPDATE ' . SFNC_FEEDS . '
				SET next_update = ' . ( time() + $this->refresh_after ) . '
				WHERE id = ' . (int) $this->feed_id;

//		$db->sql_query($sql);
	}

	/**
	 * Changes latest update time
	 *
	 * @global db $db
	 */
	private function feed_updated()
	{
		global $db;

		$sql = 'UPDATE ' . SFNC_FEEDS . '
				SET last_update = ' . (time() + 5) . '
				WHERE id = ' . (int) $this->feed_id;

//		$db->sql_query($sql);
	}

	/**
	 * Saves feed_type, encoding and available parsed atributes
	 *
	 * @global db $db
	 */
	private function autosave_settings()
	{
		global $db;

		$sql = 'UPDATE ' . SFNC_FEEDS . '
				SET feed_type = "' . strtolower($this->feed_type) . '",
					encoding = "' . strtolower($this->encoding) . '",
					available_feed_atributes = "' . implode(',', $this->available_feed_atributes) . '",
					available_item_atributes = "' . implode(',', $this->available_item_atributes) . '"
				WHERE id = ' . (int) $this->feed_id;

		$db->sql_query($sql);
	}

	/**
	 * Sets settings for selected feed
	 *
	 * @global global $db
	 * @param integer $feed_id
	 */
	private function setup_feed($feed_id)
	{
		global $db;

		// reset possible previous feed settings
		$this->reset_feed();

		$this->feed_id = (int) $feed_id;

		// parser setup
		$sql = 'SELECT url, feed_name, feed_type, encoding,
					next_update, last_update, refresh_after,
					template_for_displaying, template_for_posting,
					poster_id, poster_forum_destination_id, poster_topic_destination_id, posting_limit,
					available_feed_atributes, available_item_atributes,
					enabled_posting, enabled_displaying
				FROM ' . SFNC_FEEDS . '
				WHERE id = ' . $this->feed_id;

		$result = $db->sql_query($sql);

		$feed_data = array();
		$feed_data = $db->sql_fetchrow($result);

		if ($feed_data)
		{
			// SETTINGS for specified feed
			foreach ($feed_data as $k => $v)
			{
				// override default only if value is available
				if ($v)
				{
					$this->$k = $v;
				}
			}

			// split values from db ...
			if (!is_array($this->available_feed_atributes))
			{
				$this->available_feed_atributes = explode(',', $this->available_feed_atributes);
			}
			if (!is_array($this->available_item_atributes))
			{
				$this->available_item_atributes = explode(',', $this->available_item_atributes);
			}

			// get data from the feed and prepare it for later use if wanted
			if ($this->enabled_posting || $this->enabled_displaying)
			{
				$this->populate($this->feed_id);
			}
		}
	}

	// POSTING BOT MOD part [+]
	public function setup_posting($id)
	{
		$this->setup_feed($id);

		// post only if posting is enabled
		if (!empty($this->items) && $this->enabled_posting)
		{
			$this->init_posting();
		}
	}

	private function init_posting()
	{
		global $config, $user, $auth, $db, $phpbb_root_path, $phpEx;

		// require necessary functions for posting
		require_once($phpbb_root_path . 'includes/functions_posting.' . $phpEx);

		// prepare user data for posting bot
		$user_backup = $user;
		$auth_backup = $auth;

		$sql = 'SELECT *
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . (int) $this->poster_id;

		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$row['is_registered'] = true;
		$db->sql_freeresult($result);

		$user->data = array_merge($user->data, $row);

		$auth->acl($user->data);

		// backward posting (from the oldest to the newest)
		$i = (sizeof($this->items) > $this->posting_limit) ? $this->posting_limit - 1 : sizeof($this->items);
		$j = 0;
		while ($i >= 0 && ( ($this->posting_limit == 0) || ($this->posting_limit > $j) ))
		{
			// necessary vars
			$uid = $bitfield = $options = $poll = '';

			// prepare data for posting

			$subject = truncate_string($this->items[$i]['title']);
			generate_text_for_storage($subject, $uid, $bitfield, $options, false, false, false);

			// TODO remake the check, if this post/topic is in db ... post if not
			// NOTE some news has a same repetitive name/title, with actual simple check for topic_title/subject => they'll never be posted :-/
			// NOTE ... not all feeds has a pubDate or similar time announcing tag :-/
			// IDEA MANUAL POSTING
			//		What about downloading "all" messages and privileged user check the checkbox for messages to post ?
			//      After automatic check if the feed has a new messages, and it has a new messages, send PM to privileged user(s)
			// check if this topic is not already posted
			$sql = 'SELECT topic_title
					FROM ' . TOPICS_TABLE . '
					WHERE topic_title = "' . $db->sql_escape($subject) . '"
						AND topic_poster = ' . (int) $this->poster_id;

			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			// Do we have a new item to post ?
			//if (strnatcasecmp($row['topic_title'], $subject))
			if (true)
			{
		/*
				// templates RSS / ATOM has different indexes for messages
				$temp = ( ($this->feed_type == 'rss') || ($this->feed_type == 'rdf') ) ? 'description' : 'content';
				$message = $this->feed_name . "\n\n" . $this->items[$i][$temp];
		 */
				// TODO templates
				// $this->template $this->templating() // [$temp]
				$message = $this->apply_template($this->items[$i]); //$this->feed_name . "\n\n" . $this->items[$i][$temp];

				// post time - not used in version > 0.3.2 (caused bugs with post sorting in topic and quoting)
				$post_time = 0;
				// ATOM entry->updated
				// RSS item->pubDate
				// RDF item->dc:date ???

				// do we have a pubDate ? ... post will be posted with this time!
//				$post_time = ($this->items[$i]['pubDate']) ? strtotime($this->items[$i]['pubDate']) : time();
//				if (($this->feed_type == 'rss') && isset($this->items[$i]['pubDate']))
//				{
//					if (($time = strtotime($this->items[$i]['pubDate'])) !== false)
//					{
//						$post_time = $time;
//					}
//				}
//				elseif (($this->feed_type == 'atom') && isset($this->items[$i]['updated']))
//				{
//					if (($time = strtotime($this->items[$i]['updated'])) !== false)
//					{
//						$post_time = $time;
//					}
//				}
//				elseif (($this->feed_type == 'rdf') && isset($this->items[$i]['dc:date']))
//				{
//					if (($time = strtotime($this->items[$i]['dc:date'])) !== false)
//					{
//						$post_time = $time;
//					}
//				}

				// prepare post data
				// -> functions_content.php
				// generate_text_for_storage(&$text, &$uid, &$bitfield, &$flags, $allow_bbcode = false, $allow_urls = false, $allow_smilies = false)
				generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

				$data = array(
					// General Posting Settings
					'forum_id' => $this->poster_forum_destination_id,
					'topic_id' => 0, //$this->poster_topic_destination_id, // temporarily absolutely disabled
					'icon_id' => false,
					// Defining Post Options
					'enable_bbcode' => true,
					'enable_smilies' => true,
					'enable_urls' => true,
					'enable_sig' => true,
					// Message Body
					'message' => $message,
					'message_md5' => md5($message),
					'bbcode_bitfield' => $bitfield,
					'bbcode_uid' => $uid,
					// Other Options
					'post_edit_locked' => 0,
					'topic_title' => $subject,
					'topic_description' => '',
					// Email Notification Settings
					'notify_set' => false,
					'notify' => false,
					'post_time' => 0,
					'forum_name' => '',
					// Indexing
					'enable_indexing' => true, // Allow indexing the post? (bool)
					// 3.0.6+
					'force_approved_state' => true, // Allow the post to be submitted without going into unapproved queue
				);

				// submit and approve the post!
				// functions_posting.php
				// submit_post($mode, $subject, $username, $topic_type, &$poll, &$data, $update_message = true, $update_search_index = true)
				submit_post('post', $subject, $user->data['username'], POST_NORMAL, $poll, $data, true, true);
				// for development reasons, comment the previous line
			}
			// change $i to the next (ehm previous :D ) item
			$i--;
			$j++;
		}

		// TODO rebuild/sync forums latest topics and post counts
		// redirect to index
		if (!$this->cron_init)
		{
			redirect(generate_board_url());
		}
	}
	// POSTING BOT MOD [-]



	/**
	 * Inits the sfnc on index.php of phpBB
	 *
	 * @global db $db
	 */
	public function index_init()
	{
		global $db;

		$this->setup();

		// initiated on index.php
		// update feed, only if .MOD is not set to run in cron mode
		if (!$this->cron_init)
		{
			$sql = 'SELECT id
					FROM ' . SFNC_FEEDS . '
					WHERE next_update < ' . time() . '
						AND (enabled_posting = 1) OR (enabled_displaying = 1)
					LIMIT 0,1';
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$id = $row['id'];

			$db->sql_freeresult($result);

			if ($id)
			{
				if ($this->index_posting)
				{
					$this->setup_posting($id);
				}
				else
				{
					$this->setup_feed($id);
				}
			}
		}
	}

	/**
	 * Cron init - updates all feeds
	 *
	 * @global db $db
	 */
	public function cron_init()
	{
		global $db;

		$this->setup();

		// forces download
		$this->cron_init = true;

		$ids = array();

		$sql = 'SELECT id
				FROM ' . SFNC_FEEDS . '
				WHERE (enabled_posting = 1) OR (enabled_displaying = 1)';

		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$ids[] = $row['id'];
		}

		$db->sql_freeresult($result);

		if ($ids)
		{
			foreach ($ids as $id)
			{
				if ($this->cron_posting)
				{
					$this->setup_posting($id);
				}
				else
				{
					$this->setup_feed($id);
					// IDEA if we'll find a message which hasn't been posted, we can send a PM to privileged user to manually post the messages
				}
			}
		}
	}

	/**
	 * ACP init - inits specified feed id
	 *
	 * @global db $db
	 * @param int $id feed id
	 */
	public function acp_init($id)
	{
		// forces download
		$this->cron_init = true;

		$this->setup_feed($id);

		$this->populate($this->feed_id);
	}

	/**
	 * Returns available sfnc BB codes for actually initiated feed
	 *
	 * @return array
	 */
	public function get_available_bb()
	{
		$bb = array();

		foreach ($this->available_feed_atributes as $a)
		{
			// don´t show item as available for templates
			if ($a != 'item' && $a != 'items')
			{
				// sfnc_ helps to find the tag
				$bb[$a] = "[sfnc_feed_".$a."]";
			}

			// feed name is always available
			$bb['feed_name'] = "[sfnc_feed_name]";
		}

		foreach ($this->available_item_atributes as $a)
		{
			// sfnc_ helps to find the tag
			$bb[$a] = "[sfnc_item_".$a."]";
		}

		return $bb;
	}

	/**
	 * Apply specified template on message
	 *
	 * @param array $text message data for templating
	 * @param string $type post/display
	 * @return type
	 */
	private function apply_template($text, $type = 'post')
	{
		$template = 'template_for_'.$type.'ing';

		$message = $this->$template;

		if (!$message)
		{
			// TODO return "default" template or default error message???
			return '';
		}

		foreach ($this->get_available_bb() as $id => $bb)
		{
			// is it feed or item attribute we are searching?
			$type = (strpos($bb, 'feed') !== false) ? 'feed' : 'item';

			// if bb is available in template
			if (strpos($message, $bb) !== false && $type == 'item')
			{
				if (isset($text[$id]))
				{
					$message = str_replace("[sfnc_".$type.'_'.$id."]", $text[$id], $message);
				}
			}
			elseif ($type == 'feed') // it's a feed attribute
			{
				if ($id == 'feed_name')
				{
					$message = str_replace("[sfnc_feed_name]", $this->feed_name, $message);
				}

				// damn, this also depends on a feed type :-(
				if ($this->feed_type == 'rss')
				{
					if (isset($this->data->channel->$id))
					{
						// channel image
						if ($id == 'image')
						{
							$message = str_replace("[sfnc_".$type.'_'.$id."]", isset($this->data->channel->$id->url) ? '[img]'.$this->data->channel->$id->url.'[/img]' : '', $message);
						}
						else
						{
							// TODO there might be more work on this :-/
							$message = str_replace("[sfnc_".$type.'_'.$id."]", isset($this->data->channel->$id[0]) ? $this->data->channel->$id[0] : isset($this->data->channel->$id) ? $this->data->channel->$id : '', $message);
						}
					}
				}
				elseif ($this->feed_type == 'rdf')
				{
					if (isset($this->data->channel->$id))
					{
						// channel image
						if ($id == 'image')
						{
							$message = str_replace("[sfnc_".$type.'_'.$id."]", isset($this->data->channel->$id->url) ? '[img]'.$this->data->channel->$id->url.'[/img]' : '', $message);
						}
					}
				}
				elseif ($this->feed_type == 'atom')
				{
					$message = str_replace("[sfnc_".$type.'_'.$id."]", isset($this->data->$id) ? $this->data->$id : '', $message);
				}
			}
		}

		return $message;
	}

	/**
	 * Returns a data array filled with feed items for ticker
	 *
	 * @param int $id
	 * @return array
	 */
	public function get_ticker_data($id = 0)
	{
		if (!$id)
		{
			return;
		}

		$this->setup_feed($id);

		$this->populate($id);

		if (!$this->data)
		{
			return;
		}

		// make returning array
		$ticker_data = array();
		$type = 'display';
		$template = 'template_for_'.$type.'ing';

		// basic info
		$ticker_data['id'] = $this->feed_id;
		$ticker_data['name'] = $this->feed_name;
		$ticker_data['url'] = $this->url;

		foreach ($this->items as $txt)
		{
			$ticker_data['items'][] = $this->apply_template($txt, 'display');
		}

		return $ticker_data;
	}
}

?>