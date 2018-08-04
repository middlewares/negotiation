# middlewares/negotiation

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware using [wildurand/Negotiation](https://github.com/willdurand/Negotiation) to implement content negotiation. Contains the following components:

* [ContentType](#contenttype)
* [ContentLanguage](#contentlanguage)
* [ContentEncoding](#contentencoding)

## Requirements

* PHP >= 7.0
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/negotiation](https://packagist.org/packages/middlewares/negotiation).

```sh
composer require middlewares/negotiation
```

## Example

```php
$dispatcher = new Dispatcher([
    new Middlewares\ContentType(),
    new Middlewares\ContentLanguage(['en', 'gl', 'es']),
    new Middlewares\ContentEncoding(['gzip', 'deflate']),
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## ContentType

To detect the preferred mime type using the `Accept` header and the path extension and edit the header with this value. A `Content-Type` header is also added to the response if it's missing.

#### `__construct(array $formats = null)`

Set the available formats to negotiate sorted by priority. By default uses [these](src/formats_defaults.php)

#### `useDefault($useDefault = true)`

Whether use the default format (the first format provided) if the negotiation does not return a valid format. Set to `false` to disable the default format and return a `406` response. By default is `true`.

#### `charsets(array $charsets)`

Array with the available charsets, to negotiate with the `Accept-Charset` header. By default is `['UTF-8']`.

#### `noSniff($nosniff = true)`

Adds the `X-Content-Type-Options: nosniff` header, to mitigating [MIME confusión attacks.](https://blog.mozilla.org/security/2016/08/26/mitigating-mime-confusion-attacks-in-firefox/). `true` by default. To disable it: `noSniff(false)`.

#### `responseFactory(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`

A PSR-17 factory to create `406` responses.

```php
$request = (new ServerRequest())
    ->withHeader('Accept', 'application/xml;charset=UTF-8,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8');

$dispatcher = new Dispatcher([
    new Middlewares\ContentType(),

    function ($request, $next) {
        $type = $request->getHeaderLine('Accept');
        $response = new Response();

        if ($type === 'text/html') {
            $response->getBody()->write('<p>Hello world</p>');
        } elseif ($type === 'application/json') {
            $response->getBody()->write(json_encode(['message' => 'Hello world']));
        }

        return $response;
    }
]);

$response = $dispatcher->dispatch($request);

echo $response->getHeaderLine('Content-Type'); //text/html; charset=UTF-8
echo $response->getBody(); //<p>Hello world</p>
```

## ContentLanguage

To detect the preferred language using the `Accept-Language` header or the path prefix and edit the header with this value. A `Content-Language` header is also added to the response if it's missing.

#### `__construct(array $languages)`

Set the available languages to negotiate sorted by priority. The first value will be used as default if no other languages is choosen in the negotiation.

```php
$request = (new ServerRequest())
    ->withHeader('Accept-Language', 'gl-es, es;q=0.8, en;q=0.7');

$dispatcher = new Dispatcher([
    new Middlewares\ContentLanguage(['es', 'en']),

    function ($request, $next) {
        $language = $request->getHeaderLine('Accept-Language');
        $response = new Response();

        if ($language === 'es') {
            $response->getBody()->write('Hola mundo');
        } else {
            $response->getBody()->write('Hello world');
        }

        return $response;
    }
]);

$response = $dispatcher->dispatch($request);

echo $response->getHeaderLine('Content-Language'); //es
echo $response->getBody(); //Hola mundo
```

#### `usePath()`

To use the base path to detect the language. This is useful if you have different paths for each language, for example `/gl/foo` and `/en/foo`. 

Note: the language in the path has preference over the `Accept-Language` header.

```php
$request = (new ServerRequest())->withUri(new Uri('/en/hello-world'));

$dispatcher = new Dispatcher([
    (new Middlewares\ContentLanguage(['es', 'en']))
        ->usePath(),

    function ($request, $next) {
        $language = $request->getHeaderLine('Accept-Language');
        $response = new Response();

        if ($language === 'es') {
            $response->getBody()->write('Hola mundo');
        } else {
            $response->getBody()->write('Hello world');
        }

        return $response;
    }
]);

$response = $dispatcher->dispatch($request);

echo $response->getHeaderLine('Content-Language'); //en
echo $response->getBody(); //Hello world
```

#### `redirect()`

Used to return a `302` response redirecting to a path containing the language. This only works if `usePath` is enabled, so for example, if the request uri is `/welcome`, returns a redirection to `/en/welcome`.

#### `responseFactory(Psr\Http\Message\ResponseFactoryInterface $responseFactory)`

A PSR-17 factory to create redirect responses.

## ContentEncoding

To detect the preferred encoding type using the `Accept-Encoding` header and edit the header with this value.

#### `__construct(array $encodings)`

Set the available encodings to negotiate.

```php
$request = (new ServerRequest())
    ->withHeader('Accept-Encoding', 'gzip,deflate');

$dispatcher = new Dispatcher([
    new Middlewares\ContentEncoding(['gzip']),

    function ($request, $next) {
        echo $request->getHeaderLine('Accept-Encoding'); //gzip
    }
]);

$response = $dispatcher->dispatch($request);
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/negotiation.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/negotiation/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/negotiation.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/negotiation.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/c737d5a3-6458-4030-b2d8-94adf47ab507.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/negotiation
[link-travis]: https://travis-ci.org/middlewares/negotiation
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/negotiation
[link-downloads]: https://packagist.org/packages/middlewares/negotiation
[link-sensiolabs]: https://insight.sensiolabs.com/projects/c737d5a3-6458-4030-b2d8-94adf47ab507
