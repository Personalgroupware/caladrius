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
 * 0.1.5: somewhere to keep the items an administrator marked as checked.
 */
class v015 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT config_name FROM ' . $this->table_prefix . "config_text
			WHERE config_name = 'caladrius_checked'";
		$result = $this->db->sql_query($sql);
		$found = (bool) $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $found;
	}

	public static function depends_on()
	{
		return ['\personalgroupware\caladrius\migrations\v010'];
	}

	public function update_data()
	{
		return [
			['config_text.add', ['caladrius_checked', '']],
		];
	}
}
