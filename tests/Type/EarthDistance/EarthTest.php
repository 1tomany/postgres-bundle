<?php

namespace OneToMany\PostgresBundle\Tests\Type\EarthDistance;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use OneToMany\PostgresBundle\Exception\InvalidArgumentException;
use OneToMany\PostgresBundle\Type\EarthDistance\Earth;
// use Doctrine\DBAL\Types\ConversionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('UnitTests')]
#[Group('TypeTests')]
final class EarthTest extends TestCase
{
    public function testGettingName(): void
    {
        $this->assertEquals('earth', new Earth()->getName());
    }

    public function testGettingSQLDeclaration(): void
    {
        $platform = $this->createStub(AbstractPlatform::class);

        $this->assertEquals('earth', new Earth()->getSQLDeclaration([], $platform));
    }

    public function _testConvertingToPHPValueExpectsNullOrString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Earth()->convertToPHPValue(['3.14159'], $this->createStub(AbstractPlatform::class));
    }

    /**
     * @param ?array<string> $phpValue
     */
    #[DataProvider('providerDatabaseAndPHPValue')]
    public function testConvertingToPHPValue(mixed $databaseValue, ?array $phpValue): void
    {
        $this->assertSame($phpValue, new Earth()->convertToPHPValue($databaseValue, $this->createStub(AbstractPlatform::class)));
    }

    /**
     * @return array<array<null|string|array<string>>>
     */
    public static function providerDatabaseAndPHPValue(): array
    {
        $provider = [
            [null, null],
            ['', null],
            ['(-1.0)', null],
            ['(-1.0,-2.0)', null],
            ['(-1.0,-2.0,-3.0)', null],
            ['(-1.0, -2.0, -3.0)', ['-1.0', '-2.0', '-3.0']],
        ];

        return $provider;
    }

    public function _testConvertingToDatabaseValueRequiresArrayOfThreeElements(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "earth" requires list of exactly three elements.');

        new Earth()->convertToDatabaseValue(['1.0'], $this->createStub(AbstractPlatform::class));
    }

    public function testConvertingToDatabaseValueRequiresArrayOfNumericElements(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "earth" requires a list of exactly three numeric elements.');

        new Earth()->convertToDatabaseValue(['abc', 'def', '1.0'], $this->createStub(AbstractPlatform::class));
    }

    public function testConvertingToDatabaseValue(): void
    {
        $this->assertEquals('(1.0, 3.14, 0.577)', new Earth()->convertToDatabaseValue(['1.0', '3.14', '0.577'], $this->createStub(AbstractPlatform::class)));
    }

}
