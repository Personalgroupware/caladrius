<?php
/**
 *
 * Caladrius. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Wilson Logan, https://www.personalgroupware.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace personalgroupware\caladrius\acp;

class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\personalgroupware\caladrius\acp\main_module',
			'title'		=> 'ACP_CALADRIUS_TITLE',
			'modes'		=> [
				'health'	=> [
					'title'	=> 'ACP_CALADRIUS_HEALTH',
					'auth'	=> 'ext_personalgroupware/caladrius && acl_a_board',
					'cat'	=> ['ACP_CALADRIUS_TITLE'],
				],
			],
		];
	}
}
