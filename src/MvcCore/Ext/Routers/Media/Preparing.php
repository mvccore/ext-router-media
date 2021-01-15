<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Ext\Routers\Media;

trait Preparing {

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
		/** @var $this \MvcCore\Ext\Routers\Media */
		// if there is only one allowed version, do not process anything else
		if (count($this->allowedMediaVersionsAndUrlValues) < 2) {
			$this->mediaSiteVersion = static::MEDIA_VERSION_FULL;
			$this->requestMediaSiteVersion = $this->mediaSiteVersion;
			return;
		}

		//if ($this->stricModeBySession) { // check it with any strict session configuration to have more flexible navigations
			$sessStrictModeSwitchUrlParam = static::URL_PARAM_SWITCH_MEDIA_VERSION;
			if (isset($this->requestGlobalGet[$sessStrictModeSwitchUrlParam])) {
				$switchUriParamMediaSiteVersion = strtolower($this->requestGlobalGet[$sessStrictModeSwitchUrlParam]);
				if (isset($this->allowedMediaVersionsAndUrlValues[$switchUriParamMediaSiteVersion]))
					$this->switchUriParamMediaSiteVersion = $switchUriParamMediaSiteVersion;
			}
		//}
		
		// look into session object if there are or not any record about recognized device from previous request:
		$mediaVersionUrlParam = static::URL_PARAM_MEDIA_VERSION;
		if (isset($this->session->{$mediaVersionUrlParam})) {
			$sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
			if (isset($this->allowedMediaVersionsAndUrlValues[$sessionMediaSiteVersion]))
				$this->sessionMediaSiteVersion = $this->session->{$mediaVersionUrlParam};
		}
		
		// set up current media site version from url string
		$this->prepareRequestMediaVersionFromUrl();
	}

	/**
	 * Try to set up into `\MvcCore\Request` object new 
	 * media site version detected from requested url by url query string param
	 * or by url path prefix. If there is caught any valid media site 
	 * version - set this value into request object. If there is nothing caught,
	 * set into request object full media site version anyway.
	 * @return void
	 */
	protected function prepareRequestMediaVersionFromUrl () {
		/** @var $this \MvcCore\Ext\Routers\Media */
		$this->prepareRequestMediaVersionFromUrlQueryString();
		if ($this->requestMediaSiteVersion === NULL && $this->anyRoutesConfigured) 
			$this->prepareRequestMediaVersionFromUrlPath();
		if ($this->requestMediaSiteVersion === NULL) 
			$this->requestMediaSiteVersion = static::MEDIA_VERSION_FULL;
	}

	/**
	 * Try to set up media site version from request query string as request 
	 * media site version.
	 * @return void
	 */
	protected function prepareRequestMediaVersionFromUrlQueryString () {
		/** @var $this \MvcCore\Ext\Routers\Media */
		$requestMediaVersion = $this->request->GetParam(static::URL_PARAM_MEDIA_VERSION, 'a-zA-Z');
		$this->prepareSetUpRequestMediaSiteVersionIfValid($requestMediaVersion);
	}
	
	/**
	 * Try to set up media site version from request path as request media site 
	 * version. If there is any request path detected, remove media site version 
	 * from request path and store detected media site version in local context.
	 * @return void
	 */
	protected function prepareRequestMediaVersionFromUrlPath () {
		/** @var $this \MvcCore\Ext\Routers\Media */
		$requestPath = $this->request->GetPath(TRUE);
		$requestPathExploded = explode('/', trim($requestPath, '/'));
		$requestPathFirstPart = mb_strtolower($requestPathExploded[0]);
		foreach ($this->allowedMediaVersionsAndUrlValues as $mediaSiteVersion => $mediaSiteUrlValue) {
			$mediaSiteUrlPrefix = mb_strtolower($mediaSiteUrlValue);
			if ($requestPathFirstPart === $mediaSiteUrlPrefix) {
				//$this->prepareSetUpRequestMediaSiteVersionIfValid($mediaSiteVersion);
				$this->requestMediaSiteVersion = $mediaSiteVersion;
				if (mb_strlen($mediaSiteUrlPrefix) > 0) 
					$this->request->SetPath(
						mb_substr($requestPath, mb_strlen('/' . $mediaSiteUrlPrefix))	
					);
				break;
			}
		}
	}

	/**
	 * Try to set up given media site version as request media site version if 
	 * it is valid. Convert it to lower case and check if exists as value or key 
	 * in protected property `$this->allowedMediaVersionsAndUrlValues;`.
	 * @param string|NULL $rawRequestMediaVersion 
	 * @return bool
	 */
	protected function prepareSetUpRequestMediaSiteVersionIfValid ($rawRequestMediaVersion) {
		/** @var $this \MvcCore\Ext\Routers\Media */
		$result = FALSE;
		$rawRequestMediaVersionLength = $rawRequestMediaVersion ? strlen($rawRequestMediaVersion) : 0;
		$requestMediaVersionValidStr = $rawRequestMediaVersionLength > 0;
		$requestMediaVersionFormated = '';
		if ($requestMediaVersionValidStr) {
			$requestMediaVersionFormated = mb_strtolower($rawRequestMediaVersion);
			if (isset($this->allowedMediaVersionsAndUrlValues[$requestMediaVersionFormated])) { 
				$this->requestMediaSiteVersion = $requestMediaVersionFormated;
				$result = TRUE;
			} else {
				$allowedSiteKey = array_search($requestMediaVersionFormated, array_values($this->allowedMediaVersionsAndUrlValues), TRUE);
				if ($allowedSiteKey !== FALSE) {
					$this->requestMediaSiteVersion = $allowedSiteKey;
					$result = TRUE;
				}
			}
		}
		return $result;
	}
}
