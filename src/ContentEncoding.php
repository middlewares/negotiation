<?php
declare(strict_types = 1);

namespace Middlewares;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Negotiation\EncodingNegotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentEncoding implements MiddlewareInterface
{
    use Utils\NegotiationTrait;

    /**
     * @var array Available encodings
     */
    private $encodings = [
        'gzip',
        'deflate',
    ];

    /**
     * Define de available encodings.
     */
    public function __construct(array $encodings = null)
    {
        if ($encodings !== null) {
            $this->encodings = $encodings;
        }
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader('Accept-Encoding')) {
            $accept = $request->getHeaderLine('Accept-Encoding');
            $encoding = $this->negotiateHeader($accept, new EncodingNegotiator(), $this->encodings);

            if ($encoding === null) {
                return $handler->handle($request->withoutHeader('Accept-Encoding'));
            }

            return $handler->handle($request->withHeader('Accept-Encoding', $encoding));
        }

        return $handler->handle($request);
    }
}
