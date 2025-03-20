<?php
declare(strict_types = 1);

namespace Middlewares\tests;

use Middlewares\ContentEncoding;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ContentEncodingTest extends TestCase
{
    /**
     * @return array<array<string|string[]>>
     */
    public static function encodingsProvider(): array
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
     * @param string[] $encodings
     */
    public function testEncoding(array $encodings, ?string $accept = null, string $encoding = ''): void
    {
        $request = Factory::createServerRequest('GET', '/');

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
