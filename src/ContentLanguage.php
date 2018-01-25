<?php
declare(strict_types = 1);

namespace Middlewares;

use Negotiation\LanguageNegotiator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContentLanguage implements MiddlewareInterface
{
    use NegotiationTrait;

    /**
     * @var array Allowed languages
     */
    private $languages = [];

    /**
     * @var bool Use the path to detect the language
     */
    private $usePath = false;

    /**
     * @var bool Returns a redirect response or not
     */
    private $redirect = false;

    /**
     * Define de available languages.
     */
    public function __construct(array $languages)
    {
        $this->languages = $languages;
    }

    /**
     * Use the base path to detect the current language.
     */
    public function usePath(bool $usePath = true): self
    {
        $this->usePath = $usePath;

        return $this;
    }

    /**
     * Whether returns a 302 response to the new path.
     * Note: This only works if usePath is true.
     */
    public function redirect(bool $redirect = true): self
    {
        $this->redirect = (bool) $redirect;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $language = $this->detectFromPath($uri->getPath());

        if ($language === null) {
            $language = $this->detectFromHeader($request);

            if ($this->redirect && $this->usePath) {
                $location = $uri->withPath(str_replace('//', '/', $language.'/'.$uri->getPath()));

                return Utils\Factory::createResponse(302)
                    ->withHeader('Location', (string) $location);
            }
        }

        $response = $handler->handle($request->withHeader('Accept-Language', $language));

        if (!$response->hasHeader('Content-Language')) {
            return $response->withHeader('Content-Language', $language);
        }

        return $response;
    }

    /**
     * Returns the format using the file extension.
     *
     * @return null|string
     */
    private function detectFromPath(string $path)
    {
        if (!$this->usePath) {
            return;
        }

        $dirs = explode('/', ltrim($path, '/'), 2);
        $first = strtolower(array_shift($dirs));

        if (!empty($first) && in_array($first, $this->languages, true)) {
            return $first;
        }
    }

    /**
     * Returns the format using the Accept-Language header.
     *
     * @return null|string
     */
    private function detectFromHeader(ServerRequestInterface $request)
    {
        $accept = $request->getHeaderLine('Accept-Language');
        $language = $this->negotiateHeader($accept, new LanguageNegotiator(), $this->languages);

        if (empty($language)) {
            return isset($this->languages[0]) ? $this->languages[0] : '';
        }

        return $language;
    }
}
