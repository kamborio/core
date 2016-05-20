<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Authentication\Token;

use JsonSerializable;

interface IToken extends JsonSerializable {

	const TEMPORARY_TOKEN = 0;
	const PERMANENT_TOKEN = 1;

	/**
	 * Get the token ID
	 *
	 * @return int
	 */
	public function getId();

	/**
	 * Get the user UID
	 *
	 * @return string
	 */
	public function getUID();

	/**
	 * Get the (encrypted) login password
	 *
	 * @return string
	 */
	public function getPassword();
}
