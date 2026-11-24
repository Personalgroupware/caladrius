<?php
/**
 *
 * Caladrius. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Wilson Logan, https://www.personalgroupware.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_CALADRIUS_TITLE'	=> 'Caladrius',
	'ACP_CALADRIUS_HEALTH'	=> 'Board health',
	'LOG_CALADRIUS_TICKETS'	=> '<strong>Caladrius ticket address changed</strong><br />» from %1$s to %2$s',
]);
