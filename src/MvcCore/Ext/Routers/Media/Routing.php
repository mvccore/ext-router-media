<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Routers\Media;

use MvcCore\Ext\Routers;

trait Routing
{
	/**
	 * Process media site version before request is routed by `\MvcCore\Router`.
	 * - If there is special media site switching param in request object global
	 *   array `$_GET` and it's value is allowed as media site version:
	 *	 - Switch media site version in session by given switching param value 
	 *     and redirect to the same page with new media site version substring 
	 *     in url or with new media site version query string param.
	 * - If there is no media site version in session or if media site version 
	 *   from sesson is not allowed:
	 *   - Recognize media site version by `\Mobile_Detect` third party library 
	 *     and store recognized version in this context and in session namespace.
	 * - Else set up media site version from session into this context
	 * - Later - if detected media site version is not the same as requested 
	 *   media site version - redirect to detected version in this context.
	 * Return always `TRUE` and return `FALSE` if request is redirected.
	 * @return bool
	 */
	protected function preRouteMedia () {
		if (
			(($this->isGet && $this->routeGetRequestsOnly) || !$this->routeGetRequestsOnly) &&
			$this->switchUriParamMediaSiteVersion !== NULL
		) {
			// if there is detected in requested url media site version switching param,
			// store switching param value in session, remove param from `$_GET` 
			// and redirect to the same page with new media site version:
			if (!$this->manageMediaSwitchingAndRedirect()) return FALSE;

		} else if (
			(($this->isGet && $this->routeGetRequestsOnly) || !$this->routeGetRequestsOnly) && 
			$this->sessionMediaSiteVersion === NULL
		) {
			// if there is no session record about media site version:
			$this->manageMediaDetectionAndStoreInSession();
			// check if media site version is the same as local media site version:
			if (!$this->checkMediaVersionWithUrlAndRedirectIfNecessary()) return FALSE;

		} else {
			// if there is media site version in session already:
			$this->mediaSiteVersion = $this->sessionMediaSiteVersion;
			// check if media site version is the same as local media site version:
			if (!$this->checkMediaVersionWithUrlAndRedirectIfNecessary()) return FALSE;
		}

		// set up stored/detected media site version into request:
		$this->request->SetMediaSiteVersion($this->mediaSiteVersion);
		
		return TRUE;
	}

	/**
	 * Prepare media site version processing before request is routed by `\MvcCore\Router`:
	 * - prepare media site version from requested url
	 * - prepare media site version from session initialized by any previous request
	 * - detect if there is any special media site switching parameter in 
	 *   request object global array `$_GET` with name by 
	 *   `static::SWITCH_MEDIA_VERSION_URL_PARAM`
	 * @return bool
	 */
	protected function preRoutePrepareMedia () {
		//if ($this->stricModeBySession) { // check it with any strict session configuration to have more flexible navigations
			$sessStrictModeSwitchUrlParam = static::SWITCH_MEDIA_VERSION_URL_PARAM;
			if (isset($this->requestGlobalGet[$sessStrictModeSwitchUrlParam])) {
				$switchUriParamMediaSiteVersion = strtolower($this->requestGlobalGet[$sessStrictModeSwitchUrlParam]);
				if (isset($this->allowedSiteKeysAndUrlPrefixes[$switchUriParamMediaSiteVersion]))
					$this->switchUriParamMediaSiteVersion = $switchUriParamMediaSiteVersion;
			}
		//}
		
		// look into session object if there are or not any record about recognized device from previous request:
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		if (isset($this->session->{$mediaVersionUrlParam})) {
			$sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
			if (isset($this->allowedSiteKeysAndUrlPrefixes[$sessionMediaSiteVersion]))
				$this->sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
		}
		
		// set up current media site version from url string
		$this->setUpRequestMediaVersionFromUrl();

		return TRUE;
	}

	/**
	 * Try to set up into `\MvcCore\Request` object new 
	 * media site version detected from requested url by url query string param
	 * or by url path prefix. If there is catched any valid media site 
	 * version - set this value into request object. If there is nothing catched,
	 * set into request object full media site version anyway.
	 * @return void
	 */
	protected function setUpRequestMediaVersionFromUrl () {
		$requestMediaVersion = $this->request->GetParam(static::MEDIA_VERSION_URL_PARAM, 'a-zA-Z');
		$requestMediaVersionValidStr = $requestMediaVersion && strlen($requestMediaVersion) > 0;
		if ($requestMediaVersionValidStr) 
			$requestMediaVersion = strtolower($requestMediaVersion);
		if ($requestMediaVersionValidStr && isset($this->allowedSiteKeysAndUrlPrefixes[$requestMediaVersion])) 
			$this->requestMediaSiteVersion = $requestMediaVersion;
		if ($this->requestMediaSiteVersion === NULL && $this->anyRoutesConfigured) {
			$requestPath = $this->request->GetPath(TRUE);
			foreach ($this->allowedSiteKeysAndUrlPrefixes as $mediaSiteVersion => $requestPathPrefix) {
				if (mb_strpos($requestPath, $requestPathPrefix . '/') === 0 || $requestPath === $requestPathPrefix) {
					$this->requestMediaSiteVersion = $mediaSiteVersion;
					$this->request->SetPath(mb_substr($requestPath, mb_strlen($requestPathPrefix)));
					break;
				}
			}
		}
		if ($this->requestMediaSiteVersion === NULL) 
			$this->requestMediaSiteVersion = static::MEDIA_VERSION_FULL;
	}

	/**
	 * Store new media site version from url in session namespace, remove 
	 * media site version switching param from request object global collection 
	 * `$_GET` and redirect to the same page without switching param.
	 * @return bool
	 */
	protected function manageMediaSwitchingAndRedirect () {
		// unset site key switch param
		unset($this->requestGlobalGet[static::SWITCH_MEDIA_VERSION_URL_PARAM]);
		// redirect to no switch param uri version
		return $this->redirectToTargetMediaSiteVersion(
			$this->setUpMediaSiteVersionToContextAndSession($this->switchUriParamMediaSiteVersion)
		);
	}

	/**
	 * Detect media site version by sended user agent string by 
	 * third party `\Mobile_Detect` library and store detected result 
	 * in session namespace for next requests.
	 * @return void
	 */
	protected function manageMediaDetectionAndStoreInSession() {
		$detect = new \Mobile_Detect();
		if (
			array_key_exists(Routers\IMedia::MEDIA_VERSION_MOBILE, $this->allowedSiteKeysAndUrlPrefixes) && 
			$detect->isMobile()
		) {
			$this->mediaSiteVersion = Routers\IMedia::MEDIA_VERSION_MOBILE;
		} else if (
			array_key_exists(Routers\IMedia::MEDIA_VERSION_TABLET, $this->allowedSiteKeysAndUrlPrefixes) && 
			$detect->isTablet()
		) {
			$this->mediaSiteVersion = Routers\IMedia::MEDIA_VERSION_TABLET;
		} else {
			$this->mediaSiteVersion = Routers\IMedia::MEDIA_VERSION_FULL;
		}
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		$this->session->{$mediaVersionUrlParam} = $this->mediaSiteVersion;
	}

	/**
	 * If local media site version is the same as requested - do not process 
	 * any redirections. If local media site version is different version than 
	 * version in requested url (local media site version is completed previously 
	 * from session or by `\Mobile_Detect` library), then  - if strict mode by 
	 * session is configured as `TRUE` - redirect to local media site version. 
	 * If it's configured as `FALSE`, redirect to requested media site version.
	 * @return bool
	 */
	protected function checkMediaVersionWithUrlAndRedirectIfNecessary() {
		// if requested media site version is not the same as version in session 
		// fix it by `$this->stricModeBySession` configuration:
		if (
			$this->stricModeBySession && 
			(($this->isGet && $this->routeGetRequestsOnly) || !$this->routeGetRequestsOnly)
		) {
			// redirect back to `$this->mediaSiteVersion` by session
			$targetMediaSiteVersion = $this->mediaSiteVersion;
		} else {
			// redirect to requested version by `$this->requestMediaSiteVersion`:
			$targetMediaSiteVersion = $this->requestMediaSiteVersion;
			$this->mediaSiteVersion = $targetMediaSiteVersion;
		}
		if ($targetMediaSiteVersion === $this->requestMediaSiteVersion) return TRUE;
		// store target media site version in locale context and session and redirect
		return $this->redirectToTargetMediaSiteVersion(
			$this->setUpMediaSiteVersionToContextAndSession($targetMediaSiteVersion)
		);
	}

	/**
	 * Set up media site version string into current context and into session and return it.
	 * @param string $targetMediaSiteVersion 
	 * @return string
	 */
	protected function setUpMediaSiteVersionToContextAndSession ($targetMediaSiteVersion) {
		$this->session->{static::MEDIA_VERSION_URL_PARAM} = $targetMediaSiteVersion;
		$this->mediaSiteVersion = $targetMediaSiteVersion;
		return $targetMediaSiteVersion;
	}
}