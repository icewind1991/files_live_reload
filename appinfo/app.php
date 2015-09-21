<?php

$application = new \OCA\Files_Live_Reload\AppInfo\Application();

\OCP\Util::connectHook('OC_Filesystem', 'setup', $application, 'setupHooks');

$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OCA\Files::loadAdditionalScripts',
	function () {
		\OCP\Util::addScript('files_live_reload', '../build/build');
	}
);
