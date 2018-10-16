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

trait Redirecting
{
	/**
	 * Redirect to target media site version with path and by cloned request 
	 * object global `$_GET` collection. Return always `FALSE`.
	 * @param string $targetMediaSiteVersion 
	 * @return bool
	 */
	protected function redirectToTargetMediaSiteVersion ($targetMediaSiteVersion) {
		// unset site key switch param and redirect to no switch param uri version
		$request = & $this->request;
		$mediaVersionUrlParam = static::URL_PARAM_MEDIA_VERSION;
		$targetMediaSameAsDefault = $targetMediaSiteVersion === static::MEDIA_VERSION_FULL;

		if (isset($this->requestGlobalGet[$mediaVersionUrlParam])) {
			if ($targetMediaSameAsDefault) {
				if (isset($this->requestGlobalGet[$mediaVersionUrlParam]))
					unset($this->requestGlobalGet[$mediaVersionUrlParam]);
			} else {
				$this->requestGlobalGet[$mediaVersionUrlParam] = $targetMediaSiteVersion;
			}
			$targetMediaPrefix = '';
		} else {
			$targetMediaPrefix = $this->allowedSiteKeysAndUrlPrefixes[$targetMediaSiteVersion];
		}

		if ($this->anyRoutesConfigured) {
			$targetUrl = $request->GetBaseUrl()
				. $targetMediaPrefix
				. $request->GetPath(TRUE);
		} else {
			$targetUrl = $request->GetBaseUrl();
			$this->removeDefaultCtrlActionFromGlobalGet();
			if ($this->requestGlobalGet)
				$targetUrl .= $request->GetScriptName();
		}

		if ($this->requestGlobalGet) {
			$amp = $this->getQueryStringParamsSepatator();
			$targetUrl .= '?' . str_replace('%2F', '/', http_build_query($this->requestGlobalGet, '', $amp, PHP_QUERY_RFC3986));
		}
		
		if ($this->request->GetFullUrl() === $targetUrl) return TRUE;

		$this->redirect($targetUrl, \MvcCore\IResponse::SEE_OTHER);
		return FALSE;
	}
}
