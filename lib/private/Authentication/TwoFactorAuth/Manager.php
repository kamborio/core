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

namespace OC\Authentication\TwoFactorAuth;

use OC;
use OC\App\AppManager;
use OCP\AppFramework\QueryException;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\ISession;
use OCP\IUser;

class Manager {

	const SESSION_UID_KEY = 'two_factor_auth_uid';

	/** @var AppManager */
	private $appManager;

	/** @var ISession */
	private $session;

	/**
	 * @param AppManager $appManager
	 * @param ISession $session
	 */
	public function __construct(AppManager $appManager, ISession $session) {
		$this->appManager = $appManager;
		$this->session = $session;
	}

	/**
	 * Determine whether the user must provide a second factor challenge
	 *
	 * @param IUser $user
	 * @return boolean
	 */
	public function isTwoFactorAuthenticated(IUser $user) {
		return count($this->getProviders($user)) > 0;
	}

	/**
	 * Get a 2FA provider by its ID
	 *
	 * @param IUser $user
	 * @param string $challengeProviderId
	 * @return IProvider|null
	 */
	public function getProvider(IUser $user, $challengeProviderId) {
		$providers = $this->getProviders($user);
		return $providers[$challengeProviderId] ? : null;
	}

	/**
	 * Get the list of 2FA providers for the given user
	 *
	 * @param IUser $user
	 * @return IProvider[]
	 */
	public function getProviders(IUser $user) {
		$allApps = $this->appManager->getEnabledAppsForUser($user);
		$providers = [];

		foreach ($allApps as $appId) {
			$info = $this->appManager->getAppInfo($appId);
			$providerClasses = $info['two-factor-providers'];
			foreach ($providerClasses as $class) {
				try {
					$provider = OC::$server->query($class);
					$providers[$provider->getId()] = $provider;
				} catch (QueryException $exc) {
					// Provider class can not be resolved, ignore it
				}
			}
		}

		return array_filter($providers, function ($provider) use ($user) {
			/* @var $provider IProvider */
			return $provider->isTwoFactorAuthEnabledForUser($user);
		});
	}

	/**
	 * Verify the given challenge
	 *
	 * @param string $providerId
	 * @param IUser $user
	 * @param string $challenge
	 * @return boolean
	 */
	public function verifyChallenge($providerId, IUser $user, $challenge) {
		$provider = $this->getProvider($user, $providerId);
		if (is_null($provider)) {
			return false;
		}

		$result = $provider->verifyChallenge($user, $challenge);
		if ($result) {
			$this->session->remove(self::SESSION_UID_KEY);
		}
		return $result;
	}

	/**
	 * Check if the currently logged in user needs to pass 2FA
	 *
	 * @return boolean
	 */
	public function needsSecondFactor() {
		return $this->session->exists(self::SESSION_UID_KEY);
	}

	/**
	 * Prepare the 2FA login (set session value)
	 *
	 * @param IUser $user
	 */
	public function prepareTwoFactorLogin(IUser $user) {
		$this->session->set(self::SESSION_UID_KEY, $user->getUID());
	}

}
