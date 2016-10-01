<?php

namespace Middlewares\tests;

use Middlewares\ContentEncoding;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use mindplay\middleman\Dispatcher;

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
        $request = (new ServerRequest())->withHeader('Accept-Encoding', $accept);

        $response = (new Dispatcher([
            new ContentEncoding($encodings),

            function ($request) {
                $response = new Response();
                $response->getBody()->write($request->getHeaderLine('Accept-Encoding'));

                return $response;
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($encoding, (string) $response->getBody());
    }
}
