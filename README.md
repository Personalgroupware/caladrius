# Caladrius for phpBB

**A plain-English health page for your board.** Caladrius adds one page to the admin control panel that answers "is everything OK with my forum?" It checks the things that quietly stop working, and whether phpBB's own files have been tampered with.

It only reads. It never changes posts, users, files or settings, and Caladrius itself sends nothing anywhere. (phpBB's own extension update check does fetch one small file from personalgroupware.com, see Updates below.)

*Named after the caladrius, the white bird of Roman and medieval legend that could tell with one look whether a sick person would get better.* A [Personalgroupware](https://www.personalgroupware.com/caladrius/) product.

## What it checks

**Behind the scenes**
- **Scheduled jobs:** when each of phpBB's jobs last ran, and whether any have stopped or a stuck lock is holding them up.
- **Email queue:** how many emails are waiting, and whether board email is switched on.
- **Database size:** the largest tables, and a sessions table that keeps growing (a sign tidying has stopped). MySQL and MariaDB only.
- **Errors logged by phpBB:** critical entries in the last 24 hours.
- **PHP errors on the server:** serious errors and warnings in the last 24 hours, when the host lets PHP read its error log.

**Support dates**
- **phpBB version:** from phpBB's own update check. Caladrius only reads phpBB's cached result and never contacts phpbb.com itself.
- **PHP version:** how long your PHP version still gets security fixes.

**Tampering check**
- **phpBB's own files** compared with the official package for your version (SHA-256 fingerprints shipped with the extension).
- **Upload folders:** scripts, PHP hidden inside "pictures", settings files that could make uploads run as code, and the installer's leftover `store/install_config.php`. Backup copies of extensions that update tools such as Extension Toolbox keep in `store/` show as amber, not red, while `store/.htaccess` keeps the web out (from 0.1.6-dev). The web shell check still looks inside them.
- **Web shell and miner patterns** in PHP, JavaScript and HTML that isn't part of the official package.
- **Unknown extension folders** that hold PHP but aren't installed extensions.
- **Setup:** a leftover `install/` folder, or a `config.php` anyone on the server can change.

Each item is green, amber or red, with one line on what to do about anything that isn't green.

## Mark as checked

From 0.1.5-dev, an amber or red item you've looked at and found harmless can be marked as checked. It stops counting towards the summary but stays on the page, greyed out, with who marked it, when, and an Undo button. **Hide checked items** / **Show checked items** next to the summary switches them out of view, and the summary always says how many are marked.

A mark only holds while the check finds exactly the same thing. If it finds something different (another changed file, a new error, a newer version) or the item gets worse, it comes back as a normal warning.

## When it runs

- Once a day through phpBB's scheduled jobs, and whenever you press **Check now**.
- Optional email alerts to the board founders when a check turns red. Off until you switch them on. They use the board's own email settings and queue.

## Updates

From 0.1.4-dev, phpBB tells you when a new version is out: Customise > Manage extensions shows it next to Caladrius, with a download link. This is phpBB's standard extension update check. When an admin opens that page, phpBB fetches https://www.personalgroupware.com/caladrius/version.json, which lists the latest version. The request carries nothing about your board, but like any web request it shows our server your server's IP address. Boards on 0.1.3-dev or older don't get the notice, so get those from the [releases page](https://github.com/Personalgroupware/caladrius/releases).

To update, upload the new files over the old ones and purge the board cache.

## Requirements

- phpBB 3.3.0 or newer (3.3.x)
- PHP 7.2 or newer
- File fingerprints are included for phpBB 3.3.15 and 3.3.19. On other versions the file comparison shows "Not checked" and everything else works.

## Install

1. Download the zip from [Releases](https://github.com/Personalgroupware/caladrius/releases) (not GitHub's green "Code" button, which names the folder wrongly).
2. Unzip it and upload the `personalgroupware` folder into your board's `ext/` folder, so you have `ext/personalgroupware/caladrius/`.
3. In the admin panel go to **Customise > Manage extensions** and enable **Caladrius**.
4. Open **Extensions > Caladrius > Board health**.

## Uninstall

Disable the extension, then press **Delete data**. Caladrius removes its settings, stored results and admin page, and leaves nothing behind.

## Feedback

Questions, false alarms or things it missed: reply on the [phpBB.com development topic](https://www.phpbb.com/community/viewtopic.php?t=2677153) or open an [issue here on GitHub](https://github.com/Personalgroupware/caladrius/issues).

## License

[GNU General Public License v2](license.txt)
