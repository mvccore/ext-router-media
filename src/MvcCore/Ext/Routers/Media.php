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

namespace MvcCore\Ext\Routers;

/**
 * Responsibility - recognize media site version from url or user agent or session and set 
 *					up request object, complete automaticly rewrited url with remembered 
 *					media site version. Redirect to proper media site version by configuration.
 */
class		Media 
extends		\MvcCore\Router
implements	\MvcCore\Ext\Routers\IMedia,
			\MvcCore\Ext\Routers\IExtended
{
	use \MvcCore\Ext\Routers\Extended;
	use \MvcCore\Ext\Routers\Media\PropsGettersSetters;
	use \MvcCore\Ext\Routers\Media\Routing;
	use \MvcCore\Ext\Routers\Media\UrlCompletion;
	
	public function & Route () {
		$result = FALSE;
		if ($this->preRouteMedia() === FALSE) return $result;
		$result = parent::Route();
		return $result;
	}
}
