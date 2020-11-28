<?php
declare(strict_types = 1);

namespace Middlewares\tests;

use Middlewares\ContentLanguage;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ContentLanguageTest extends TestCase
{
    public function languagesProvider(): array
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
            [
                [],
            ],
        ];
    }

    /**
     * @dataProvider languagesProvider
     */
    public function testLanguages(array $languages, string $accept = null, string $language = null): void
    {
        $request = Factory::createServerRequest('GET', '/');

        if ($accept !== null) {
            $request = $request->withHeader('Accept-Language', $accept);
        }

        $response = Dispatcher::run([
            new ContentLanguage($languages),
            function ($request) {
                echo $request->getHeaderLine('Accept-Language');
            },
        ], $request);

        self::assertEquals($language, (string) $response->getBody());
    }

    public function languagesPathProvider(): array
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
    public function testLanguagesPath(
        array $languages,
        string $uri,
        string $accept,
        string $location,
        string $language = null
    ): void {
        $request = Factory::createServerRequest('GET', $uri)->withHeader('Accept-Language', $accept);

        $response = Dispatcher::run([
            (new ContentLanguage($languages))->usePath()->redirect(),
            function ($request) {
                $language = $request->getHeaderLine('Accept-Language');
                echo $language;
                return Factory::createResponse()->withHeader('Content-Language', $language);
            },
        ], $request);

        self::assertEquals($language, (string) $response->getBody());

        if ($language === null) {
            self::assertEquals(302, $response->getStatusCode());
            self::assertEquals($location, $response->getHeaderLine('Location'));
        } else {
            self::assertEquals(200, $response->getStatusCode());
            self::assertEmpty($response->getHeaderLine('Location'));
        }
    }
}
