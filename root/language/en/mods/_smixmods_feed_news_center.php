<?php
/**
 * SmiX.MODs Feed News Center [English]
 *
 * @package smixmods_feed_news_center
 * @version $Id: $
 * @copyright (c) 2009-2010 Jiri Smika (Smix) http://phpbb3.smika.net
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

	// log mesages
	'LOG_ERROR_SMIXMODS_FEED_PARSER_NO_FEED_TYPE'	=>	'Unable to detect feed type for feed "$s"',

	// hello_world example
    'HELLO'            => 'Hello',
    'HELLO_EXPLAIN'    => 'Just a little example.',
    'HELLO_WHO'        => 'Who says \'Hello\' ?',
    'WORLD'            => 'World',
    'SAY_HELLO'     => '%1$s says \'Hello %2$s\'',

    'BERTIE'    => 'Bertiezilla',

));

?>