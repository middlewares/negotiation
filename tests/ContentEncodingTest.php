<?php

namespace Middlewares\tests;

use Middlewares\ContentEncoding;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class ContentEncodingTest extends \PHPUnit_Framework_TestCase
{
    public function encodingsProvider()
    {
        return [
            [
                ['gzip'],
                'gzip,deflate',
                'gzip',
            ], [
                ['deflate', 'gzip'],
                'gzip,deflate',
                'deflate',
            ], [
                [],
                '',
            ], [
                ['gzip'],
                '',
            ],
        ];
    }

    /**
     * @dataProvider encodingsProvider
     */
    public function testEncoding(array $encodings, $accept, $encoding = '')
    {
        $request = Factory::createServerRequest()->withHeader('Accept-Encoding', $accept);

        $response = Dispatcher::run([
            new ContentEncoding($encodings),
            function ($request) {
                echo $request->getHeaderLine('Accept-Encoding');
            },
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($encoding, (string) $response->getBody());
    }
}
