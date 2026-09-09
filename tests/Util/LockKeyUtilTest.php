<?php

namespace OneToMany\PostgresBundle\Tests\Util;

use OneToMany\PostgresBundle\Exception\DomainException;
use OneToMany\PostgresBundle\Util\LockKeyUtil;
use PHPUnit\Framework\TestCase;

final class LockKeyUtilTest extends TestCase
{
    public function testCreatingKeyRequiresNonEmptyBits(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIs('A valid key cannot be generated because no bits were provided.');

        LockKeyUtil::generate();
    }

    public function testCreatingKeyRequiresNonEmptyBitsAfterFiltering(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIs('A valid key cannot be generated because no bits were provided.');

        LockKeyUtil::generate('', '  ', "\n");
    }

    public function testGeneratingKey(): void
    {
        $this->assertSame('advisory:10:lock:key', LockKeyUtil::generate('advisory', 10, 'lock', '', 'key'));
    }
}
