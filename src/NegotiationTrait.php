<?php
declare(strict_types = 1);

namespace Middlewares;

use Exception;
use Negotiation\AbstractNegotiator;

/**
 * Common functions used by all negotiation middlewares.
 */
trait NegotiationTrait
{
    /**
     * Returns the best value of a header.
     */
    private function negotiateHeader(string $accept, AbstractNegotiator $negotiator, array $priorities): ?string
    {
        try {
            $best = $negotiator->getBest($accept, $priorities);
            return $best ? $best->getValue() : null;
        } catch (Exception $exception) {
            return null;
        }
    }
}
