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
 * Reads phpBB's email queue file, which sets $this->queue_data when included.
 */
class queue_reader
{
	public $queue_data = [];

	public function count($file)
	{
		include $file;
		return isset($this->queue_data['email']['data']) ? count($this->queue_data['email']['data']) : 0;
	}
}
