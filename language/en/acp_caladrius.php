<?php
/**
 *
 * Caladrius. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026, Wilson Logan, https://www.personalgroupware.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'CALADRIUS_INTRO'			=> 'Caladrius checks the things that quietly stop working on a board, and whether phpBB’s own files have been tampered with. It only reads, and it sends nothing anywhere.',
	'CALADRIUS_LAST_RUN'		=> 'Last checked %1$s, in %2$s seconds.',
	'CALADRIUS_RUN_NOW'			=> 'Check now',
	'CALADRIUS_SUMMARY_OK'		=> 'All green. Nothing needs attention.',
	'CALADRIUS_SUMMARY_PROBLEMS'	=> [
		1	=> '%d thing needs attention.',
		2	=> '%d things need attention.',
	],
	'CALADRIUS_WHAT_TO_DO'		=> 'What to do',
	'CALADRIUS_LIST_MORE'		=> 'and %d more',

	'CALADRIUS_STATE_OK'		=> 'OK',
	'CALADRIUS_STATE_WARN'		=> 'Check',
	'CALADRIUS_STATE_BAD'		=> 'Act now',
	'CALADRIUS_STATE_UNKNOWN'	=> 'Not checked',

	'CALADRIUS_GROUP_UPKEEP'	=> 'Behind the scenes',
	'CALADRIUS_GROUP_VERSIONS'	=> 'Support dates',
	'CALADRIUS_GROUP_TAMPER'	=> 'Tampering check',

	'CALADRIUS_AGO_MINUTES'		=> [
		1	=> '%d minute ago',
		2	=> '%d minutes ago',
	],
	'CALADRIUS_AGO_HOURS'		=> [
		1	=> '%d hour ago',
		2	=> '%d hours ago',
	],
	'CALADRIUS_AGO_DAYS'		=> [
		1	=> '%d day ago',
		2	=> '%d days ago',
	],
	'CALADRIUS_AGO_NEVER'		=> 'never',

	'CALADRIUS_EMAIL_TITLE'		=> 'Email alerts',
	'CALADRIUS_EMAIL'			=> 'Email the board founders when a check turns red',
	'CALADRIUS_EMAIL_EXPLAIN'	=> 'Caladrius runs its checks once a day using phpBB’s scheduled jobs. With this on, it emails the board founders when a check turns red, using the board’s own email settings. It stays quiet while nothing changes.',
	'CALADRIUS_EMAIL_BOARD_OFF'	=> 'Board email is switched off, so no alerts can be sent.',
	'CALADRIUS_SAVED'			=> 'Settings saved.',

	// Scheduled jobs
	'CALADRIUS_CRON_TITLE'		=> 'Scheduled jobs',
	'CALADRIUS_CRON_OK'			=> 'Running normally. The most recent job ran %s.',
	'CALADRIUS_CRON_WARN'		=> 'Some jobs are running late. The most recent job ran %s.',
	'CALADRIUS_CRON_BAD'		=> 'Some jobs have stopped. The most recent job ran %s.',
	'CALADRIUS_CRON_UNKNOWN'	=> 'No scheduled job has run yet. That’s normal on a brand-new board.',
	'CALADRIUS_CRON_TODO'		=> 'phpBB tidies up old logins and sends its emails through these jobs. They only run when people visit the board, unless the server runs them. If they have stopped, ask your host to run “php bin/phpbbcli.php cron:run” every five minutes, then switch on “Run periodic tasks from system cron” under Server settings.',
	'CALADRIUS_CRON_JOB'		=> '%1$s last ran %2$s',
	'CALADRIUS_CRON_LOCK'		=> 'A job started %s and still holds the lock, which stops the others',

	// Email queue
	'CALADRIUS_QUEUE_TITLE'		=> 'Email queue',
	'CALADRIUS_QUEUE_OK'		=> [
		0	=> 'Empty. Emails are going out.',
		1	=> '%1$d email waiting, which is normal. The queue was last sent %2$s.',
		2	=> '%1$d emails waiting, which is normal. The queue was last sent %2$s.',
	],
	'CALADRIUS_QUEUE_WARN'		=> [
		0	=> 'Board email is switched off.',
		1	=> '%1$d email is waiting. The queue was last sent %2$s.',
		2	=> '%1$d emails are waiting. The queue was last sent %2$s.',
	],
	'CALADRIUS_QUEUE_BAD'		=> '%1$d emails are waiting and the queue was last sent %2$s. Members are probably not getting their emails.',
	'CALADRIUS_QUEUE_UNKNOWN'	=> 'Couldn’t be checked.',
	'CALADRIUS_QUEUE_OFF'		=> 'No emails are sent at all, including password resets and notifications',
	'CALADRIUS_QUEUE_TODO'		=> 'If board email is off, switch it on in General > Board configuration > Email settings. Otherwise the queue is sent by the scheduled jobs, so check those first, then send a test email from Email settings.',

	// Database
	'CALADRIUS_DATABASE_TITLE'	=> 'Database size',
	'CALADRIUS_DATABASE_OK'		=> 'The database uses %s. The largest tables are listed below.',
	'CALADRIUS_DATABASE_WARN'	=> 'The database uses %1$s. The sessions table holds %2$d rows, which usually means old logins aren’t being tidied away.',
	'CALADRIUS_DATABASE_UNKNOWN'	=> 'Table sizes can only be read on MySQL and MariaDB.',
	'CALADRIUS_DATABASE_TODO'	=> 'Old logins are removed by the scheduled jobs. Once those run again, the table shrinks by itself.',
	'CALADRIUS_DB_TABLE'		=> '%1$s: %2$s MB',

	// phpBB error log
	'CALADRIUS_PHPBB_LOG_TITLE'	=> 'Errors logged by phpBB',
	'CALADRIUS_PHPBB_LOG_OK'	=> 'None in the last 24 hours.',
	'CALADRIUS_PHPBB_LOG_WARN'	=> [
		1	=> '%d error in the last 24 hours.',
		2	=> '%d errors in the last 24 hours.',
	],
	'CALADRIUS_PHPBB_LOG_UNKNOWN'	=> 'Couldn’t be checked.',
	'CALADRIUS_PHPBB_LOG_TODO'	=> 'See Maintenance > Error log for the full messages. Email errors usually mean the email settings need checking.',
	'CALADRIUS_LOG_LINE'		=> '%1$s (%2$d times)',
	'CALADRIUS_LOG_ENTRY'		=> [
		1	=> '%2$s',
		2	=> '%2$s (%1$d times)',
	],
	'CALADRIUS_LOG_REFUSED'		=> [
		1	=> 'Not counted: %d page phpBB refused (an expired link, a bot, or a deleted topic). That’s normal.',
		2	=> 'Not counted: %d pages phpBB refused (expired links, bots, or deleted topics). That’s normal.',
	],

	// PHP errors
	'CALADRIUS_PHP_ERRORS_TITLE'	=> 'PHP errors on the server',
	'CALADRIUS_PHP_ERRORS_OK'		=> 'No serious errors in the last 24 hours. Warnings: %2$d.',
	'CALADRIUS_PHP_ERRORS_WARN'		=> 'No serious errors, but %2$d warnings in the last 24 hours.',
	'CALADRIUS_PHP_ERRORS_BAD'		=> '%1$d serious errors in the last 24 hours. Some visitors probably saw an error page.',
	'CALADRIUS_PHP_ERRORS_UNKNOWN'	=> 'Your host doesn’t let the board read the server’s error log, so this can’t be checked from here.',
	'CALADRIUS_PHP_ERRORS_TODO'		=> 'The most common messages are listed below, with the file each came from. A file under ext/ points to that extension, so check it has an update.',

	// phpBB version
	'CALADRIUS_PHPBB_VERSION_TITLE'		=> 'phpBB version',
	'CALADRIUS_PHPBB_VERSION_OK'		=> 'phpBB %s is up to date.',
	'CALADRIUS_PHPBB_VERSION_WARN'		=> 'This board runs phpBB %1$s. Version %2$s is available.',
	'CALADRIUS_PHPBB_VERSION_UNKNOWN'	=> 'This board runs phpBB %s. Open the admin panel’s front page once so phpBB checks for updates, then check again.',
	'CALADRIUS_PHPBB_VERSION_TODO'		=> 'Updates fix security problems. Back up first, try the update on a copy of the board if you can, and follow phpBB’s update guide.',

	// PHP version
	'CALADRIUS_PHP_VERSION_TITLE'	=> 'PHP version',
	'CALADRIUS_PHP_VERSION_OK'		=> 'PHP %1$s gets security fixes until %2$s.',
	'CALADRIUS_PHP_VERSION_WARN'	=> 'PHP %1$s stops getting security fixes on %2$s, in %3$d days.',
	'CALADRIUS_PHP_VERSION_BAD'		=> 'PHP %1$s stopped getting security fixes on %2$s.',
	'CALADRIUS_PHP_VERSION_UNKNOWN'	=> 'PHP %s isn’t in Caladrius’s list of support dates yet.',
	'CALADRIUS_PHP_VERSION_TODO'	=> 'Ask your host to move the board to a newer PHP version. Check first that your extensions and style support it.',

	// Core files
	'CALADRIUS_CORE_FILES_TITLE'	=> 'phpBB’s own files',
	'CALADRIUS_CORE_FILES_OK'		=> '%1$d files compared with the official phpBB %2$s package. None changed.',
	'CALADRIUS_CORE_FILES_WARN'		=> '%1$d files compared with the official phpBB %2$s package. These are different:',
	'CALADRIUS_CORE_FILES_UNKNOWN'	=> 'This version of Caladrius doesn’t include the fingerprints for phpBB %s, so this can’t be checked.',
	'CALADRIUS_CORE_FILES_TODO'		=> 'Changes to phpBB’s files are usually edits someone made on purpose. If nobody you know made these, have the files checked and replace them from the official package.',
	'CALADRIUS_CORE_CHANGED'		=> 'changed: %s',
	'CALADRIUS_CORE_MISSING'		=> 'missing: %s',
	'CALADRIUS_CORE_ADDED'			=> 'added script: %s',

	// Upload folders
	'CALADRIUS_UPLOADS_TITLE'		=> 'Upload folders',
	'CALADRIUS_UPLOADS_OK'			=> 'Nothing unusual in %d files.',
	'CALADRIUS_UPLOADS_BAD'			=> 'Found files that could run as programs.',
	'CALADRIUS_UPLOADS_UNKNOWN'		=> 'Couldn’t be checked.',
	'CALADRIUS_UPLOADS_TODO'		=> 'Upload folders should only hold pictures and attachments. Scripts or settings files there are a common way in for attackers. Remove them, then change the admin passwords.',
	'CALADRIUS_UPLOADS_SCRIPT'		=> 'script: %s',
	'CALADRIUS_UPLOADS_HIDDEN'		=> 'PHP code hidden in: %s',
	'CALADRIUS_UPLOADS_INSTALLCFG'	=> 'left over from installing or updating phpBB, and it can hold the database password. Delete it: %s',
	'CALADRIUS_UPLOADS_CONFIG'		=> 'settings file that can make files run as code: %s',

	// Web shells
	'CALADRIUS_SHELLS_TITLE'		=> 'Web shell and miner patterns',
	'CALADRIUS_SHELLS_OK'			=> [
		0 => 'No PHP files outside the official package to check.',
		1 => 'None found in the one PHP file outside the official package.',
		2 => 'None found in %d PHP files outside the official package.',
	],
	'CALADRIUS_SHELLS_BAD'			=> 'Found code that looks like a web shell or a crypto-miner.',
	'CALADRIUS_SHELLS_UNKNOWN'		=> 'Couldn’t be checked.',
	'CALADRIUS_SHELLS_TODO'			=> 'Have these files checked by someone you trust before deleting them. Some security extensions contain these patterns on purpose. If a file is malicious, someone has broken in, so change all passwords and look for other changes too.',
	'CALADRIUS_SHELLS_FOUND'		=> '%1$s (%2$s)',

	// Extension folders
	'CALADRIUS_EXT_FOLDERS_TITLE'	=> 'Unknown extension folders',
	'CALADRIUS_EXT_FOLDERS_OK'		=> 'Every folder under ext/ belongs to an extension phpBB knows about.',
	'CALADRIUS_EXT_FOLDERS_WARN'	=> [
		1	=> '%d folder under ext/ holds PHP files but isn’t an installed extension.',
		2	=> '%d folders under ext/ hold PHP files but aren’t installed extensions.',
	],
	'CALADRIUS_EXT_FOLDERS_UNKNOWN'	=> 'Couldn’t be checked.',
	'CALADRIUS_EXT_FOLDERS_TODO'	=> 'Old copies and trials do nothing while switched off, but they can hide unwanted code. Remove the ones you don’t need.',
	'CALADRIUS_EXT_FOLDER'			=> '%1$s (PHP files: %2$d)',

	// Setup
	'CALADRIUS_SETUP_TITLE'			=> 'Setup',
	'CALADRIUS_SETUP_OK'			=> 'No install folder, and config.php can’t be changed by everyone.',
	'CALADRIUS_SETUP_BAD'			=> 'Found a setup problem.',
	'CALADRIUS_SETUP_UNKNOWN'		=> 'Couldn’t be checked.',
	'CALADRIUS_SETUP_TODO'			=> 'Delete the install folder, and set config.php so only the board can read it (permission 640 or 644).',
	'CALADRIUS_SETUP_INSTALL'		=> 'The install/ folder is still on the server',
	'CALADRIUS_SETUP_CONFIG'		=> 'config.php can be changed by any user on the server',
]);
