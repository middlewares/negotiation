<?php
declare(strict_types = 1);

namespace Middlewares\tests;

use Middlewares\ContentType;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ContentTypeTest extends TestCase
{
    /**
     * @return array<array<string>>
     */
    public static function formatsProvider(): array
    {
        return [
            [
                '/',
                'application/xml;charset=UTF-8,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'text/html',
                'text/html; charset=UTF-8',
            ],
            [
                '/test.json',
                '',
                'application/json',
                'application/json; charset=UTF-8',
            ],
            [
                '/',
                '',
                'text/html',
                'text/html; charset=UTF-8',
            ],
            [
                '/',
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                'text/html',
                'text/html; charset=UTF-8',
            ],
            [
                '/',
                'text/html, image/gif, image/jpeg, *; q=0.2, */*; q=0.2',
                'text/html',
                'text/html; charset=UTF-8',
            ],
            [
                '/points.kml',
                '',
                'application/vnd.google-earth.kml+xml',
                'application/vnd.google-earth.kml+xml; charset=UTF-8',
            ],
            [
                '/points.xxx',
                '',
                'text/html',
                'text/html; charset=UTF-8',
            ],
        ];
    }

    /**
     * @dataProvider formatsProvider
     */
    public function testFormats(string $uri, string $acceptHeader, string $accept, string $contentType): void
    {
        $request = Factory::createServerRequest('GET', $uri);

        if (!empty($acceptHeader)) {
            $request = $request->withHeader('Accept', $acceptHeader);
        }

        $response = Dispatcher::run([
            new ContentType(),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertEquals($accept, (string) $response->getBody());
        $this->assertEquals($contentType, $response->getHeaderLine('Content-Type'));
        $this->assertEquals('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    public function testFormatNotFound(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'text/xxx');

        $response = Dispatcher::run([
            (new ContentType())->errorResponse(),
        ], $request);

        $this->assertEquals(406, $response->getStatusCode());
        $this->assertEquals('Not Acceptable', $response->getReasonPhrase());
    }

    public function testCustomFormats(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'text/xxx');

        $response = Dispatcher::run([
            new ContentType(['json', 'html']),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertEquals('application/json', (string) $response->getBody());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testDisableNosniff(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'text/json');

        $response = Dispatcher::run([
            (new ContentType())->nosniff(false),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertEquals('application/json', (string) $response->getBody());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertFalse($response->hasHeader('X-Content-Type-Options'));
    }

    /**
     * @return array<array<string[]|string>>
     */
    public static function charsetProvider(): array
    {
        return [
            [
                ['UTF-8'],
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'ISO-8859-1, UTF-8; q=0.9',
                'text/html; charset=UTF-8',
            ], [
                ['ISO-8859-1', 'UTF-8'],
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'ISO-8859-1, UTF-8; q=0.9',
                'text/html; charset=ISO-8859-1',
            ], [
                ['ISO-8859-1', 'UTF-8'],
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'UTF-8, ISO-8859-1; q=0.9',
                'text/html; charset=UTF-8',
            ],
        ];
    }

    /**
     * @dataProvider charsetProvider
     * @param string[] $charsets
     */
    public function testCharset(array $charsets, string $accept, string $acceptCharset, string $result): void
    {
        $request = Factory::createServerRequest('GET', '/')
            ->withHeader('Accept-Charset', $acceptCharset)
            ->withHeader('Accept', $accept);

        $response = Dispatcher::run([
            (new ContentType())
                ->charsets($charsets),
        ], $request);

        $this->assertEquals($result, $response->getHeaderLine('Content-Type'));
    }

    public function testMissingHeader(): void
    {
        $request = Factory::createServerRequest('GET', '/');

        $response = Dispatcher::run([
            (new ContentType(['json', 'html']))->errorResponse(),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertEquals('application/json', (string) $response->getBody());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testNotMissingHeader(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'image/*');

        $response = Dispatcher::run([
            (new ContentType(['json', 'html']))->errorResponse(),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertEquals('', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(406, $response->getStatusCode());
    }

    public function testAttribute(): void
    {
        $request = Factory::createServerRequest('GET', '/')->withHeader('Accept', 'text/json');

        $response = Dispatcher::run([
            (new ContentType(['json', 'html']))->attribute('format_name'),
            function ($request) {
                echo $request->getAttribute('format_name');
            },
        ], $request);

        $this->assertEquals('', $response->getHeaderLine('application/json'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('json', (string) $response->getBody());
    }
}
