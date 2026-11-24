<?php
/**
 *
 * Caladrius. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Wilson Logan, https://www.personalgroupware.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace personalgroupware\caladrius\service;

/**
 * Notes as HTML, and the ticket address setting. Plain PHP with no phpBB calls, so it can be tested on its own.
 */
class links
{
	const MAX_URL = 255;

	/** In note text: a full https address, or #123 that isn't part of a word, an address or an HTML entity. */
	const TOKEN = '~(https://[^\s<>"\']+)|(?<![\w/&#])#(\d{1,7})(?!\w)~u';

	/**
	 * Note text as HTML: everything escaped, with https addresses and (when the board has a ticket address)
	 * #123 turned into links that open in a new tab.
	 */
	public static function linkify($text, $tickets = '')
	{
		$e = function ($s) {
			return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		};
		$out = '';
		$pos = 0;
		if (preg_match_all(self::TOKEN, $text, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
		{
			foreach ($m as $hit)
			{
				$start = $hit[0][1];
				$url = isset($hit[1]) && $hit[1][1] >= 0 ? $hit[1][0] : '';
				$num = isset($hit[2]) && $hit[2][1] >= 0 ? $hit[2][0] : '';
				if ($url !== '')
				{
					$core = rtrim($url, '.,;:!?)]}');
					$tail = substr($url, strlen($core));
					$out .= $e(substr($text, $pos, $start - $pos))
						. '<a href="' . $e($core) . '" target="_blank" rel="noopener noreferrer">' . $e($core) . '</a>' . $e($tail);
				}
				else if ($num !== '' && $tickets !== '')
				{
					$out .= $e(substr($text, $pos, $start - $pos))
						. '<a href="' . $e($tickets . '/' . $num) . '" target="_blank" rel="noopener noreferrer">#' . $e($num) . '</a>';
				}
				else
				{
					continue;
				}
				$pos = $start + strlen($hit[0][0]);
			}
		}
		return $out . $e(substr($text, $pos));
	}

	/**
	 * The ticket address as typed: [clean address, ''] or ['', language key of the problem]. Empty clears it.
	 */
	public static function clean_tickets($url)
	{
		$url = trim((string) $url);
		if ($url === '')
		{
			return ['', ''];
		}
		if (strlen($url) > self::MAX_URL || preg_match('~[\s<>"\']~', $url))
		{
			return ['', 'CALADRIUS_TICKETS_BAD'];
		}
		$u = parse_url($url);
		if (!$u || !isset($u['scheme'], $u['host']) || strtolower($u['scheme']) !== 'https' || isset($u['user']) || isset($u['pass']))
		{
			return ['', 'CALADRIUS_TICKETS_HTTPS'];
		}
		if (isset($u['query']) || isset($u['fragment']) || strpos($url, '?') !== false || strpos($url, '#') !== false)
		{
			return ['', 'CALADRIUS_TICKETS_QUERY'];
		}
		return [rtrim($url, '/'), ''];
	}
}
