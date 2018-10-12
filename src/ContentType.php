<?php
declare(strict_types = 1);

namespace Middlewares;

use Middlewares\Utils\Traits\HasResponseFactory;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Negotiation\CharsetNegotiator;
use Negotiation\Negotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContentType implements MiddlewareInterface
{
    use HasResponseFactory;
    use NegotiationTrait;

    /**
     * @var bool Whether use the first format as default
     */
    private $useDefault = true;

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
     * Return the default formats.
     */
    public static function getDefaultFormats(): array
    {
        return require __DIR__.'/formats_defaults.php';
    }

    /**
     * Define de available formats.
     */
    public function __construct(array $formats = null, ResponseFactoryInterface $responseFactory = null)
    {
        $this->formats = $formats ?: static::getDefaultFormats();
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
    }

    /**
     * Whether use the first format as default.
     * @param mixed $useDefault
     */
    public function useDefault($useDefault = true): self
    {
        $this->useDefault = (bool) $useDefault;

        return $this;
    }

    /**
     * Set the available charsets. The first value will be used as default
     */
    public function charsets(array $charsets): self
    {
        $this->charsets = $charsets;

        return $this;
    }

    /**
     * Configure the nosniff option.
     */
    public function nosniff(bool $nosniff = true): self
    {
        $this->nosniff = $nosniff;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $format = $this->detectFromExtension($request) ?: $this->detectFromHeader($request);

        if ($format === null) {
            if (!$this->useDefault) {
                return $this->createResponse(406);
            }

            $format = key($this->formats);
        }

        $contentType = $this->formats[$format]['mime-type'][0];
        $charset = $this->detectCharset($request) ?: current($this->charsets);

        $request = $request
            ->withHeader('Accept', $contentType)
            ->withHeader('Accept-Charset', $charset);

        $response = $handler->handle($request);

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
            return null;
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
