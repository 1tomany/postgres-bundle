<?php

namespace OneToMany\PostgresBundle\Lock;

use OneToMany\PostgresBundle\Exception\DomainException;

use function array_push;
use function count;
use function crc32;
use function implode;
use function trim;

final class Key implements \Stringable
{
    private int $id;

    /**
     * @var non-empty-string
     */
    private string $key;

    /**
     * @throws DomainException when the key is empty
     */
    public function __construct(int|string|null $key)
    {
        if ('' === $key = trim((string) $key)) {
            throw new DomainException('The key cannot be empty.');
        }

        $this->key = $key;
    }

    /**
     * @see \Stringable
     *
     * @return non-empty-string
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->key;
    }

    /**
     * @throws DomainException when a valid key cannot be generated
     */
    public static function create(int|string ...$bits): static
    {
        $keyBits = [];

        if (0 !== $count = count($bits)) {
            for ($i = 0; $i < $count; ++$i) {
                $bits[$i] = (string) $bits[$i];

                if ('' !== $bit = trim($bits[$i])) {
                    array_push($keyBits, $bit);
                }
            }
        }

        if ([] === $keyBits) {
            throw new DomainException('At least one non-empty component is required to create a key.');
        }

        return new static(implode(':', $keyBits));
    }

    public function getId(): int
    {
        if (!isset($this->id)) {
            $this->generateId();
        }

        return $this->id;
    }

    /**
     * @return non-empty-string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    private function generateId(): void
    {
        $this->id = crc32($this->key);
    }
}
