<?php

namespace OneToMany\PostgresBundle\Type\EarthDistance;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

use function array_map;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function trim;

/**
 * @phpstan-type EarthPoint list{
 *   0: numeric-string,
 *   1: numeric-string,
 *   2: numeric-string,
 * }
 */
final class Earth extends Type
{
    /**
     * @see Doctrine\DBAL\Types\Type
     *
     * @return 'earth'
     */
    public function getName(): string
    {
        return 'earth';
    }

    /**
     * @see Doctrine\DBAL\Types\Type
     *
     * @return 'earth'
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'earth';
    }

    /**
     * @see Doctrine\DBAL\Types\Type
     *
     * @return ?EarthPoint
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?array
    {
        $value = is_string($value) ? trim($value) : '';

        // Example: (-631765.752065, -5310503.3232811, 3475694.6822414)
        if (1 === preg_match('/^\((\S+)\, (\S+)\, (\S+)\)$/', $value)) {
            /** @var EarthPoint $earthPoint */
            $earthPoint = array_map('trim', explode(',', trim($value, '()')));
        }

        return $earthPoint ?? null;
    }

    /**
     * @see Doctrine\DBAL\Types\Type
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (!is_array($value)) {
            return null;
        }

        if (3 !== count($value)) {
            throw new ConversionException(sprintf('Type "%s" requires list of exactly three values.', $this->getName()));
        }

        $points = array_map('is_numeric', $value);

        if (3 !== count($points)) {
            throw new ConversionException(sprintf('Type "%s" requires list of exactly three numeric values.', $this->getName()));
        }

        return sprintf('(%s)', implode(', ', array_values($value)));
    }
}
