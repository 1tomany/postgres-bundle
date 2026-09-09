<?php

namespace OneToMany\PostgresBundle\Tests\Driver;

use OneToMany\PostgresBundle\Driver\AdvisoryLockManager;
use PHPUnit\Framework\TestCase;

final class AdvisoryLockManagerTest extends TestCase
{
    public function testCreatingKey(): void
    {
        $this->assertSame('advisory:10:lock::key', AdvisoryLockManager::createKey('advisory', 10, 'lock', '', 'key'));
    }
}
