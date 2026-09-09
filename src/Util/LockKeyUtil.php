<?php

namespace OneToMany\PostgresBundle\Util;

use OneToMany\PostgresBundle\Exception\DomainException;

use function array_filter;
use function array_map;
use function implode;
use function trim;

final readonly class LockKeyUtil
{
    private function __construct()
    {
    }

    /**
     * @return non-empty-string
     *
     * @throws DomainException when a valid key cannot be generated
     */
    public static function generate(int|string ...$bits): string
    {
        if ([] !== $bits) {
            $mapper = function (int|string $bit): string {
                return trim((string) $bit);
            };

            /** @var list<string> $bits */
            $bits = array_map($mapper, $bits);

            $filter = function (string $bit): bool {
                return '' !== $bit;
            };

            $bits = array_filter($bits, $filter);
        }

        if ([] === $bits) {
            throw new DomainException('A valid key cannot be generated because no bits were provided.');
        }

        return implode(':', $bits);
    }
}
