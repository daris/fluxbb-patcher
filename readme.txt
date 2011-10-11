FluxBB Patcher 2.0-dev

This script allows you to easily install and uninstall FluxBB modifications.

1. Upload directories to FluxBB root directory.
2. Go to Admin Panel and click on Patcher plugin.


--- IMPORTANT ---
In order to install mods, FluxBB root directory and subdirectories must be WRITABLE.

If you want to create backups of FluxBB files backups directory (in FluxBB root directory) 
must be writable.

If you want to upload, download or update mod, mods directory must be writable.

Note:
There is a PCLZIP library included, but I feel it is slower than ZIP extension for PHP. 
If you want to speedup extracting process install and enable ZIP extension.

Known issues:
- ajax post edit mod is not compatible with topictags (fails on viewtopic.php file, author of 
topictags should delete <div class="postmsg"> from its readme)

Tips:
- If you have some modification installed and you have changed something in its readme file, 
you can just click uninstall link (Do not click Uninstall button), change in the address 
bar "action=uninstall" to "action=update", press Enter and click Update button.

FluxBB doesn't work:
If Patcher fails with installing some mod and it will make your FluxBB not usable (for example
causing fatal error) you can upload revert_backup.php.txt file to your server, rename it to
revert_backup.php and run in browser. It will allow you to revert modified files from backup.