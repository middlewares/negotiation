<?php

namespace Middlewares\tests;

use Middlewares\ContentType;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ContentTypeTest extends TestCase
{
    public function formatsProvider()
    {
        return [
            [
                '/',
                'application/xml;charset=UTF-8,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'text/html',
            ],
            [
                '/test.json',
                '',
                'application/json',
            ],
            [
                '/',
                '',
                'text/html',
            ],
            [
                '/',
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                'text/html',
            ],
            [
                '/',
                'text/html, image/gif, image/jpeg, *; q=0.2, */*; q=0.2',
                'text/html',
            ],
            [
                '/points.kml',
                '',
                'application/vnd.google-earth.kml+xml',
            ],
        ];
    }

    /**
     * @dataProvider formatsProvider
     * @param mixed $uri
     * @param mixed $accept
     * @param mixed $mime
     */
    public function testFormats($uri, $accept, $mime)
    {
        $request = Factory::createServerRequest([], 'GET', $uri)->withHeader('Accept', $accept);

        $response = Dispatcher::run([
            new ContentType(),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertEquals($mime, (string) $response->getBody());
        $this->assertEquals($mime.'; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    public function testFormatNotFound()
    {
        $request = Factory::createServerRequest()->withHeader('Accept', 'text/xxx');

        $response = Dispatcher::run([
            (new ContentType())->useDefault(false),
        ], $request);

        $this->assertEquals(406, $response->getStatusCode());
        $this->assertEquals('Not Acceptable', $response->getReasonPhrase());
    }

    public function testCustomFormats()
    {
        $formats = ContentType::getDefaultFormats();

        $default = $formats['json'];
        $formats = ['json' => $default] + $formats;

        $request = Factory::createServerRequest()->withHeader('Accept', 'text/xxx');

        $response = Dispatcher::run([
            new ContentType($formats),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertEquals('application/json', (string) $response->getBody());
        $this->assertEquals('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function charsetProvider()
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
     * @param mixed $charsets
     * @param mixed $accept
     * @param mixed $acceptCharset
     * @param mixed $result
     */
    public function testCharset($charsets, $accept, $acceptCharset, $result)
    {
        $request = Factory::createServerRequest([], 'GET', '/')
            ->withHeader('Accept-Charset', $acceptCharset)
            ->withHeader('Accept', $accept);

        $response = Dispatcher::run([
            (new ContentType())
                ->charsets($charsets),
        ], $request);

        $this->assertEquals($result, $response->getHeaderLine('Content-Type'));
    }
}
