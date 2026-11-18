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
 * Emails the board founders when a check turns red. Off unless the board switches it on.
 * Uses the board's own email settings and queue. Nothing goes anywhere else.
 */
class alerts
{
	protected $config;
	protected $db;
	protected $language;
	protected $root_path;
	protected $php_ext;
	protected $prefix;

	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language,
		$root_path, $php_ext, $table_prefix)
	{
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->prefix = $table_prefix;
	}

	/**
	 * Items that are red now but weren't red in the previous results.
	 */
	public function newly_red($previous, $current)
	{
		$was = [];
		foreach ((isset($previous['items']) ? $previous['items'] : []) as $i)
		{
			$was[$i['id']] = $i['state'];
		}
		$new = [];
		foreach ($current['items'] as $i)
		{
			if ($i['state'] === 'bad' && (!isset($was[$i['id']]) || $was[$i['id']] !== 'bad'))
			{
				$new[] = $i['id'];
			}
		}
		return $new;
	}

	/**
	 * Sends one email per founder listing what turned red. Returns how many emails were queued.
	 */
	public function notify($previous, $current)
	{
		if (!$this->config['caladrius_email'] || !$this->config['email_enable'])
		{
			return 0;
		}
		$new = $this->newly_red($previous, $current);
		if (!$new)
		{
			return 0;
		}

		if (!class_exists('messenger'))
		{
			include $this->root_path . 'includes/functions_messenger.' . $this->php_ext;
		}

		$sql = 'SELECT user_id, username, user_email, user_lang
			FROM ' . USERS_TABLE . '
			WHERE user_type = ' . USER_FOUNDER . "
				AND user_email <> ''";
		$result = $this->db->sql_query($sql);
		$founders = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		$this->language->add_lang('acp_caladrius', 'personalgroupware/caladrius');
		$lines = [];
		foreach ($new as $id)
		{
			$lines[] = '- ' . $this->language->lang('CALADRIUS_' . strtoupper($id) . '_TITLE');
		}

		$sent = 0;
		// through the board's own email queue, like phpBB's other emails
		$messenger = new \messenger(true);
		foreach ($founders as $f)
		{
			$messenger->template('@personalgroupware_caladrius/caladrius_alert', $f['user_lang'] ?: $this->config['default_lang']);
			$messenger->to($f['user_email'], $f['username']);
			$messenger->assign_vars([
				'USERNAME'	=> htmlspecialchars_decode($f['username']),
				'ITEMS'		=> implode("\n", $lines),
			]);
			$messenger->send(NOTIFY_EMAIL);
			$sent++;
		}
		$messenger->save_queue();
		return $sent;
	}
}
