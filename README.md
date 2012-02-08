
# FluxBB Patcher 2.0-dev

This script allows you to easily install and uninstall FluxBB modifications.

1. Upload directories to FluxBB root directory.
2. Go to Admin Panel and click on Patcher plugin.

## Permission issues
The basic Linux server permission settings does not allow to modify files via PHP. There are two ways of making Patcher to do this:

1. Change chmod of FluxBB root directory to 0777 - but it is a security issue, I suggest to do step 2 instead :)
2. Enable FTP layer by renaming plugins/patcher/config_sample.php to config.php (in the same directory) and filling the FTP login details.

Example below:

	// Comment out if you want to disable FTP layer
	$ftpData = array(
		'host' => '127.0.0.1',
		'port' => 21,
		'user' => 'fluxbb',
		'pass' => 'pass',
		'path' => 'html/fluxbb/',
	);

More details about permission problems: [File-Ownership-Problems](http://www.joomlaholic.com/forum/showthread.php?787-File-Ownership-Problems)

## Note
There is a PCLZIP library included, but I feel it is slower than ZIP extension for PHP. If you want to speedup extracting process install and enable ZIP extension.

## Known issues
- ajax post edit mod is not compatible with topictags (fails on viewtopic.php file, author of topictags should delete <div class="postmsg"> from its readme)

## Tips
- If you have some modification installed and you have changed something in its readme file, you can just click uninstall link (Do not click Uninstall button), change in the address bar "action=uninstall" to "action=update", press Enter and click Update button.

## FluxBB not usable
If Patcher fails with installing some mod and it will make your FluxBB not usable (for example causing fatal error) you can upload revert_backup.php.txt file to your server, rename it to revert_backup.php and run in browser. It will allow you to revert modified files from backup.
