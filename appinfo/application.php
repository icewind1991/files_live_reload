<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Live_Reload\AppInfo;

use OC\Files\Filesystem;
use OCA\Files_Live_Reload\Listener\Listener;
use OCA\Files_Live_Reload\Listener\MulticastBuffer;
use OCP\AppFramework\App;
use OCP\Files\Storage;
use OCP\IContainer;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('files_live_reload', $urlParams);

		$container = $this->getContainer();

		$container->registerService('\OCA\Files_Live_Reload\Listener\MulticastBuffer', function (IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			return new MulticastBuffer(
				$server->getMemCacheFactory()->create('live_reload_buffer')
			);
		});

		$container->registerAlias('MulticastBuffer', '\OCA\Files_Live_Reload\Listener\MulticastBuffer');

		$container->registerService('\OC\Files\View', function () {
			return Filesystem::getView();
		});

		$container->registerAlias('View', '\OC\Files\View');
	}

	public function setupHooks() {
		$container = $this->getContainer();
		$listener = new Listener($container->query('View'), $container->query('MulticastBuffer'));
		\OCP\Util::connectHook('OC_Filesystem', 'post_write', $listener, 'write');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', $listener, 'rename');
		\OCP\Util::connectHook('OC_Filesystem', 'delete', $listener, 'delete');
	}
}
