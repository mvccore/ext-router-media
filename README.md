# MvcCore Extension - Router - Media

[![Latest Stable Version](https://img.shields.io/badge/Stable-v4.3.1-brightgreen.svg?style=plastic)](https://github.com/mvccore/ext-router-media/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

MvcCore Router extension to manage your website media version for different 
templates/css/js files rendering.

## Installation
```shell
composer require mvccore/ext-router-media
```

## Features
- Recognizes user device by user agent with `\Mobile_Detect` library into full/tablet/mobile.
- Stores recognized device version in it's own session namespace with configurable expiration.
- Completes `$request->GetMediaSiteVersion()` to use it in your app.
- Removes possibly founded media prefix substring from `$request->GetPath()` to 
  process routing as usuall without any special url variants to annoying you in your projects and routing.
- Completes every get application url with media prefix substring (possible to configure).
- Strict mode media site version configuration option.

## Usage
Add this to `Bootstrap.php` or to **very application beginning**, 
before application routing or any other extension configuration
using router for any purposes:
```php
\MvcCore\Application::GetInstance()->SetRouterClass(\MvcCore\Ext\Routers\Media::class);
```

## Configuration

### Session expiration
There is possible to change session expiration about detected media
site version value to not recognize media site version every request
where is no prefix in url, because all regular expressions in `Mobile_Detect`
library could takes some time. By **default** there is **1 hour**. 
You can change it by:
```php
\MvcCore\Ext\Routers\Media::GetInstance()->SetSessionExpirationSeconds(86400); // day
```

### Media url prefixes and allowed media versions
To allow only some media site versions and configure url prefixes, you can use:
```php
// to allow only mobile version (with url prefix '/mobile') 
// and full version (with no url prefix):
\MvcCore\Ext\Routers\Media::GetInstance()->SetAllowedSiteKeysAndUrlPrefixes(array(
	\MvcCore\Ext\Routers\Media::MEDIA_VERSION_MOBILE	=> '/mobile',
	\MvcCore\Ext\Routers\Media::MEDIA_VERSION_FULL		=> '',			// empty string as last item!
));
```

### Strict session mode
To change managing user media version into more strict mode,
where is not possible to change media version only by request 
application with different media prefix in path like:
```
/mobile/any/application/request/path
```
but ony where is possible to change media site version by 
special `$_GET` param "switch_media_version" like:
```
/mobile/any/application/request/path?switch_media_version=mobile
```
you need to configure router into strict session mode by:
```php
\MvcCore\Ext\Routers\Media::GetInstance()->SetStricModeBySession(TRUE);
```
