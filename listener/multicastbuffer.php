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

use OC\Hooks\Emitter;
use OC\Hooks\EmitterTrait;
use OCP\IMemcache;

/**
 * Memcache based message queue using a circular buffer
 *
 * Allows multiple processes to read the same message
 */
class MulticastBuffer implements Emitter {
	use EmitterTrait;

	const RESULT_PUSHED = 2;
	const RESULT_PROCESSED = 1;
	const RESULT_NO_MESSAGE = 0;
	const ERROR_ID_ALREADY_USED = -2;
	const ERROR_QUEUE_FULL = -3;

	const BUFFER_SIZE = 1024;
	const MAX_TRIES = 10;
	const TRY_PAUSE = 10; // time to sleep after a failed try in ms

	/**
	 * @var IMemcache
	 */
	private $memCache;

	/**
	 * @var int
	 */
	private $readPointer;

	/**
	 * Queue constructor.
	 *
	 * @param IMemcache $memCache
	 * @param int $readPointer
	 */
	public function __construct(IMemcache $memCache, $readPointer = -1) {
		$this->memCache = $memCache;
		$this->readPointer = $readPointer;
	}

	/**
	 * Pull a message from the queue and emit it
	 *
	 * @return int self::RESULT_PROCESSED, self::RESULT_NO_MESSAGE
	 */
	public function poll() {
		$lastMessage = $this->memCache->get('writePointer');
		if ($this->readPointer === -1) {
			$this->readPointer = $lastMessage;
		}

		if ($lastMessage !== $this->readPointer) {
			$messageId = ($this->readPointer + 1) % self::BUFFER_SIZE;
			$message = $this->memCache->get($messageId);
			if (!$message) {
				return self::RESULT_NO_MESSAGE;
			}
			$this->emit('Queue', 'message', [$message]);
			$this->readPointer = $messageId;
			return self::RESULT_PROCESSED;
		} else {
			return self::RESULT_NO_MESSAGE;
		}
	}

	/**
	 * Push a message to the queue
	 *
	 * Will not retry on race conditions
	 *
	 * @param mixed $message
	 * @return int self::RESULT_PUSHED or self::ERROR_ID_ALREADY_USED
	 * @throws \Exception
	 */
	private function pushMessage($message) {
		if (!$this->memCache->add('pushLock', true)) {
			return self::ERROR_ID_ALREADY_USED;
		}
		$lastMessage = $this->memCache->get('writePointer');
		$messageId = ($lastMessage + 1) % self::BUFFER_SIZE;
		$this->memCache->set($messageId, $message);
		$this->memCache->set('writePointer', $messageId);
		$this->memCache->remove('pushLock');
		return self::RESULT_PUSHED;
	}

	/**
	 * Push a message to the queue
	 *
	 * Will retry on race conditions
	 *
	 * @param mixed $message
	 * @throws \Exception
	 */
	public function push($message) {
		$tries = 0;
		while ($tries < self::MAX_TRIES) {
			if ($this->pushMessage($message) !== self::ERROR_ID_ALREADY_USED) {
				return;
			}
			$tries++;
			usleep(self::TRY_PAUSE * 1000);
		}
	}
}
