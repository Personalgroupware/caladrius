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

class v010 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['caladrius_version']);
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_data()
	{
		return [
			['config.add', ['caladrius_version', '0.1.0']],
			['config.add', ['caladrius_last_run', 0]],
			['config.add', ['caladrius_email', 0]],
			['config_text.add', ['caladrius_results', '']],

			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_CALADRIUS_TITLE']],
			['module.add', ['acp', 'ACP_CALADRIUS_TITLE', [
				'module_basename'	=> '\personalgroupware\caladrius\acp\main_module',
				'modes'				=> ['health'],
			]]],
		];
	}
}
