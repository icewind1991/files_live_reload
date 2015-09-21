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

namespace OCA\Files_Live_Reload\Controller;

use OC\Files\View;
use OCA\Files_Live_Reload\Listener\EventSource;
use OCA\Files_Live_Reload\Listener\MulticastBuffer;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Class ListenController
 *
 * @package OCA\Files_Live_Reload\Controller
 */
class ListenController extends Controller {
	/**
	 * @var MulticastBuffer
	 */
	private $buffer;

	/**
	 * @var View
	 */
	private $view;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param \OCA\Files_Live_Reload\Listener\MulticastBuffer $buffer
	 */
	public function __construct(
		$AppName,
		IRequest $request,
		MulticastBuffer $buffer,
		View $view
	) {
		parent::__construct(
			$AppName,
			$request
		);
		$this->buffer = $buffer;
		$this->view = $view;
	}

	private function getInfo($relativePath) {
		$fileInfo = $this->view->getFileInfo($relativePath);
		return [
			'path' => $relativePath,
			'mtime' => $fileInfo->getMTime() * 1000,
			'mimetype' => $fileInfo->getMimetype(),
			'size' => $fileInfo->getSize(),
			'name' => $fileInfo->getName(),
			'etag' => $fileInfo->getEtag(),
			'permissions' => $fileInfo->getPermissions(),
			'type' => $fileInfo->getType(),
			'id' => $fileInfo->getId()
		];
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function listen() {
		$source = new EventSource();

		$source->send('_init_');

		$this->buffer->listen('Queue', 'message', function ($data) use ($source) {
			switch ($data['event']) {
				case 'write':
					$relativePath = $this->view->getRelativePath($data['path']);
					if ($relativePath) {
						$source->send('write', $this->getInfo($relativePath));
					}
					return;
				case 'rename':
					$relativeSource = $this->view->getRelativePath($data['source']);
					$relativeTarget = $this->view->getRelativePath($data['target']);
					if ($relativeSource and $relativeTarget) {
						$info = $this->getInfo($relativeTarget);
						$info['source'] = $relativeSource;
						$info['target'] = $relativeTarget;
						$source->send('rename', $info);
					}
					return;
				case 'delete':
					$relativePath = $this->view->getRelativePath($data['path']);
					if ($relativePath) {
						$source->send('delete', [
							'path' => $relativePath
						]);
					}
					return;
			}
		});

		for ($i = 0; $i < 100; $i++) {
			$this->buffer->poll();
			usleep(100 * 1000);
		}
		exit;
	}
}
