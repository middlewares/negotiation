<?php

namespace Middlewares\tests;

use Middlewares\ContentLanguage;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ContentLanguageTest extends TestCase
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
     * @param mixed $accept
     * @param mixed $language
     */
    public function testLanguages(array $languages, $accept, $language)
    {
        $request = Factory::createServerRequest()->withHeader('Accept-Language', $accept);

        $response = Dispatcher::run([
            new ContentLanguage($languages),
            function ($request) {
                echo $request->getHeaderLine('Accept-Language');
            },
        ], $request);

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
     * @param mixed      $uri
     * @param mixed      $accept
     * @param mixed      $location
     * @param null|mixed $language
     */
    public function testLanguagesPath(array $languages, $uri, $accept, $location, $language = null)
    {
        $request = Factory::createServerRequest([], 'GET', $uri)->withHeader('Accept-Language', $accept);

        $response = Dispatcher::run([
            (new ContentLanguage($languages))->usePath()->redirect(),
            function ($request) {
                echo $request->getHeaderLine('Accept-Language');
            },
        ], $request);

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
