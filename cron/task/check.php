<?php
/**
 *
 * Caladrius. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Wilson Logan, https://www.personalgroupware.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace personalgroupware\caladrius\cron\task;

/**
 * Runs the checks once a day through phpBB's scheduled jobs, and sends alerts if those are switched on.
 */
class check extends \phpbb\cron\task\base
{
	protected $config;
	protected $checker;
	protected $alerts;

	public function __construct(\phpbb\config\config $config, \personalgroupware\caladrius\service\checker $checker,
		\personalgroupware\caladrius\service\alerts $alerts)
	{
		$this->config = $config;
		$this->checker = $checker;
		$this->alerts = $alerts;
	}

	public function run()
	{
		// mark the run first, so a slow check can't be started twice
		$this->config->set('caladrius_last_run', time(), false);
		list($previous, $current) = $this->checker->run();
		$this->alerts->notify($previous, $current);
	}

	public function should_run()
	{
		return (int) $this->config['caladrius_last_run'] < time() - 86400;
	}
}
