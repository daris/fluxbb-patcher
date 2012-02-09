<?php

return array(

'Backup created redirect'		=> 'Backup created. Redirecting …',
'Important'						=> 'Important',
'Directories not writable info'	=> 'The following directories are not writable',
'Files not writable info'		=> 'The following files are not writable',
'Disabled features info'		=> 'Some features might be disabled until you correct permissions to these directories.',
'Directory not writable'		=> 'The %s directory is not writable.',
'File not writable'				=> 'The %s file is not writable.',
'File does not exist'			=> 'The %s file does not exist.',
'Invalid mod dir'				=> 'This directory does not have a valid mod package.',
'Cannot write to'				=> 'Cannot write file to %s.',
'Failed to extract file'		=> 'Failed to extract file',

'Can\'t copy file'				=> 'Can\'t copy "%s" file to "%s".',

'Actions'						=> 'Actions',
'NOT DONE'						=> 'NOT DONE',
'DONE'							=> 'DONE',
'REVERTED'						=> 'REVERTED',

'Patcher head'					=> 'Patcher plugin v%s',

'Mod installation'				=> 'Mod installation',
'Mod installation status'		=> 'Mod installation status',
'Mod requirements'				=> 'Mod requirements',
'Files to upload'				=> 'Files to upload',
'Files to upload info'			=> 'Following files have to be uploaded to FluxBB root directory, however there are some files that does not exist. The upload step defined in readme file probably points to invalid file location. Report this list to mod author as it does not allow you to install this mod using Patcher.',
'Directories'					=> 'Directories',
'Directories info'				=> 'This mod requires the following directories to be writable in order to install/uninstall it.',
'Affected files info'			=> 'List of files that will be modified in installing/uninstalling mod process. All of them must be writable.',
'Missing strings'				=> 'Missing strings',
'Missing strings info'			=> 'Missing strings info',
'Missing string'				=> 'Missing string',
'Not exists'					=> 'Not exists',
'Not writable'					=> 'Not writable',
'Found'							=> 'Found',
'Writable'						=> 'Writable',
'Created'						=> 'Created',
'Can\'t create'					=> 'Can\'t create',

'Mod uninstallation'			=> 'Mod uninstallation',
'Warning'						=> 'Warning',
'Uninstall warning'				=> 'If you uninstall a mod, any data associated with that mod will be permanently deleted from the database and cannot be restored by re-installing the mod.',

'Unmet requirements'			=> 'Unmet requirements',
'Check again'					=> 'Check again',
'Unmet requirements info'		=> 'There are some unmet requirements that have to be corrected before you be able to install this mod. When you fix these issues, click Check again button.',
'Congratulations'				=> 'Congratulations',
'Mod installed'					=> 'Successfully installed new modification.',
'Mod uninstalled'				=> 'Successfully removed modification.',
'Mod enabled'					=> 'Successfully enabled this modification.',
'Mod disabled'					=> 'Successfully disabled this modification.',
'Mod updated'					=> 'Successfully updated this modification.',
'Install failed'				=> 'Install failed',
'Uninstall failed'				=> 'Uninstall failed',
'Enable failed'					=> 'Enable failed',
'Disable failed'				=> 'Disable failed',
'Update failed'					=> 'Update failed',
'Installing'					=> 'Installing modification',
'Uninstalling'					=> 'Uninstalling modification',
'Enabling'						=> 'Enabling modification',
'Disabling'						=> 'Disabling modification',
'Updating'						=> 'Updating modification',
'Mod patching failed'			=> 'There were some steps that failed to execute and in that case modification you tried to install might not working properly. If you are uncertain about consequences of that, use Uninstall button to uninstall this modification.',
'What to do now'				=> 'What to do now',
'Mod patching failed info 1'	=> 'If you know PHP language, you can try to correct steps that failed in modification readme.txt and click Update button to make changes again.',

'Database prepared for'			=> 'Your database has been successfully prepared for %s mod',
'Database restored'				=> 'Your database has been successfully restored',
'Database not restored'			=> 'Your database has not been restored, as the author of this mod have not implemented restore function',
'Patching file'					=> 'Patching file %s',
'Running'						=> 'Running %s',
'Running code'					=> 'Running code',
'Deleting'						=> 'Deleting %s',
'Num changes'					=> '%s changes',
'Num changes reverted'			=> '%s changes reverted',
'Num failed'					=> '%s failed',
'Uploading files'				=> 'Uploading files',
'Num files uploaded'			=> '%s files uploaded',
'Deleting files'				=> 'Deleting files',
'Num files deleted'				=> '%s files deleted',
'Final instructions'			=> 'Final instructions',

'Show log'						=> 'Show log',
'Return to mod list'			=> 'Return to mod list',

'Mod already installed'			=> 'Modification already installed.',
'Mod already uninstalled'		=> 'Modification already uninstalled.',
'Mod already enabled'			=> 'Modification already enabled.',
'Mod already disabled'			=> 'Modification already disabled.',

'Modification overview'			=> 'Modification overview',
'Description'					=> 'Description',
'Warning'						=> 'Warning',
'Unsupported version'			=> 'The mod you are about to install was not made specifically to support your current version of FluxBB (%s). This mod supports FluxBB versions: %s. If you are uncertain about installing the mod due to this potential version conflict, contact the mod author.',
'Unsupported version info'		=> 'This mod you was not made specifically to support your current version of FluxBB.',
'Following files not writable'	=> 'Following files are not writable: %s. Please check permission settings for these files before proceeding.',
'Update info'					=> 'You are going to install an outdated version of this modification.',
'Download update'				=> 'Download update (%s)',
'New version available'			=> 'New version available.',
'New Patcher version available'	=> 'You are using an outdated version of Patcher. A newer version (%s) is available at %s.',
'Resources page'				=> 'FluxBB resources page',
'Download and install'			=> 'Download and install',
'Download and install update'	=> 'Download and install update',
'Install old version'			=> 'Install old version',

'Manage backups legend'			=> 'Manage backups',
'Backup files'					=> 'Backup files',
'Backup files info' 			=> 'Select Yes if you want to create a backup before patching FluxBB files.',
'Backup filename'				=> 'Backup filename',
'Backup filename info'			=> 'Filename of new backup.',
'Make backup'					=> 'Make backup',

'Backup tool info'				=> 'This tool makes a backup of FluxBB root, include and lang/English directory. All backups are placed in <strong>backups</strong> directory inside FluxBB root directory.',

'Revert from backup'			=> 'Revert from backup',
'Revert'						=> 'Revert',
'Revert info'					=> 'Use this form to revert FluxBB files from backup.',
'Revert info 2'					=> 'Reverting files will not uninstall any mods from databse. It will only revert all changes of files that was made by installing mods.',
'No backups'					=> 'You have not created any backups yet. If you want to, use form above.',

'Upload modification legend'	=> 'Upload modification',
'Upload package'				=> 'Upload package',
'Upload'						=> 'Upload',
'Upload package info'			=> 'You can use this form to upload modification ZIP package and install it.',
'Mods directory not writable'	=> 'Mods directory is not writable. PHP must have write permission to this directory in order to upload modification package.',

'Mods failed to uninstall'		=> 'Mods that failed to uninstall',
'Mods to update'				=> 'Modifications available to update',
'Installed mods'				=> 'Installed modifications',
'Mods not installed'			=> 'Modifications not installed',
'Mods to download'				=> 'Modifications available to download from FluxBB repository',

'No installed mods'				=> 'There are no mods installed.',
'No mods not installed'			=> 'There are no mods available for installing.',
'No mods to download'			=> 'List of mods available to download is empty.',

'Modifications'					=> 'Modifications',
'Mod title'						=> 'Mod title',
'by'							=> 'by',
'Version'						=> 'Version',
'Works on FluxBB'				=> 'Works on FluxBB',
'Status'						=> 'Status',
'Action'						=> 'Action',

'Installed'						=> 'Installed',
'Not installed'					=> 'Not installed',
'Install'						=> 'Install',
'Uninstall'						=> 'Uninstall',
'Try again to uninstall'		=> 'Try again to uninstall',
'Update'						=> 'Update',
'Enable'						=> 'Enable',
'Disable'						=> 'Disable',
'Enabled'						=> 'Enabled',
'Disabled'						=> 'Disabled',

'Release date'					=> 'Release date',
'Affected files'				=> 'Affected files',
'Affects DB'					=> 'Affects DB',
'Notes'							=> 'Notes',
'DISCLAIMER'					=> 'DISCLAIMER',
'Yes'							=> 'Yes',
'No'							=> 'No',

'No modifications'				=> 'No modifications found in <strong>%s</strong> directory.',

'File does not exist error'		=> 'File does not exist',
'Can\'t delete file error'		=> 'Can\'t delete file',
'Can\'t create zip archive.'	=> 'Can\'t create zip archive.',
'File already exists'			=> 'File "%s" already exists.',

'Files reverted redirect'		=> 'Files reverted from backup. Redirecting …',
'File was not sent'				=> 'File was not sent',
'Upload ZIP archive only'		=> 'You can upload only ZIP archive',
'Directory already exists'		=> 'Directory "%s" already exists',
'Can\'t create mod directory'	=> 'Can\'t create directory "%s" in mods directory',
'Can\'t open the mod package' 	=> 'Can\'t open the modification package.',

'Which readme file message'		=> 'Modification files were successfully unpacked to "%s" directory, but Patcher does not know which readme file should be used for patching. Click on one of following readme files:',

'Modification updated redirect'	=> 'Modification updated. Redirecting …',
'Modification uploaded redirect'=> 'Modification uploaded. Redirecting …',
'Modification downloaded redirect'=> 'Modification downloaded. Redirecting …',

'Already patched'				=> 'A %s file of %s mod was already installed. Patching it next time could make a conflicts. Do not try to patch second time unless you know what are you doing.',
'Patch again'					=> 'Patch again',

'Check for updates'				=> 'Check for updates',
'Check for updates info'		=> '(automatically checking in every hour)',

// Actions
'OPEN'							=> 'OPEN',
'FIND'							=> 'FIND',
'REPLACE'						=> 'REPLACE',
'AFTER ADD'						=> 'AFTER ADD',
'BEFORE ADD'					=> 'BEFORE ADD',
'AT THE END OF FILE ADD'		=> 'AT THE END OF FILE ADD',
'RUN'							=> 'RUN',
'DELETE'						=> 'DELETE',
'RENAME'						=> 'RENAME',
'RUN CODE'						=> 'RUN CODE',

'Root directory not writable message' => 'FluxBB root directory is not writable.<br /><br />The basic Linux server permission settings does not allow to modify files via PHP.<br />There are two ways of making Patcher to do this:<br />1. Change chmod of each FluxBB directory to 0777 (and apply for each file) - but it is a seciurity issue and I suggest you to do step 2 instead :)<br />2. Enable FTP layer by renaming plugins/patcher/config_sample.php to config.php and filling the FTP login details. This way Patcher will use a FTP account to make changes to the files.<br /><br />More details about permission problem:<br /><a href="http://www.joomlaholic.com/forum/showthread.php?787-File-Ownership-Problems">http://www.joomlaholic.com/forum/showthread.php?787-File-Ownership-Problems</a>',

);
