<?php
/**
 *
 * Caladrius. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Wilson Logan, https://www.personalgroupware.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace personalgroupware\caladrius\migrations;

/**
 * 0.1.7: notes on items, and the board's ticket address (so "#38" in a note links to ticket 38).
 */
class v017 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['caladrius_tickets']);
	}

	public static function depends_on()
	{
		return ['\personalgroupware\caladrius\migrations\v015'];
	}

	public function update_data()
	{
		return [
			['config.add', ['caladrius_tickets', '']],
			['config_text.add', ['caladrius_notes', '']],
		];
	}
}
