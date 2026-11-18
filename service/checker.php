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
 * Runs the health checks. Read-only: SELECTs and file reads, nothing is changed and nothing is sent anywhere.
 *
 * Each result item is ['id', 'group', 'state', 'args', 'list'].
 * state is one of ok, warn, bad, unknown. The ACP page turns id + state into words through the language file.
 * Values in 'args' and 'list' that are arrays like ['ago' => seconds] are turned into "5 minutes ago" there.
 */
class checker
{
	/** PHP branches and the last day of their security support (php.net). Updated with each Caladrius release. */
	const PHP_EOL = [
		'7.2' => '2020-11-30', '7.3' => '2021-12-06', '7.4' => '2022-11-28',
		'8.0' => '2023-11-26', '8.1' => '2025-12-31', '8.2' => '2026-12-31',
		'8.3' => '2027-12-31', '8.4' => '2028-12-31', '8.5' => '2029-12-31',
	];

	/** Code patterns typical of web shells, injected code and crypto-miners. */
	const PATTERNS = [
		'eval of decoded data'				=> '/\beval\s*\(\s*(base64_decode|gzinflate|gzuncompress|gzdecode|str_rot13|strrev|rawurldecode|hex2bin)\s*\(/i',
		'eval or assert of request data'	=> '/\b(eval|assert)\s*\(\s*(stripslashes\s*\()?\$_(GET|POST|REQUEST|COOKIE|SERVER)\b/i',
		'shell command from request data'	=> '/\b(system|shell_exec|passthru|exec|popen|proc_open|pcntl_exec)\s*\(\s*(stripslashes\s*\()?\$_(GET|POST|REQUEST|COOKIE)\b/i',
		'file chosen by the request'		=> '/\b(include|include_once|require|require_once)\s*\(?\s*\$_(GET|POST|REQUEST|COOKIE)\b/i',
		'file written from request data'	=> '/\b(file_put_contents|fwrite|fputs)\s*\([^;]{0,80}\$_(GET|POST|REQUEST|COOKIE|FILES)\b/i',
		'preg_replace with /e'				=> '/preg_replace\s*\(\s*([\'"])(.).*\2[a-df-z]*e[a-z]*\1/i',
		'known web shell'					=> '/(c99shell|r57shell|WSO\s*[0-9.]+|b374k|IndoXploit|Alfa\s*Team|FilesMan)/i',
		'long encoded blob'					=> '/[\'"][A-Za-z0-9+\/=]{3000,}[\'"]/',
	];
	const MINER = '/(coinhive|coin-hive|cryptonight|coinimp|webminer|cryptoloot|minero\.cc|deepminer|\.miner\.start\s*\()/i';

	protected $config;
	protected $config_text;
	protected $db;
	protected $version_helper;
	protected $root;
	protected $cache_dir;
	protected $prefix;

	/** @var array|null path => sha256 of the official package for this phpBB version */
	protected $manifest;

	/** @var array path => sha256 of the file on this board, so each file is read once per run */
	protected $hashes = [];

	public function __construct(\phpbb\config\config $config, \phpbb\config\db_text $config_text, \phpbb\db\driver\driver_interface $db,
		\phpbb\version_helper $version_helper, $root_path, $cache_dir, $table_prefix)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->db = $db;
		$this->version_helper = $version_helper;
		$this->root = rtrim(str_replace('\\', '/', realpath($root_path)), '/') . '/';
		$this->cache_dir = $cache_dir;
		$this->prefix = $table_prefix;
	}

	/**
	 * Runs every check, stores the results and returns [previous results, new results].
	 */
	public function run()
	{
		@set_time_limit(300);
		$t0 = microtime(true);
		$this->hashes = [];
		$previous = $this->load();

		$items = [];
		foreach (['cron', 'queue', 'database', 'phpbb_log', 'php_errors', 'phpbb_version', 'php_version',
			'core_files', 'uploads', 'shells', 'ext_folders', 'setup'] as $check)
		{
			$t = microtime(true);
			try
			{
				$item = $this->$check();
			}
			catch (\Throwable $e)
			{
				$item = $this->item($check, 'unknown', [], ['Check failed: ' . $e->getMessage()]);
			}
			$item['ms'] = (int) round((microtime(true) - $t) * 1000);
			$items[] = $item;
		}

		$results = [
			'time'		=> time(),
			'seconds'	=> round(microtime(true) - $t0, 1),
			'version'	=> $this->config['version'],
			'items'		=> $items,
		];
		$this->config_text->set('caladrius_results', json_encode($results));
		$this->config->set('caladrius_last_run', $results['time'], false);
		return [$previous, $results];
	}

	/** The last stored results, or null. */
	public function load()
	{
		$json = $this->config_text->get('caladrius_results');
		$data = $json ? json_decode($json, true) : null;
		return is_array($data) ? $data : null;
	}

	protected function item($id, $state, array $args = [], array $list = [], $group = null)
	{
		$groups = [
			'cron' => 'upkeep', 'queue' => 'upkeep', 'database' => 'upkeep', 'phpbb_log' => 'upkeep', 'php_errors' => 'upkeep',
			'phpbb_version' => 'versions', 'php_version' => 'versions',
		];
		return [
			'id'	=> $id,
			'group'	=> $group ?: (isset($groups[$id]) ? $groups[$id] : 'tamper'),
			'state'	=> $state,
			'args'	=> $args,
			'list'	=> array_slice($list, 0, 25),
			'more'	=> max(0, count($list) - 25),
		];
	}

	protected function rows($sql, $limit = 0)
	{
		$result = $limit ? $this->db->sql_query_limit($sql, $limit) : $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);
		return $rows;
	}

	protected function one($sql)
	{
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $row ? reset($row) : null;
	}

	// ---------------------------------------------------------------- behind the scenes

	/** When each scheduled job last ran (phpBB keeps "<job>_last_gc" and the interval "<job>_gc" in config). */
	protected function cron()
	{
		$now = time();
		$latest = 0;
		$late = $stopped = [];
		foreach ($this->config as $name => $value)
		{
			if (substr($name, -8) !== '_last_gc')
			{
				continue;
			}
			$base = substr($name, 0, -8);
			$last = (int) $value;
			if (!$last)
			{
				// never run yet (a new board, or a job with nothing to do so far)
				continue;
			}
			$latest = max($latest, $last);
			$every = isset($this->config[$base . '_gc']) ? (int) $this->config[$base . '_gc'] : 86400;
			$overdue = $now - $last - $every;
			if ($overdue > 3 * 86400)
			{
				$stopped[] = [$base, ['ago' => $now - $last]];
			}
			else if ($overdue > 86400)
			{
				$late[] = [$base, ['ago' => $now - $last]];
			}
		}
		$latest = max($latest, (int) $this->config['last_queue_run']);
		if (!$latest)
		{
			return $this->item('cron', 'unknown');
		}

		$list = [];
		foreach (array_merge($stopped, $late) as $job)
		{
			$list[] = ['key' => 'CALADRIUS_CRON_JOB', 'args' => $job];
		}
		$lock = explode(' ', (string) $this->config['cron_lock']);
		if ((int) $lock[0] && $now - (int) $lock[0] > 3600)
		{
			$list[] = ['key' => 'CALADRIUS_CRON_LOCK', 'args' => [['ago' => $now - (int) $lock[0]]]];
		}

		$state = $stopped ? 'bad' : (($late || $list) ? 'warn' : 'ok');
		return $this->item('cron', $state, [['ago' => $now - $latest]], $list);
	}

	/** The email queue, which phpBB keeps in a PHP file until a scheduled job sends it. */
	protected function queue()
	{
		if (!$this->config['email_enable'])
		{
			return $this->item('queue', 'warn', [0], [['key' => 'CALADRIUS_QUEUE_OFF']]);
		}
		$count = 0;
		$file = $this->cache_dir . 'queue.php';
		if (is_file($file))
		{
			$reader = new queue_reader();
			$count = $reader->count($file);
		}
		$since = time() - (int) $this->config['last_queue_run'];
		if ($count >= 500 || ($count && $since > 3 * 86400))
		{
			$state = 'bad';
		}
		else if ($count >= 50 || ($count && $since > 86400))
		{
			$state = 'warn';
		}
		else
		{
			$state = 'ok';
		}
		return $this->item('queue', $state, [$count, ['ago' => $since]]);
	}

	/** Size of the database, the largest tables, and the sessions table (it grows when tidying stops). */
	protected function database()
	{
		if (strpos($this->db->get_sql_layer(), 'mysql') !== 0)
		{
			return $this->item('database', 'unknown');
		}
		$tables = $this->rows("SELECT table_name AS t, data_length + index_length AS b
			FROM information_schema.tables WHERE table_schema = DATABASE()
			ORDER BY data_length + index_length DESC", 6);
		$total = (float) $this->one('SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()');
		$list = [];
		foreach ($tables as $t)
		{
			$t = array_change_key_case($t, CASE_LOWER);
			$list[] = ['key' => 'CALADRIUS_DB_TABLE', 'args' => [preg_replace('/^' . preg_quote($this->prefix, '/') . '/', '', $t['t']), round($t['b'] / 1048576, 1)]];
		}
		$sessions = (int) $this->one('SELECT COUNT(session_id) FROM ' . SESSIONS_TABLE);
		$size = $total >= 1073741824 ? round($total / 1073741824, 2) . ' GB' : round($total / 1048576) . ' MB';
		if ($sessions > 50000)
		{
			return $this->item('database', 'warn', [$size, $sessions], $list);
		}
		return $this->item('database', 'ok', [$size], $list);
	}

	/** Errors phpBB itself logged as critical in the last 24 hours (ACP > Maintenance > Error log). */
	protected function phpbb_log()
	{
		$since = time() - 86400;
		$rows = $this->rows('SELECT log_operation, COUNT(log_id) AS n FROM ' . LOG_TABLE . '
			WHERE log_type = ' . LOG_CRITICAL . ' AND log_time >= ' . $since . '
			GROUP BY log_operation ORDER BY n DESC', 5);
		$total = 0;
		$list = [];
		foreach ($rows as $r)
		{
			$total += (int) $r['n'];
			$list[] = ['key' => 'CALADRIUS_LOG_LINE', 'args' => [$r['log_operation'], (int) $r['n']]];
		}
		return $this->item('phpbb_log', $total ? 'warn' : 'ok', [$total], $list);
	}

	/** PHP errors in the server's error log in the last 24 hours, when the host lets PHP read that log. */
	protected function php_errors()
	{
		$log = (string) ini_get('error_log');
		if ($log === '' || !@is_file($log) || !@is_readable($log))
		{
			return $this->item('php_errors', 'unknown');
		}
		$size = filesize($log);
		$h = @fopen($log, 'r');
		if (!$h)
		{
			return $this->item('php_errors', 'unknown');
		}
		if ($size > 2097152)
		{
			fseek($h, -2097152, SEEK_END);
			fgets($h);
		}
		$since = time() - 86400;
		$fatal = $warn = 0;
		$top = [];
		while (($line = fgets($h)) !== false)
		{
			if (!preg_match('/^\[([^\]]+)\]\s+PHP (Fatal error|Parse error|Warning|Notice|Deprecated):\s+(.{0,100}?)(?: in (\S+)(?: on line |:)(\d+))?/', $line, $m))
			{
				continue;
			}
			$time = strtotime($m[1]);
			if ($time === false || $time < $since)
			{
				continue;
			}
			$serious = in_array($m[2], ['Fatal error', 'Parse error'], true);
			$serious ? $fatal++ : $warn++;
			$where = isset($m[4]) ? ' (' . $this->relative($m[4]) . ':' . $m[5] . ')' : '';
			$key = $m[2] . ': ' . trim($m[3]) . $where;
			$top[$key] = (isset($top[$key]) ? $top[$key] : 0) + 1;
		}
		fclose($h);
		arsort($top);
		$list = [];
		foreach (array_slice($top, 0, 5, true) as $k => $n)
		{
			$list[] = ['key' => 'CALADRIUS_LOG_LINE', 'args' => [$k, $n]];
		}
		$state = $fatal ? 'bad' : ($warn > 20 ? 'warn' : 'ok');
		return $this->item('php_errors', $state, [$fatal, $warn], $list);
	}

	// ---------------------------------------------------------------- support dates

	/** phpBB's own update check, from its cache only: Caladrius never asks phpbb.com itself. */
	protected function phpbb_version()
	{
		try
		{
			$updates = $this->version_helper->get_suggested_updates(false, true);
		}
		catch (\Throwable $e)
		{
			return $this->item('phpbb_version', 'unknown', [$this->config['version']]);
		}
		if (empty($updates))
		{
			return $this->item('phpbb_version', 'ok', [$this->config['version']]);
		}
		$newest = '';
		foreach ($updates as $u)
		{
			if (!empty($u['current']) && ($newest === '' || phpbb_version_compare($u['current'], $newest, '>')))
			{
				$newest = $u['current'];
			}
		}
		return $this->item('phpbb_version', 'warn', [$this->config['version'], $newest]);
	}

	protected function php_version()
	{
		$branch = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		if (!isset(self::PHP_EOL[$branch]))
		{
			return $this->item('php_version', 'unknown', [PHP_VERSION]);
		}
		$end = strtotime(self::PHP_EOL[$branch] . ' 23:59:59 UTC');
		$days = (int) floor(($end - time()) / 86400);
		$date = self::PHP_EOL[$branch];
		if ($days < 0)
		{
			return $this->item('php_version', 'bad', [PHP_VERSION, $date]);
		}
		if ($days < 120)
		{
			return $this->item('php_version', 'warn', [PHP_VERSION, $date, $days]);
		}
		return $this->item('php_version', 'ok', [PHP_VERSION, $date]);
	}

	// ---------------------------------------------------------------- tampering check

	protected function manifest()
	{
		if ($this->manifest === null)
		{
			$file = __DIR__ . '/../manifests/phpbb-' . basename($this->config['version']) . '.json';
			$data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
			$this->manifest = is_array($data) ? $data : [];
		}
		return $this->manifest;
	}

	protected function matches_package($path)
	{
		$m = $this->manifest();
		return isset($m[$path]) && $this->hash($path) === $m[$path];
	}

	protected function hash($path)
	{
		if (!isset($this->hashes[$path]))
		{
			$this->hashes[$path] = (string) @hash_file('sha256', $this->root . $path);
		}
		return $this->hashes[$path];
	}

	/** phpBB's own files against the official package for this version. */
	protected function core_files()
	{
		$m = $this->manifest();
		if (!$m)
		{
			return $this->item('core_files', 'unknown', [$this->config['version']]);
		}
		$changed = $missing = $added = [];
		foreach ($m as $path => $sha)
		{
			if ($path === 'config.php')
			{
				continue;
			}
			if (!is_file($this->root . $path))
			{
				// docs/ and install/ are meant to be removed from a live board
				if (strpos($path, 'docs/') !== 0 && strpos($path, 'install/') !== 0)
				{
					$missing[] = ['key' => 'CALADRIUS_CORE_MISSING', 'args' => [$path]];
				}
				continue;
			}
			if ($this->hash($path) !== $sha)
			{
				$changed[] = ['key' => 'CALADRIUS_CORE_CHANGED', 'args' => [$path]];
			}
		}
		foreach (['adm', 'bin', 'config', 'includes', 'phpbb', 'styles/prosilver', 'styles/all', 'vendor'] as $dir)
		{
			foreach ($this->walk($dir) as $f)
			{
				if (!isset($m[$f]) && preg_match('/\.(php\d?|phtml|phar)$/i', $f))
				{
					$added[] = ['key' => 'CALADRIUS_CORE_ADDED', 'args' => [$f]];
				}
			}
		}
		$list = array_merge($changed, $missing, $added);
		$args = [count($m), $this->config['version'], count($list)];
		return $this->item('core_files', $list ? 'warn' : 'ok', $args, $list);
	}

	/** Scripts, hidden PHP and settings files in the folders that hold uploads. */
	protected function uploads()
	{
		$dirs = ['files', 'store', 'images/avatars/upload'];
		foreach (['upload_path', 'avatar_path'] as $c)
		{
			if (!empty($this->config[$c]))
			{
				$dirs[] = trim($this->config[$c], '/');
			}
		}
		$dirs = array_unique($dirs);
		$files = 0;
		$list = [];
		foreach ($dirs as $d)
		{
			foreach ($this->walk($d) as $f)
			{
				$files++;
				$base = basename($f);
				if (preg_match('/^(\.htaccess|\.htpasswd|\.user\.ini|php\.ini|php5\.ini|web\.config)$/i', $base))
				{
					if (!$this->matches_package($f) && $this->runs_code($f))
					{
						$list[] = ['key' => 'CALADRIUS_UPLOADS_CONFIG', 'args' => [$f]];
					}
					continue;
				}
				if (in_array($base, ['index.htm', 'index.html'], true))
				{
					continue;
				}
				if ($f === 'store/install_config.php')
				{
					$list[] = ['key' => 'CALADRIUS_UPLOADS_INSTALLCFG', 'args' => [$f]];
					continue;
				}
				if (preg_match('/\.(php\d?|phtml|phar|pht|pl|py|cgi|sh|asp|aspx|jsp)$/i', $base))
				{
					$list[] = ['key' => 'CALADRIUS_UPLOADS_SCRIPT', 'args' => [$f]];
					continue;
				}
				if (filesize($this->root . $f) <= 20971520)
				{
					$src = (string) @file_get_contents($this->root . $f);
					if (preg_match('/<\?php|<\?=\s*\$/i', $src))
					{
						$list[] = ['key' => 'CALADRIUS_UPLOADS_HIDDEN', 'args' => [$f]];
					}
				}
			}
		}
		return $this->item('uploads', $list ? 'bad' : 'ok', [$files], $list);
	}

	protected function runs_code($f)
	{
		$src = (string) @file_get_contents($this->root . $f, false, null, 0, 65536);
		return (bool) preg_match('/(AddHandler|SetHandler|AddType|ForceType|php_value|php_flag|php_admin|auto_prepend|auto_append|ExecCGI|allow_url_include)/i', $src);
	}

	/** Web-shell and crypto-miner patterns in PHP, JS and HTML that isn't part of the official package. */
	protected function shells()
	{
		$own = 'ext/personalgroupware/caladrius/';
		$scanned = 0;
		$list = [];
		foreach ($this->walk('', ['vendor', 'cache', 'docs']) as $f)
		{
			if (strpos($f, $own) === 0 || !preg_match('/\.(php\d?|phtml|inc|js|html?)$/i', $f))
			{
				continue;
			}
			if ($this->matches_package($f))
			{
				continue;
			}
			$src = @file_get_contents($this->root . $f);
			if ($src === false)
			{
				continue;
			}
			if (preg_match('/\.(php\d?|phtml|inc)$/i', $f))
			{
				$scanned++;
				foreach (self::PATTERNS as $why => $re)
				{
					if (preg_match($re, $src))
					{
						$list[] = ['key' => 'CALADRIUS_SHELLS_FOUND', 'args' => [$f, $why]];
						break;
					}
				}
			}
			if (preg_match(self::MINER, $src))
			{
				$list[] = ['key' => 'CALADRIUS_SHELLS_FOUND', 'args' => [$f, 'crypto-miner code']];
			}
		}
		return $this->item('shells', $list ? 'bad' : 'ok', [$scanned], $list);
	}

	/** Folders under ext/ that hold PHP but aren't installed extensions (old copies, trials, or hidden code). */
	protected function ext_folders()
	{
		$known = [];
		foreach ($this->rows('SELECT ext_name FROM ' . EXT_TABLE) as $r)
		{
			$known[$r['ext_name']] = true;
		}
		$list = [];
		foreach (glob($this->root . 'ext/*/*', GLOB_ONLYDIR) ?: [] as $d)
		{
			$name = substr(str_replace('\\', '/', $d), strlen($this->root) + 4);
			if (isset($known[$name]))
			{
				continue;
			}
			$php = 0;
			foreach ($this->walk('ext/' . $name) as $f)
			{
				if (preg_match('/\.php$/', $f))
				{
					$php++;
				}
			}
			if ($php)
			{
				$list[] = ['key' => 'CALADRIUS_EXT_FOLDER', 'args' => [$name, $php]];
			}
		}
		return $this->item('ext_folders', $list ? 'warn' : 'ok', [count($list)], $list);
	}

	/** Leftovers from setup that make a board easier to attack. */
	protected function setup()
	{
		$list = [];
		if (is_dir($this->root . 'install'))
		{
			$list[] = ['key' => 'CALADRIUS_SETUP_INSTALL'];
		}
		$mode = @fileperms($this->root . 'config.php');
		if ($mode !== false && ($mode & 0002))
		{
			$list[] = ['key' => 'CALADRIUS_SETUP_CONFIG'];
		}
		return $this->item('setup', $list ? 'bad' : 'ok', [], $list);
	}

	// ---------------------------------------------------------------- helpers

	/** Files under a folder of the board, as paths relative to the board root. */
	protected function walk($dir, array $skip = [])
	{
		$base = $this->root . ($dir === '' ? '' : rtrim($dir, '/') . '/');
		if (!is_dir($base))
		{
			return [];
		}
		$skip_abs = [];
		foreach ($skip as $s)
		{
			$skip_abs[] = $this->root . $s;
		}
		$out = [];
		$it = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
			new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
			function ($f) use ($skip_abs)
			{
				return !in_array(str_replace('\\', '/', $f->getPathname()), $skip_abs, true) && !$f->isLink();
			}
		), \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);
		foreach ($it as $f)
		{
			if ($f->isFile())
			{
				$out[] = substr(str_replace('\\', '/', $f->getPathname()), strlen($this->root));
			}
		}
		return $out;
	}

	protected function relative($path)
	{
		$path = str_replace('\\', '/', $path);
		return strpos($path, $this->root) === 0 ? substr($path, strlen($this->root)) : basename($path);
	}
}

