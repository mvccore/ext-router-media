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
		$requestMediaVersion = $this->request->GetParam(static::URL_PARAM_MEDIA_VERSION, 'a-zA-Z');
		$requestMediaVersionValidStr = $requestMediaVersion && strlen($requestMediaVersion) > 0;
		if ($requestMediaVersionValidStr) 
			$requestMediaVersion = strtolower($requestMediaVersion);
		if ($requestMediaVersionValidStr && isset($this->allowedSiteKeysAndUrlValues[$requestMediaVersion])) 
			$this->requestMediaSiteVersion = $requestMediaVersion;
		if ($this->requestMediaSiteVersion === NULL && $this->anyRoutesConfigured) {
			$requestPath = $this->request->GetPath(TRUE);
			foreach ($this->allowedSiteKeysAndUrlValues as $mediaSiteVersion => $mediaSiteUrlValue) {
				$mediaSiteUrlPrefix = $mediaSiteUrlValue === '' ? '' : '/' . $mediaSiteUrlValue ;
				$requestPathPart = mb_substr($requestPath, 0, mb_strlen($mediaSiteUrlPrefix));
				if ($requestPathPart === $mediaSiteUrlPrefix) {
					$this->requestMediaSiteVersion = $mediaSiteVersion;
					$this->request->SetPath(mb_substr($requestPath, mb_strlen($mediaSiteUrlPrefix)));
					break;
				}
			}
		}
		if ($this->requestMediaSiteVersion === NULL) 
			$this->requestMediaSiteVersion = static::MEDIA_VERSION_FULL;
	}
}
