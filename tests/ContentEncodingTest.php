<?php
declare(strict_types = 1);

namespace Middlewares\tests;

use Middlewares\ContentEncoding;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ContentEncodingTest extends TestCase
{
    public function encodingsProvider(): array
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
            ], [
                ['gzip'],
            ],
        ];
    }

    /**
     * @dataProvider encodingsProvider
     */
    public function testEncoding(array $encodings, string $accept = null, string $encoding = '')
    {
        $request = Factory::createServerRequest();

        if ($accept !== null) {
            $request = $request->withHeader('Accept-Encoding', $accept);
        }

        $response = Dispatcher::run([
            new ContentEncoding($encodings),
            function ($request) {
                echo $request->getHeaderLine('Accept-Encoding');
            },
        ], $request);

        $this->assertEquals($encoding, (string) $response->getBody());
    }
}
