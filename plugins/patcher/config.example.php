<?php

// Rename this file to config.php

// Uncomment when you want to disable download feature (used to fetch modifications from repository)
// define('PATCHER_NO_DOWNLOAD', 1);

// Warning: The filesystem type and zip type values are case sensitive!
return array(
	'filesystem' => array(
		'type'		=> 'FTP',			// Either "Native" (PHP implementation) or "FTP" (Joomla FTP layer)
		'options'	=> array(
			// Following options are only used for FTP
			'host' => '127.0.0.1',		// FTP server hostname (example: fluxbb.org)
			'port' => 21,				// FTP post (you probably do not need to change this value)
			'user' => 'root',			// FTP account username
			'pass' => '123456',			// FTP account password
			'path' => 'public_html/',	// Path to the FluxBB directory (relative to the root FTP directory)
		)
	),
	'zip'		=> array(
		'type'		=> 'Native',		// Either "Native" (ZipArchive class) or "Pcl" (PclZip library)
		'options' 	=> array()
	)
);
