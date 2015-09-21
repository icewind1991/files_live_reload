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

namespace OCA\Files_Live_Reload\Listener;

use OC\Files\View;

class Listener {
	/**
	 * @var View
	 */
	private $view;

	/**
	 * @var MulticastBuffer
	 */
	private $buffer;

	/**
	 * Listener constructor.
	 *
	 * @param View $view
	 * @param MulticastBuffer $buffer
	 */
	public function __construct(View $view, MulticastBuffer $buffer) {
		$this->view = $view;
		$this->buffer = $buffer;
	}

	public function write($params) {
		$path = $params['path'];
		$this->buffer->push([
			'event' => 'write',
			'path' => $this->view->getAbsolutePath($path)
		]);
	}

	public function rename($params) {
		$source = $params['oldpath'];
		$target = $params['newpath'];
		$this->buffer->push([
			'event' => 'rename',
			'source' => $this->view->getAbsolutePath($source),
			'target' => $this->view->getAbsolutePath($target)
		]);
	}

	public function delete($params) {
		$path = $params['path'];
		$this->buffer->push([
			'event' => 'delete',
			'path' => $this->view->getAbsolutePath($path)
		]);
	}
}
