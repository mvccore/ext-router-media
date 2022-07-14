<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Routers\Media;

use MvcCore\Ext\Routers;
use \Mobile_Detect;

/**
 * @mixin \MvcCore\Ext\Routers\Media
 */
trait PreRouting {

	/**
	 * Process media site version before request is routed by `\MvcCore\Router`.
	 * - If there is special media site switching param in request object global
	 *   array `$_GET` and it's value is allowed as media site version:
	 *	 - Switch media site version in session by given switching param value 
	 *     and redirect to the same page with new media site version substring 
	 *     in url or with new media site version query string param.
	 * - If there is no media site version in session or if media site version 
	 *   from session is not allowed:
	 *   - Recognize media site version by `\Mobile_Detect` third party library 
	 *     and store recognized version in this context and in session namespace.
	 * - Else set up media site version from session into this context
	 * - Later - if detected media site version is not the same as requested 
	 *   media site version - redirect to detected version in this context.
	 * Return always `TRUE` and return `FALSE` if request is redirected.
	 * @return bool
	 */
	protected function preRouteMedia () {
		if (!$this->mediaSiteVersion) {
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
		}

		// set up stored/detected media site version into request:
		$this->request->SetMediaSiteVersion($this->mediaSiteVersion);
		$this->session->{static::URL_PARAM_MEDIA_VERSION} = $this->mediaSiteVersion;
		
		return TRUE;
	}

	/**
	 * Store new media site version from url in session namespace, remove 
	 * media site version switching param from request object global collection 
	 * `$_GET` and redirect to the same page without switching param.
	 * @return bool
	 */
	protected function manageMediaSwitchingAndRedirect () {
		// unset site key switch param
		$switchMediaVersionParamName = static::URL_PARAM_SWITCH_MEDIA_VERSION;
		// it couldn't be there in module extended router, because this variable is 
		// used by extended router to redirect non-valid values in 3rd level domains
		if (isset($this->requestGlobalGet[$switchMediaVersionParamName]))
			unset($this->requestGlobalGet[$switchMediaVersionParamName]);
		// redirect to no switch param URL version
		return $this->redirectToVersion(
			$this->setUpMediaSiteVersionToContextAndSession($this->switchUriParamMediaSiteVersion)
		);
	}

	/**
	 * Detect media site version by sent user agent string by 
	 * third party `\Mobile_Detect` library and store detected result 
	 * in session namespace for next requests.
	 * @return void
	 */
	protected function manageMediaDetectionAndStoreInSession () {
		$detect = new \Mobile_Detect();
		if (
			array_key_exists(Routers\IMedia::MEDIA_VERSION_MOBILE, $this->allowedMediaVersionsAndUrlValues) && 
			$detect->isMobile()
		) {
			$this->mediaSiteVersion = Routers\IMedia::MEDIA_VERSION_MOBILE;
		} else if (
			array_key_exists(Routers\IMedia::MEDIA_VERSION_TABLET, $this->allowedMediaVersionsAndUrlValues) && 
			$detect->isTablet()
		) {
			$this->mediaSiteVersion = Routers\IMedia::MEDIA_VERSION_TABLET;
		} else {
			$this->mediaSiteVersion = Routers\IMedia::MEDIA_VERSION_FULL;
		}
		$mediaVersionUrlParam = static::URL_PARAM_MEDIA_VERSION;
		$this->session->{$mediaVersionUrlParam} = $this->mediaSiteVersion;
		$this->sessionMediaSiteVersion = $this->mediaSiteVersion;
		$this->firstRequestMediaDetection = $this->mediaSiteVersion === $this->requestMediaSiteVersion;
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
			!$this->adminRequest &&
			(($this->isGet && $this->routeGetRequestsOnly) || !$this->routeGetRequestsOnly)
		) {
			// redirect back to `$this->mediaSiteVersion` by session
			$targetMediaSiteVersion = $this->mediaSiteVersion;
		} else if ($this->firstRequestMediaDetection === FALSE) {
			// redirect only if detected version is different than requested version
			$targetMediaSiteVersion = $this->mediaSiteVersion;
		} else {
			if (
				$this->routeGetRequestsOnly && 
				!$this->isGet &&
				$this->requestMediaSiteVersion !== $this->sessionMediaSiteVersion
			) {
				// redirect to session version by `$this->requestMediaSiteVersion`:
				$targetMediaSiteVersion = $this->sessionMediaSiteVersion;
				$this->mediaSiteVersion = $targetMediaSiteVersion;
				$this->requestMediaSiteVersion = $targetMediaSiteVersion;
			} else {
				// redirect to requested version by `$this->requestMediaSiteVersion`:
				$targetMediaSiteVersion = $this->requestMediaSiteVersion;
				$this->mediaSiteVersion = $targetMediaSiteVersion;
			}
		}
		if ($targetMediaSiteVersion === $this->requestMediaSiteVersion) return TRUE;
		// store target media site version in locale context and session and redirect
		return $this->redirectToVersion(
			$this->setUpMediaSiteVersionToContextAndSession($targetMediaSiteVersion)
		);
	}

	/**
	 * Set up media site version string into current context and into session and return it.
	 * @param string $targetMediaSiteVersion 
	 * @return array
	 */
	protected function setUpMediaSiteVersionToContextAndSession ($targetMediaSiteVersion) {
		$this->session->{static::URL_PARAM_MEDIA_VERSION} = $targetMediaSiteVersion;
		$this->mediaSiteVersion = $targetMediaSiteVersion;
		return [\MvcCore\Ext\Routers\IMedia::URL_PARAM_MEDIA_VERSION => $targetMediaSiteVersion];
	}
}
