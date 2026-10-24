<?php

namespace OneToMany\PostgresBundle\Tests\Lock;

use OneToMany\PostgresBundle\Exception\DomainException;
use OneToMany\PostgresBundle\Lock\Key;
use PHPUnit\Framework\TestCase;

final class KeyTest extends TestCase
{
    public function testConstructorRequiresNonEmptyKey(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIs('The key cannot be empty.');

        new Key('');
    }

    public function testCreatingKeyRequiresNonEmptyBits(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIs('At least one non-empty component is required to create a key.');

        Key::create();
    }

    public function testCreatingKeyRequiresNonEmptyComponentsAfterFiltering(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIs('At least one non-empty component is required to create a key.');

        Key::create('', '  ', "\n");
    }

    public function testGeneratingKey(): void
    {
        $this->assertSame('advisory:10:lock:key', Key::create('advisory', 10, 'lock', '', 'key')->getKey());
    }
}
