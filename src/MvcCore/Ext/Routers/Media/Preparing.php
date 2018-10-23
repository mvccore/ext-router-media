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

trait Preparing
{
	/**
	 * Prepare media site version processing before request is routed by `\MvcCore\Router`:
	 * - prepare media site version from requested url
	 * - prepare media site version from session initialized by any previous request
	 * - detect if there is any special media site switching parameter in 
	 *   request object global array `$_GET` with name by 
	 *   `static::URL_PARAM_SWITCH_MEDIA_VERSION`
	 * @return void
	 */
	protected function prepareMedia () {
		//if ($this->mediaSiteVersion) return;

		//if ($this->stricModeBySession) { // check it with any strict session configuration to have more flexible navigations
			$sessStrictModeSwitchUrlParam = static::URL_PARAM_SWITCH_MEDIA_VERSION;
			if (isset($this->requestGlobalGet[$sessStrictModeSwitchUrlParam])) {
				$switchUriParamMediaSiteVersion = strtolower($this->requestGlobalGet[$sessStrictModeSwitchUrlParam]);
				if (isset($this->allowedSiteKeysAndUrlValues[$switchUriParamMediaSiteVersion]))
					$this->switchUriParamMediaSiteVersion = $switchUriParamMediaSiteVersion;
			}
		//}
		
		// look into session object if there are or not any record about recognized device from previous request:
		$mediaVersionUrlParam = static::URL_PARAM_MEDIA_VERSION;
		if (isset($this->session->{$mediaVersionUrlParam})) {
			$sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
			if (isset($this->allowedSiteKeysAndUrlValues[$sessionMediaSiteVersion]))
				$this->sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
		}
		
		// set up current media site version from url string
		$this->prepareRequestMediaVersionFromUrl();
	}

	/**
	 * Try to set up into `\MvcCore\Request` object new 
	 * media site version detected from requested url by url query string param
	 * or by url path prefix. If there is catched any valid media site 
	 * version - set this value into request object. If there is nothing catched,
	 * set into request object full media site version anyway.
	 * @return void
	 */
	protected function prepareRequestMediaVersionFromUrl () {
		$this->prepareRequestMediaVersionFromUrlQueryString();
		if ($this->requestMediaSiteVersion === NULL && $this->anyRoutesConfigured) 
			$this->prepareRequestMediaVersionFromUrlPath();
		if ($this->requestMediaSiteVersion === NULL) 
			$this->requestMediaSiteVersion = static::MEDIA_VERSION_FULL;
	}

	/**
	 * Try to set up media site version from request query string.
	 * @return void
	 */
	protected function prepareRequestMediaVersionFromUrlQueryString () {
		$requestMediaVersion = $this->request->GetParam(static::URL_PARAM_MEDIA_VERSION, 'a-zA-Z');
		$this->prepareSetUpRequestMediaSiteVersionIfValid($requestMediaVersion);
	}
	
	/**
	 * Try to set up media site version from request path.
	 * If there is any request path detected, remove media site version from 
	 * request path and store detected media site version in local context.
	 * @return void
	 */
	protected function prepareRequestMediaVersionFromUrlPath () {
		$requestPath = $this->request->GetPath(TRUE);
		foreach ($this->allowedSiteKeysAndUrlValues as $mediaSiteVersion => $mediaSiteUrlValue) {
			$mediaSiteUrlPrefix = $mediaSiteUrlValue === '' ? '' : '/' . mb_strtolower($mediaSiteUrlValue);
			$requestPathPart = mb_strtolower(mb_substr($requestPath, 0, mb_strlen($mediaSiteUrlPrefix)));
			if ($requestPathPart === $mediaSiteUrlPrefix) {
				//$this->prepareSetUpRequestMediaSiteVersionIfValid($mediaSiteVersion);
				$this->requestMediaSiteVersion = $mediaSiteVersion;
				$this->request->SetPath(
					mb_substr($requestPath, mb_strlen($mediaSiteUrlPrefix))
				);
				break;
			}
		}
	}

	/**
	 * @param string|NULL $rawRequestMediaVersion 
	 * @return bool
	 */
	protected function prepareSetUpRequestMediaSiteVersionIfValid ($rawRequestMediaVersion) {
		$result = FALSE;
		$rawRequestMediaVersionLength = $rawRequestMediaVersion ? strlen($rawRequestMediaVersion) : 0;
		$requestMediaVersionValidStr = $rawRequestMediaVersionLength > 0;
		$requestMediaVersionFormated = '';
		if ($requestMediaVersionValidStr) {
			$requestMediaVersionFormated = mb_strtolower($rawRequestMediaVersion);
			if (isset($this->allowedSiteKeysAndUrlValues[$requestMediaVersionFormated])) { 
				$this->requestMediaSiteVersion = $requestMediaVersionFormated;
				$result = TRUE;
			} else {
				$allowedSiteKey = array_search($requestMediaVersionFormated, array_values($this->allowedSiteKeysAndUrlValues), TRUE);
				if ($allowedSiteKey !== FALSE) {
					$this->requestMediaSiteVersion = $allowedSiteKey;
					$result = TRUE;
				}
			}
		}
		return $result;
	}
}
