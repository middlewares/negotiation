<?php

namespace Middlewares\tests;

use Middlewares\ContentLanguage;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use mindplay\middleman\Dispatcher;

class ContentLanguageTest extends \PHPUnit_Framework_TestCase
{
    public function languagesProvider()
    {
        return [
            [
                ['gl'],
                'gl-es, es;q=0.8, en;q=0.7',
                'gl',
            ],
            [
                ['es', 'en'],
                'gl-es, es;q=0.8, en;q=0.7',
                'es',
            ],
            [
                ['en', 'es'],
                'gl-es, es;q=0.8, en;q=0.7',
                'es',
            ],
            [
                [],
                '',
                null,
            ],
            [
                ['es', 'en'],
                '',
                'es',
            ],
            [
                ['en', 'es'],
                '',
                'en',
            ],
        ];
    }

    /**
     * @dataProvider languagesProvider
     */
    public function testLanguages(array $languages, $accept, $language)
    {
        $request = (new ServerRequest())->withHeader('Accept-Language', $accept);

        $response = (new Dispatcher([
            new ContentLanguage($languages),

            function ($request) {
                $response = new Response();
                $response->getBody()->write($request->getHeaderLine('Accept-Language'));

                return $response;
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($language, (string) $response->getBody());
    }

    public function languagesPathProvider()
    {
        return [
            [
                ['gl'],
                '',
                'gl-es, es;q=0.8, en;q=0.7',
                '/gl/',
            ],
            [
                ['gl'],
                '',
                'gl-es, es;q=0.8, en;q=0.7',
                '/gl/',
            ],
            [
                ['gl', 'es'],
                '/ES',
                'gl-es, es;q=0.8, en;q=0.7',
                '',
                'es',
            ],
            [
                ['gl', 'es'],
                'es',
                'gl-es, es;q=0.8, en;q=0.7',
                '',
                'es',
            ],
            [
                ['gl', 'es'],
                '/es/ola',
                'gl-es, es;q=0.8, en;q=0.7',
                '',
                'es',
            ],
            [
                ['gl', 'es'],
                '/mola/ola',
                'gl-es, es;q=0.8, en;q=0.7',
                '/es/mola/ola',
            ],
        ];
    }

    /**
     * @dataProvider languagesPathProvider
     */
    public function testLanguagesPath(array $languages, $uri, $accept, $location, $language = null)
    {
        $request = (new ServerRequest([], [], $uri))->withHeader('Accept-Language', $accept);

        $response = (new Dispatcher([
            (new ContentLanguage($languages))->usePath()->redirect(),

            function ($request) {
                $response = new Response();
                $response->getBody()->write($request->getHeaderLine('Accept-Language'));

                return $response;
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($language, (string) $response->getBody());

        if ($language === null) {
            $this->assertEquals(302, $response->getStatusCode());
            $this->assertEquals($location, $response->getHeaderLine('Location'));
        } else {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEmpty($response->getHeaderLine('Location'));
        }
    }
}
