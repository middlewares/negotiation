<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Negotiation\Negotiator;
use Negotiation\CharsetNegotiator;
use InvalidArgumentException;

class ContentType implements MiddlewareInterface
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
     * @var array Available charsets
     */
    private $charsets = ['UTF-8'];

    /**
     * @var bool Include X-Content-Type-Options: nosniff
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
        if (!isset($this->formats[$format])) {
            throw new InvalidArgumentException("The default format '{$format}' is not available");
        }

        //Move the default format to be the first element of the array
        $default = $this->formats[$format];
        unset($this->formats[$format]);
        $this->formats = [$format => $default] + $this->formats;

        return $this;
    }

    /**
     * Set the available charsets. The first value will be used as default
     *
     * @param array $charsets
     *
     * @return self
     */
    public function charsets(array $charsets)
    {
        $this->charsets = $charsets;

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
        $format = $this->detectFromExtension($request) ?: $this->detectFromHeader($request) ?: key($this->formats);
        $contentType = $this->formats[$format]['mime-type'][0];
        $charset = $this->detectCharset($request) ?: current($this->charsets);

        $request = $request
            ->withHeader('Accept', $contentType)
            ->withHeader('Accept-Charset', $charset);

        $response = $delegate->process($request);

        if (!$response->hasHeader('Content-Type')) {
            $needCharset = !empty($this->formats[$format]['charset']);

            if ($needCharset) {
                $contentType .= '; charset='.$charset;
            }

            $response = $response->withHeader('Content-Type', $contentType);
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

    /**
     * Returns the charset accepted.
     *
     * @return null|string
     */
    private function detectCharset(ServerRequestInterface $request)
    {
        $accept = $request->getHeaderLine('Accept-Charset');

        return $this->negotiateHeader($accept, new CharsetNegotiator(), $this->charsets);
    }
}
