<?php
declare(strict_types = 1);

namespace Middlewares\Utils;

use Negotiation\AbstractNegotiator;

/**
 * Common functions used by all negotiation middlewares.
 */
trait NegotiationTrait
{
    /**
     * Returns the best value of a header.
     *
     * @return string|null
     */
    private function negotiateHeader(string $accept, AbstractNegotiator $negotiator, array $priorities)
    {
        try {
            $best = $negotiator->getBest($accept, $priorities);
        } catch (\Exception $exception) {
            return;
        }

        if ($best) {
            return $best->getValue();
        }
    }
}
