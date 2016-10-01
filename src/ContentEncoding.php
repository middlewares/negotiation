<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use Negotiation\EncodingNegotiator;

class ContentEncoding implements ServerMiddlewareInterface
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
     *
     * @param array|null $encodings
     */
    public function __construct(array $encodings = null)
    {
        if ($encodings !== null) {
            $this->encodings = $encodings;
        }
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($request->hasHeader('Accept-Encoding')) {
            $accept = $request->getHeaderLine('Accept-Encoding');
            $encoding = $this->negotiateHeader($accept, new EncodingNegotiator(), $this->encodings);

            if ($encoding) {
                return $delegate->process($request->withHeader('Accept-Encoding', $encoding));
            }
        }

        return $delegate->process($request);
    }
}
