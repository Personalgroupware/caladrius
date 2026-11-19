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

class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container;

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
		if ($request->is_set_post('run') || $request->is_set_post('save'))
		{
			if (!check_form_key('personalgroupware_caladrius'))
			{
				trigger_error($language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}
			if ($request->is_set_post('save'))
			{
				$config->set('caladrius_email', $request->variable('caladrius_email', 0) ? 1 : 0);
				$message = $language->lang('CALADRIUS_SAVED');
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

		$order = ['upkeep', 'versions', 'tamper'];
		$problems = 0;
		$by_group = array_fill_keys($order, []);
		foreach ($results['items'] as $item)
		{
			if (in_array($item['state'], ['warn', 'bad'], true))
			{
				$problems++;
			}
			$by_group[$item['group']][] = $item;
		}

		foreach ($order as $group)
		{
			$template->assign_block_vars('groups', ['TITLE' => $language->lang('CALADRIUS_GROUP_' . strtoupper($group))]);
			foreach ($by_group[$group] as $item)
			{
				$key = 'CALADRIUS_' . strtoupper($item['id']);
				$template->assign_block_vars('groups.items', [
					'STATE'			=> $item['state'],
					'STATE_LABEL'	=> $language->lang('CALADRIUS_STATE_' . strtoupper($item['state'])),
					'TITLE'			=> $language->lang($key . '_TITLE'),
					'DETAIL'		=> $this->text($language, $user, $key . '_' . strtoupper($item['state']), $item['args']),
					'TODO'			=> in_array($item['state'], ['warn', 'bad'], true) && $language->is_set($key . '_TODO') ? $language->lang($key . '_TODO') : '',
					'MORE'			=> $item['more'] ? $language->lang('CALADRIUS_LIST_MORE', $item['more']) : '',
				]);
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
				}
			}
		}

		$template->assign_vars([
			'U_ACTION'			=> $this->u_action,
			'S_MESSAGE'			=> $message,
			'SUMMARY'			=> $problems ? $language->lang('CALADRIUS_SUMMARY_PROBLEMS', $problems) : $language->lang('CALADRIUS_SUMMARY_OK'),
			'S_PROBLEMS'		=> (bool) $problems,
			'LAST_RUN'			=> $language->lang('CALADRIUS_LAST_RUN', $user->format_date($results['time']), $results['seconds']),
			'S_EMAIL'			=> (bool) $config['caladrius_email'],
			'S_BOARD_EMAIL'		=> (bool) $config['email_enable'],
		]);
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
