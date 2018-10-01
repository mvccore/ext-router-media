# MvcCore Extension - Router - Media

[![Latest Stable Version](https://img.shields.io/badge/Stable-v4.3.1-brightgreen.svg?style=plastic)](https://github.com/mvccore/ext-router-media/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

MvcCore Router extension to manage your website media version in url
to have media flag in request, controller and view to render different
templates, css and js files for mobiles, tables or desktops.

## Installation
```shell
composer require mvccore/ext-router-media
```

## Features
- Recognizes user device by http header `User-Agent` with [`\Mobile_Detect`](https://github.com/serbanghita/Mobile-Detect) library into versions `full`, `tablet` or `mobile`.
- Stores recognized device version in it's own session namespace with configurable expiration to not process `\Mobile_Detect` recognition in every request.
- Completes `$request->GetMediaSiteVersion()` value to use it enywhere in your app.
- Removes possibly founded media prefix substring from `$request->GetPath()` to 
  process routing as usual (without any special url variants with or without media prefixes 
  to annoying you in your projects and routing).
- Completes every application url (or every get url by configuration) with media prefix substring (also possible to configure the url usbstring).
- Strict mode media site version configuration option to drive application media version strictly by session value.

## Usage
Add this to `/App/Bootstrap.php` or to **very application beginning**, 
before application routing or any other extension configuration
using router for any purposes:
```php
\MvcCore\Application::GetInstance()->SetRouterClass('\MvcCore\Ext\Routers\Media');
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
