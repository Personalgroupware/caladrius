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

use personalgroupware\caladrius\service\links;

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container, $phpbb_root_path;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');
		$request = $phpbb_container->get('request');
		$template = $phpbb_container->get('template');
		$config = $phpbb_container->get('config');
		$user = $phpbb_container->get('user');
		/** @var \personalgroupware\caladrius\service\checker $checker */
		$checker = $phpbb_container->get('personalgroupware.caladrius.checker');
		$alerts = $phpbb_container->get('personalgroupware.caladrius.alerts');

		$language->add_lang('acp_caladrius', 'personalgroupware/caladrius');
		$this->tpl_name = 'acp_caladrius_body';
		$this->page_title = $language->lang('ACP_CALADRIUS_TITLE');
		add_form_key('personalgroupware_caladrius');

		$message = '';
		$error = '';
		if ($request->is_set_post('run') || $request->is_set_post('save') || $request->is_set_post('mark') || $request->is_set_post('undo')
			|| $request->is_set_post('note_add') || $request->is_set_post('note_del') || $request->is_set_post('note_edit'))
		{
			if (!check_form_key('personalgroupware_caladrius'))
			{
				trigger_error($language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}
			if ($request->is_set_post('save'))
			{
				$config->set('caladrius_email', $request->variable('caladrius_email', 0) ? 1 : 0);
				list($tickets, $why) = links::clean_tickets($request->raw_variable('caladrius_tickets', ''));
				if ($why)
				{
					$error = $language->lang($why);
				}
				else
				{
					$old = (string) $config['caladrius_tickets'];
					if ($tickets !== $old)
					{
						$config->set('caladrius_tickets', $tickets);
						$phpbb_container->get('log')->add('admin', $user->data['user_id'], $user->ip, 'LOG_CALADRIUS_TICKETS', false,
							[$old !== '' ? $old : '-', $tickets !== '' ? $tickets : '-']);
					}
					$message = $language->lang('CALADRIUS_SAVED');
				}
			}
			else if ($request->is_set_post('note_add') || $request->is_set_post('note_del') || $request->is_set_post('note_edit'))
			{
				list($message, $error) = $this->note($checker, $user, $language, $request);
			}
			else if ($request->is_set_post('mark') || $request->is_set_post('undo'))
			{
				$message = $this->mark($checker, $user, $language, $request);
			}
			else
			{
				list($previous, $current) = $checker->run();
				$alerts->notify($previous, $current);
			}
		}

		$results = $checker->load();
		if (!$results)
		{
			list(, $results) = $checker->run();
		}

		// The daily check runs from the board's scheduler, so if the scheduler is stuck the stored results go
		// stale and can't say why. Ask the scheduler now, while the page is open.
		$stale = '';
		if (time() - (int) $results['time'] > 26 * 3600)
		{
			$stale = $language->lang('CALADRIUS_STALE', $user->format_date($results['time']));
			$watch = $checker->scheduler_watch();
			if ($watch && $watch['stuck'] && $watch['running'])
			{
				$stale .= ' ' . $language->lang('CALADRIUS_STALE_STOPS_AT', $watch['stuck'], $this->ago($language, $watch['waiting']));
			}
			else if ($watch && !$watch['running'])
			{
				$stale .= ' ' . $language->lang('CALADRIUS_STALE_NOT_RUNNING');
			}
		}

		$order = ['upkeep', 'versions', 'tamper'];
		$colours = ['ok' => '#2e7d4f', 'warn' => '#b26a00', 'bad' => '#b3261e', 'unknown' => '#777777'];
		$report = [];
		$problems = 0;
		$marked = 0;
		$checked = $checker->checked();
		$notes = $checker->notes();
		$tickets = (string) $config['caladrius_tickets'];
		// the note being edited (Edit is a plain link, so it only changes what this page shows)
		$editing = $error ? '' : $request->variable('edit_note', '');
		$by_group = array_fill_keys($order, []);
		foreach ($results['items'] as $item)
		{
			if (in_array($item['state'], ['warn', 'bad'], true))
			{
				// a mark only holds while the check still finds exactly the same thing
				if (isset($checked[$item['id']]['sig']) && $checked[$item['id']]['sig'] === $checker->item_signature($item))
				{
					$item['checked'] = $checked[$item['id']];
					$marked++;
				}
				else
				{
					if (isset($checked[$item['id']]['sig']))
					{
						$item['lapsed'] = $checked[$item['id']];
					}
					$problems++;
				}
			}
			$by_group[$item['group']][] = $item;
		}

		foreach ($order as $group)
		{
			$group_title = $language->lang('CALADRIUS_GROUP_' . strtoupper($group));
			$template->assign_block_vars('groups', ['TITLE' => $group_title]);
			$report[] = '';
			$report[] = '[b][u]' . $this->plain($group_title) . '[/u][/b]';
			foreach ($by_group[$group] as $item)
			{
				$key = 'CALADRIUS_' . strtoupper($item['id']);
				$is_checked = isset($item['checked']);
				$row = [
					'ID'			=> $item['id'],
					'S_CAN_MARK'	=> !$is_checked && in_array($item['state'], ['warn', 'bad'], true),
					'S_CHECKED'		=> $is_checked,
					'CHECKED_BY'	=> $is_checked ? $language->lang('CALADRIUS_CHECKED_BY', $item['checked']['user'], $user->format_date($item['checked']['time'])) : '',
					'LAPSED'		=> isset($item['lapsed']) ? $language->lang(
						isset($item['lapsed']['state']) && $item['lapsed']['state'] === 'warn' && $item['state'] === 'bad' ? 'CALADRIUS_LAPSED_WORSE' : 'CALADRIUS_LAPSED',
						$item['lapsed']['user'], $user->format_date($item['lapsed']['time'])) : '',
					'STATE'			=> $item['state'],
					'STATE_LABEL'	=> $language->lang('CALADRIUS_STATE_' . strtoupper($item['state'])),
					'TITLE'			=> $language->lang($key . '_TITLE'),
					'DETAIL'		=> $this->text($language, $user, $key . '_' . strtoupper($item['state']), $item['args']),
					'TODO'			=> $this->todo($language, $key, $item['state']),
					'MORE'			=> $item['more'] ? $language->lang('CALADRIUS_LIST_MORE', $item['more']) : '',
				];
				$template->assign_block_vars('groups.items', $row);
				$colour = isset($colours[$item['state']]) ? $colours[$item['state']] : $colours['unknown'];
				$label = $row['STATE_LABEL'];
				if ($is_checked)
				{
					$colour = $colours['unknown'];
					$label = $language->lang('CALADRIUS_REPORT_CHECKED', $row['STATE_LABEL']);
				}
				$report[] = '[color=' . $colour . '][b]' . $this->plain($label) . '[/b][/color] [b]'
					. $this->plain($row['TITLE']) . '[/b]: ' . $this->plain($row['DETAIL']);
				$report_list = [];
				foreach ($item['list'] as $line)
				{
					if (is_array($line) && $line['key'] === 'CALADRIUS_LOG_ENTRY')
					{
						$text = $language->lang('CALADRIUS_LOG_ENTRY', (int) $line['args'][0],
							$this->log_message($language, (string) $line['args'][1], isset($line['log']) ? (array) $line['log'] : []));
					}
					else
					{
						$text = is_array($line)
							? $this->text($language, $user, $line['key'], isset($line['args']) ? $line['args'] : [])
							: $line;
					}
					$template->assign_block_vars('groups.items.list', ['TEXT' => $text]);
					$report_list[] = '[*]' . $this->plain($text);
				}
				foreach ((isset($notes[$item['id']]) ? $notes[$item['id']] : []) as $n)
				{
					$note_id = $item['id'] . '|' . (int) $n['time'] . '|' . (int) $n['u'];
					$own = (int) $n['u'] === (int) $user->data['user_id'];
					$template->assign_block_vars('groups.items.notes', [
						'BY'		=> $n['user'],
						'WHEN'		=> $user->format_date($n['time']),
						'EDITED'	=> !empty($n['edited']) ? $language->lang('CALADRIUS_NOTE_EDITED', $user->format_date($n['edited'])) : '',
						'TEXT'		=> links::linkify($n['text'], $tickets),
						'RAW'		=> htmlspecialchars($n['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
						'ID'		=> $note_id,
						'ANCHOR'	=> 'note-' . substr(sha1($note_id), 0, 10),
						'S_OWN'		=> $own,
						'S_EDITING'	=> $own && $editing === $note_id,
						'U_EDIT'	=> $this->u_action . '&amp;edit_note=' . urlencode($note_id) . '#note-' . substr(sha1($note_id), 0, 10),
					]);
					$report_list[] = '[*]' . $this->plain($language->lang('CALADRIUS_REPORT_NOTE', $n['user'], $user->format_date($n['time']), $n['text']));
				}
				if ($report_list)
				{
					if ($row['MORE'])
					{
						$report_list[] = '[*]' . $this->plain($row['MORE']);
					}
					$report[] = '[list]' . implode("\n", $report_list) . '[/list]';
				}
			}
		}

		$summary = $problems ? $language->lang('CALADRIUS_SUMMARY_PROBLEMS', $problems) : $language->lang('CALADRIUS_SUMMARY_OK');
		if ($marked)
		{
			$summary .= ' ' . $language->lang('CALADRIUS_SUMMARY_CHECKED', $marked);
		}
		$last_run = $language->lang('CALADRIUS_LAST_RUN', $user->format_date($results['time']), $results['seconds']);
		array_unshift($report,
			'[b]' . $this->plain($language->lang('CALADRIUS_REPORT_HEAD', $this->ext_version($phpbb_container), $config['version'], PHP_VERSION)) . '[/b]',
			$this->plain($summary) . ' ' . $this->plain($last_run)
		);
		$report[] = '';
		$report[] = '[i]' . $this->plain($language->lang('CALADRIUS_REPORT_FOOT')) . '[/i]';
		$report_text = $this->redact(implode("\n", $report), $config, $phpbb_root_path);

		$template->assign_vars([
			'U_ACTION'			=> $this->u_action,
			'S_MESSAGE'			=> $message,
			'S_ERROR'			=> $error,
			'TICKETS'			=> $tickets,
			'NOTE_MAX'			=> self::NOTE_MAX,
			'STALE'				=> $stale,
			'SUMMARY'			=> $summary,
			'S_PROBLEMS'		=> (bool) $problems,
			'MARKED'			=> $marked,
			'CALADRIUS_VERSION'	=> $language->lang('CALADRIUS_FOOTER', $this->ext_version($phpbb_container)),
			'LAST_RUN'			=> $last_run,
			'REPORT'			=> htmlspecialchars($report_text, ENT_QUOTES, 'UTF-8'),
			'S_EMAIL'			=> (bool) $config['caladrius_email'],
			'S_BOARD_EMAIL'		=> (bool) $config['email_enable'],
		]);
	}

	/**
	 * "Mark as checked" and "Undo". Only a warning or a problem in the latest results can be marked,
	 * and the mark keeps that item's fingerprint, so it lapses when the check finds something different.
	 */
	protected function mark($checker, $user, $language, $request)
	{
		$results = $checker->load();
		$checked = $checker->checked();
		if ($request->is_set_post('undo'))
		{
			unset($checked[$request->variable('undo', '')]);
			$checker->set_checked($checked);
			return $language->lang('CALADRIUS_UNDONE');
		}
		$id = $request->variable('mark', '');
		foreach (($results ? $results['items'] : []) as $item)
		{
			if ($item['id'] === $id && in_array($item['state'], ['warn', 'bad'], true))
			{
				$checked[$id] = [
					'sig'	=> $checker->item_signature($item),
					'time'	=> time(),
					'user'	=> $user->data['username'],
					'state'	=> $item['state'],
				];
				$checker->set_checked($checked);
				return $language->lang('CALADRIUS_MARKED');
			}
		}
		return '';
	}

	const NOTE_MAX = 1000;
	const NOTES_PER_ITEM = 50;

	/**
	 * Add a note to an item, or delete one of your own. Returns [message, error].
	 */
	protected function note($checker, $user, $language, $request)
	{
		$notes = $checker->notes();
		if ($request->is_set_post('note_edit'))
		{
			list($id, $time, $uid) = array_pad(explode('|', $request->variable('note_edit', '')), 3, '');
			if ((int) $uid !== (int) $user->data['user_id'] || empty($notes[$id]))
			{
				return ['', ''];
			}
			list($text, $why) = $this->note_text($request, $language);
			if ($why)
			{
				return ['', $why];
			}
			foreach ($notes[$id] as $i => $n)
			{
				if ((int) $n['time'] === (int) $time && (int) $n['u'] === (int) $uid)
				{
					if ($n['text'] !== $text)
					{
						$notes[$id][$i]['text'] = $text;
						$notes[$id][$i]['edited'] = time();
						$checker->set_notes($notes);
					}
					return [$language->lang('CALADRIUS_NOTE_UPDATED'), ''];
				}
			}
			return ['', ''];
		}
		if ($request->is_set_post('note_del'))
		{
			list($id, $time, $uid) = array_pad(explode('|', $request->variable('note_del', '')), 3, '');
			if ((int) $uid !== (int) $user->data['user_id'] || empty($notes[$id]))
			{
				return ['', ''];
			}
			$notes[$id] = array_values(array_filter($notes[$id], function ($n) use ($time, $uid) {
				return !((int) $n['time'] === (int) $time && (int) $n['u'] === (int) $uid);
			}));
			$checker->set_notes($notes);
			return [$language->lang('CALADRIUS_NOTE_DELETED'), ''];
		}
		$id = $request->variable('note_add', '');
		$known = false;
		$results = $checker->load();
		foreach (($results ? $results['items'] : []) as $item)
		{
			$known = $known || $item['id'] === $id;
		}
		if (!$known)
		{
			return ['', ''];
		}
		list($text, $why) = $this->note_text($request, $language);
		if ($why)
		{
			return ['', $why];
		}
		if (isset($notes[$id]) && count($notes[$id]) >= self::NOTES_PER_ITEM)
		{
			return ['', $language->lang('CALADRIUS_NOTE_TOO_MANY', self::NOTES_PER_ITEM)];
		}
		$notes[$id][] = ['u' => (int) $user->data['user_id'], 'user' => $user->data['username'], 'time' => time(), 'text' => $text];
		$checker->set_notes($notes);
		return [$language->lang('CALADRIUS_NOTE_SAVED'), ''];
	}

	/**
	 * The note text from the form: [text, ''] or ['', why it can't be saved].
	 */
	protected function note_text($request, $language)
	{
		// raw: phpBB's usual request variable would store HTML entities. The text is escaped when it's shown.
		$text = trim(str_replace("\r\n", "\n", (string) $request->raw_variable('note_text', '')));
		if (function_exists('utf8_normalize_nfc'))
		{
			$text = utf8_normalize_nfc($text);
		}
		if ($text === '')
		{
			return ['', $language->lang('CALADRIUS_NOTE_EMPTY')];
		}
		if (utf8_strlen($text) > self::NOTE_MAX)
		{
			return ['', $language->lang('CALADRIUS_NOTE_TOO_LONG', self::NOTE_MAX)];
		}
		return [$text, ''];
	}

	/**
	 * What to do about a warning or a problem: KEY_TODO_WARN or KEY_TODO_BAD when the two need
	 * different advice, otherwise KEY_TODO.
	 */
	protected function todo($language, $key, $state)
	{
		if (!in_array($state, ['warn', 'bad'], true))
		{
			return '';
		}
		foreach ([$key . '_TODO_' . strtoupper($state), $key . '_TODO'] as $k)
		{
			if ($language->is_set($k))
			{
				return $language->lang($k);
			}
		}
		return '';
	}

	/**
	 * Language string with its arguments, turning ['ago' => seconds] into "5 minutes ago".
	 */
	protected function text($language, $user, $key, array $args)
	{
		foreach ($args as $i => $a)
		{
			if (is_array($a) && isset($a['ago']))
			{
				$args[$i] = $this->ago($language, (int) $a['ago']);
			}
		}
		return call_user_func_array([$language, 'lang'], array_merge([$key], array_values($args)));
	}

	/**
	 * A phpBB log entry in words, the way Maintenance > Error log shows it, as plain escaped text.
	 * Falls back to the log code and its details when no language string is loaded for it.
	 */
	protected function log_message($language, $operation, array $data)
	{
		$data = array_map('strval', $data);
		$raw = $language->is_set($operation) ? $language->lang($operation) : '';
		if (is_string($raw) && $raw !== '')
		{
			$needed = preg_match_all('/%(\d+\$)?[sd]/', $raw);
			$args = array_pad($data, max($needed, count($data)), '');
			$msg = @vsprintf($raw, $args);
			if (!is_string($msg) || $msg === '')
			{
				$msg = $raw;
			}
		}
		else
		{
			$msg = $operation . ($data ? ': ' . implode(' | ', $data) : '');
		}
		$msg = preg_replace('/(<br\s*\/?>\s*)*(&raquo;|»)\s*/u', ': ', $msg);
		$msg = preg_replace('/:\s*:/', ':', strip_tags(preg_replace('/<br\s*\/?>/', ' ', $msg)));
		$msg = trim(preg_replace(['/\s+/u', '/ :/', '/:+$/'], [' ', ':', ''], html_entity_decode($msg, ENT_QUOTES, 'UTF-8')));
		return htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Page text as plain text for the BBCode report: no tags, no entities, one line,
	 * and no square brackets that phpBB could read as BBCode.
	 */
	protected function plain($text)
	{
		$text = html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', ' ', (string) $text)), ENT_QUOTES, 'UTF-8');
		$text = strtr($text, ['[' => '(', ']' => ')']);
		return trim(preg_replace('/\s+/u', ' ', $text));
	}

	/**
	 * Takes out what a board owner may not want in a public post: the board's folder and
	 * other server paths, email addresses, IP addresses and the board's own address.
	 */
	protected function redact($text, $config, $root_path)
	{
		$root = rtrim(str_replace('\\', '/', (string) realpath($root_path)), '/');
		if (strlen($root) > 1)
		{
			$text = str_ireplace([$root, str_replace('/', '\\', $root)], '[board folder]', $text);
		}

		$text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email]', $text);

		$hosts = [];
		foreach (['server_name', 'cookie_domain'] as $name)
		{
			$host = strtolower(trim((string) $config[$name], ". \t"));
			if (preg_match('/^www\./', $host))
			{
				$host = substr($host, 4);
			}
			if ($host !== '' && $host !== 'localhost' && strpos($host, '.') !== false)
			{
				$hosts[] = preg_quote($host, '/');
			}
		}
		if ($hosts)
		{
			$text = preg_replace('/(https?:\/\/)?([a-z0-9\-]+\.)*(' . implode('|', array_unique($hosts)) . ')(:\d+)?/i', '[board address]', $text);
		}

		$text = preg_replace([
			'#(?<![\w.])/(?:home\d*|usr/home|var/www|srv|Users|opt|tmp)/[^\s\'"()]+#',
			'/\b[A-Za-z]:\\\\[^\s\'"()]+/',
			'/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
			'/\b(?:[0-9a-f]{1,4}:){3,7}[0-9a-f]{1,4}\b/i',
		], ['[path]', '[path]', '[ip]', '[ip]'], $text);

		return $text;
	}

	protected function ext_version($container)
	{
		try
		{
			$meta = $container->get('ext.manager')->create_extension_metadata_manager('personalgroupware/caladrius')->get_metadata('all');
			return isset($meta['version']) ? $meta['version'] : '?';
		}
		catch (\Exception $e)
		{
			return '?';
		}
	}

	protected function ago($language, $seconds)
	{
		if ($seconds > 20 * 365 * 86400)
		{
			return $language->lang('CALADRIUS_AGO_NEVER');
		}
		if ($seconds < 7200)
		{
			return $language->lang('CALADRIUS_AGO_MINUTES', max(1, (int) round($seconds / 60)));
		}
		if ($seconds < 3 * 86400)
		{
			return $language->lang('CALADRIUS_AGO_HOURS', (int) round($seconds / 3600));
		}
		return $language->lang('CALADRIUS_AGO_DAYS', (int) round($seconds / 86400));
	}
}
