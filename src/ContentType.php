<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Middleware\ServerMiddlewareInterface;
use Interop\Http\Middleware\DelegateInterface;
use Negotiation\Negotiator;

class ContentType implements ServerMiddlewareInterface
{
    use Utils\NegotiationTrait;

    /**
     * @var string Default format
     */
    private $default = 'html';

    /**
     * @var array Available formats with the mime types
     */
    private $formats;

    /**
     * @var boolean Include X-Content-Type-Options: nosniff
     */
    private $nosniff = true;

    /**
     * Define de available formats.
     *
     * @param array|null $formats
     */
    public function __construct(array $formats = null)
    {
        $this->formats = $formats ?: require __DIR__.'/formats_defaults.php';
    }

    /**
     * Set the default format.
     *
     * @param string $format
     *
     * @return self
     */
    public function defaultFormat($format)
    {
        $this->default = $format;

        return $this;
    }

    /**
     * Configure the nosniff option.
     *
     * @param bool $nosniff
     *
     * @return self
     */
    public function nosniff($nosniff = true)
    {
        $this->nosniff = $nosniff;

        return $this;
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
        $format = $this->detectFromExtension($request) ?: $this->detectFromHeader($request) ?: $this->default;
        $contentType = $this->formats[$format]['mime-type'][0];

        $response = $delegate->process($request->withHeader('Accept', $contentType));

        if (!$response->hasHeader('Content-Type')) {
            $response = $response->withHeader('Content-Type', $contentType.'; charset=utf-8');
        }

        if ($this->nosniff && !$response->hasHeader('X-Content-Type-Options')) {
            $response = $response->withHeader('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }

    /**
     * Returns the format using the file extension.
     *
     * @return null|string
     */
    private function detectFromExtension(ServerRequestInterface $request)
    {
        $extension = strtolower(pathinfo($request->getUri()->getPath(), PATHINFO_EXTENSION));

        if (empty($extension)) {
            return;
        }

        foreach ($this->formats as $format => $data) {
            if (in_array($extension, $data['extension'], true)) {
                return $format;
            }
        }
    }

    /**
     * Returns the format using the Accept header.
     *
     * @return null|string
     */
    private function detectFromHeader(ServerRequestInterface $request)
    {
        $headers = call_user_func_array('array_merge', array_column($this->formats, 'mime-type'));
        $accept = $request->getHeaderLine('Accept');
        $mime = $this->negotiateHeader($accept, new Negotiator(), $headers);

        if ($mime !== null) {
            foreach ($this->formats as $format => $data) {
                if (in_array($mime, $data['mime-type'], true)) {
                    return $format;
                }
            }
        }
    }
}
