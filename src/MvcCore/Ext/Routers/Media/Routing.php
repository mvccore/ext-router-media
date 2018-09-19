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

trait Routing
{
	/**
	 * Process media site version before request is routed by `\MvcCore\Router`.
	 * Prepare:
	 * - Media site version from requested url.
	 * - Media site version from session initialized by any previous request.
	 * - Detect if there is any special media site switching parameter in 
	 *   request object global array `$_GET` with name by 
	 *   `static::SWITCH_MEDIA_VERSION_URL_PARAM`.
	 * Than process for every request::
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
	 * @param \MvcCore\Request|\MvcCore\Interfaces\IRequest &$request
	 * @return bool
	 */
	protected function preRouteHandlerMedia (\MvcCore\Interfaces\IRequest & $request) {
		$result = TRUE;
		/** @var $request \MvcCore\Request */
		$this->request = & $request;
		$request->SetOriginalPath($request->GetPath());
		
		// switching media site version will be only by get:
		$this->isGet = $request->GetMethod() == \MvcCore\Interfaces\IRequest::METHOD_GET;
		
		// look into request params if are we just switching any new site media version
		$this->requestGlobalGet = array_merge([], $request->GetGlobalCollection('get')); // clone `$_GET`
		
		//if ($this->stricModeBySession) { // check it with any strict session configuration to have more flexible navigations
			$sessStrictModeSwitchUrlParam = static::SWITCH_MEDIA_VERSION_URL_PARAM;
			if (isset($this->requestGlobalGet[$sessStrictModeSwitchUrlParam])) {
				$this->mediaSiteVersionSwitchUriParam = strtolower($this->requestGlobalGet[$sessStrictModeSwitchUrlParam]);
			}
		//}
		
		// set up current media site version from url string
		$this->setUpRequestMediaVersionFromUrl();
		
		// Set up session object to look inside for something from previous requests. 
		// This command starts the session if not started yet.
		$this->setUpSession();
		
		// look into session object if there are or not any record about recognized device from previous request:
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		if (isset($this->session->{$mediaVersionUrlParam})) {
			$this->sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
		}
		
		if (
			$this->isGet &&
			$this->mediaSiteVersionSwitchUriParam !== NULL &&
			isset($this->allowedSiteKeysAndUrlPrefixes[$this->mediaSiteVersionSwitchUriParam])
		) {
			// if there is detected in requested url media site version switching param,
			// store switching param value in session, remove param from `$_GET` 
			// and redirect to the same page with new media site version:
			$result = $this->manageMediaSwitchingAndRedirect();

		} else if (
			$this->isGet && (
				$this->sessionMediaSiteVersion === NULL ||
				!isset($this->allowedSiteKeysAndUrlPrefixes[$this->sessionMediaSiteVersion])
			)
		) {
			// if there is no session record about media site version:
			$this->manageMediaDetectionAndStoreInSession();
			// check if media site version is the same as local media site version:
			$result = $this->checkMediaVersionWithRequestVersionAndRedirectIfDifferent();

		} else {
			// if there is media site version in session already:
			$this->mediaSiteVersion = $this->sessionMediaSiteVersion;
			// check if media site version is the same as local media site version:
			$result = $this->checkMediaVersionWithRequestVersionAndRedirectIfDifferent();
		}

		// set up stored/detected media site version into request:
		$request->SetMediaSiteVersion($this->mediaSiteVersion);
		
		// return `TRUE` or `FALSE` to break or not preroute handlers queue dispatching:
		return $result;
	}

	/**
	 * Store new media site version from url in session namespace, remove 
	 * media site version switching param from request object global collection 
	 * `$_GET` and redirect to the same page without switching param.
	 * @return bool
	 */
	protected function manageMediaSwitchingAndRedirect () {
		$mediaSiteVersion = $this->mediaSiteVersionSwitchUriParam;
		// store switched site key into session
		$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
		$this->session->{$mediaVersionUrlParam} = $mediaSiteVersion;
		$sessStrictModeSwitchUrlParam = static::SWITCH_MEDIA_VERSION_URL_PARAM;
		unset($this->requestGlobalGet[$sessStrictModeSwitchUrlParam]);
		// unset site key switch param and redirect to no switch param uri version
		$request = & $this->request;
		if ($this->anyRoutesConfigured) {
			$targetUrl = $request->GetBaseUrl()
				. $this->allowedSiteKeysAndUrlPrefixes[$mediaSiteVersion] 
				. $request->GetPath();
		} else {
			$targetUrl = $request->GetBaseUrl();
			if ($mediaSiteVersion === static::MEDIA_VERSION_FULL) {
				if (isset($this->requestGlobalGet[$mediaVersionUrlParam]))
					unset($this->requestGlobalGet[$mediaVersionUrlParam]);
			} else {
				$this->requestGlobalGet[$mediaVersionUrlParam] = $mediaSiteVersion;
			}
			$this->removeDefaultCtrlActionFromGlobalGet();
			if ($this->requestGlobalGet)
				$targetUrl .= $request->GetScriptName();
		}
		if ($this->requestGlobalGet) {
			$amp = $this->getQueryStringParamsSepatator();
			$targetUrl .= '?' . http_build_query($this->requestGlobalGet, '', $amp);
		}
		$this->redirect($targetUrl, \MvcCore\Interfaces\IResponse::SEE_OTHER);
		return FALSE;
	}

	/**
	 * Detect media site version by sended user agent string by 
	 * third party `\Mobile_Detect` library and store detected result 
	 * in session namespace for next requests.
	 * @return void
	 */
	protected function manageMediaDetectionAndStoreInSession() {
		$detect = new \Mobile_Detect();
		$mediaSiteVersions = array_keys($this->allowedSiteKeysAndUrlPrefixes);
		if ($detect->isMobile()) {
			$this->mediaSiteVersion = $mediaSiteVersions[0];
		} else if ($detect->isTablet()) {
			$this->mediaSiteVersion = $mediaSiteVersions[1];
		} else {
			$this->mediaSiteVersion = $mediaSiteVersions[2];
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
	protected function checkMediaVersionWithRequestVersionAndRedirectIfDifferent() {
		$request = & $this->request;
		$requestMediaSiteVersion = $request->GetMediaSiteVersion();
		$sessionOrDetectionSameWithRequest = $this->mediaSiteVersion === $requestMediaSiteVersion;
		if (!$sessionOrDetectionSameWithRequest) {
			// if requested media site version is not the same as version in session 
			// fix it by `$this->stricModeBySession` configuration:
			$mediaVersionUrlParam = static::MEDIA_VERSION_URL_PARAM;
			if ($this->stricModeBySession && $this->isGet) {
				// redirect back to `$this->mediaSiteVersion` by session
				$targetMediaSiteVersion = $this->mediaSiteVersion;
			} else {
				// redirect to requested version by `$requestMediaSiteVersion`:
				$targetMediaSiteVersion = $requestMediaSiteVersion;
			}
			// store the right media site version in session
			$this->session->{$mediaVersionUrlParam} = $targetMediaSiteVersion;
			$this->mediaSiteVersion = $targetMediaSiteVersion;
			// complete new url to redirect into
			if ($this->anyRoutesConfigured) {
				$targetUrl = $request->GetBaseUrl()
					. $this->allowedSiteKeysAndUrlPrefixes[$targetMediaSiteVersion] 
					. $request->GetPath()
					. $request->GetQuery(TRUE);
			} else {
				$targetUrl = $request->GetBaseUrl();
				if ($targetMediaSiteVersion === static::MEDIA_VERSION_FULL) {
					if (isset($this->requestGlobalGet[$mediaVersionUrlParam]))
						unset($this->requestGlobalGet[$mediaVersionUrlParam]);
				} else {
					$this->requestGlobalGet[$mediaVersionUrlParam] = $targetMediaSiteVersion;
				}
				$this->removeDefaultCtrlActionFromGlobalGet();
				if ($this->requestGlobalGet) {
					$targetUrl .= $request->GetScriptName();
					$amp = $this->getQueryStringParamsSepatator();
					$targetUrl .= '?' . http_build_query($this->requestGlobalGet, '', $amp);
				}
			}
			// redirect
			$this->redirect(
				$targetUrl, 
				\MvcCore\Interfaces\IResponse::SEE_OTHER
			);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Try to set up into `\MvcCore\Request` object new 
	 * media site version detected from requested url by url path prefix
	 * or by url query string param. If there is catched any valid media site 
	 * version - set this value into request object. If there is nothing catched,
	 * set into request object full media site version anyway.
	 * @return void
	 */
	protected function setUpRequestMediaVersionFromUrl () {
		if ($this->routes) {
			$requestPath = $this->request->GetPath(TRUE);
			$mediaSiteVersionCatchedInPath = FALSE;
			foreach ($this->allowedSiteKeysAndUrlPrefixes as $mediaSiteVersion => $requestPathPrefix) {
				if (mb_strpos($requestPath, $requestPathPrefix . '/') === 0) {
					$mediaSiteVersionCatchedInPath = TRUE;
					$this->request
						->SetMediaSiteVersion($mediaSiteVersion)
						->SetPath(mb_substr($requestPath, strlen($requestPathPrefix)));
					break;
				}
			}
			if (!$mediaSiteVersionCatchedInPath) 
				$this->request->SetMediaSiteVersion(static::MEDIA_VERSION_FULL);
		} else {
			$requestMediaVersion = $this->request->GetParam(self::MEDIA_VERSION_URL_PARAM, 'a-zA-Z');
			if ($requestMediaVersion) $requestMediaVersion = strtolower($requestMediaVersion);
			if (isset($this->allowedSiteKeysAndUrlPrefixes[$requestMediaVersion])) {
				$mediaSiteVersion = $requestMediaVersion;
			} else {
				$mediaSiteVersion = static::MEDIA_VERSION_FULL;
			}
			$this->request->SetMediaSiteVersion($mediaSiteVersion);
		}
	}
}
