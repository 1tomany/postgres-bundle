<?php

namespace OneToMany\PostgresBundle\Function\EarthDistance;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use OneToMany\PostgresBundle\Function\Exception\ParsingFunctionFailedNullTokensFoundException;

use function vsprintf;

final class Boundary extends FunctionNode
{
    private ?PathExpression $earthCol = null;
    private InputParameter|Literal|null $latitude = null;
    private InputParameter|Literal|null $longitude = null;
    private InputParameter|Literal|null $boxMeters = null;

    /**
     * @see Doctrine\ORM\Query\AST\Functions\FunctionNode
     */
    public function parse(Parser $parser): void
    {
        // BOUNDARY(earthCol, :latitude, :longitude, :boxSize)
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        // earth Column
        $this->earthCol = $parser->SingleValuedPathExpression();

        $parser->match(TokenType::T_COMMA);

        /** @var InputParameter|Literal $latitude */
        $latitude = $parser->ScalarExpression();

        $parser->match(TokenType::T_COMMA);

        /** @var InputParameter|Literal $longitude */
        $longitude = $parser->ScalarExpression();

        $parser->match(TokenType::T_COMMA);

        /** @var InputParameter|Literal $boxSize */
        $boxSize = $parser->ScalarExpression();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);

        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->boxMeters = $boxSize;
    }

    /**
     * @see Doctrine\ORM\Query\AST\Functions\FunctionNode
     *
     * @return non-empty-string
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        if (
            null === $this->earthCol
            || null === $this->latitude
            || null === $this->longitude
            || null === $this->boxMeters
        ) {
            throw new ParsingFunctionFailedNullTokensFoundException($this->name);
        }

        return vsprintf('%s <@ earth_box(ll_to_earth(%s, %s), %s)', [
            $this->earthCol->dispatch($sqlWalker),
            $this->latitude->dispatch($sqlWalker),
            $this->longitude->dispatch($sqlWalker),
            $this->boxMeters->dispatch($sqlWalker),
        ]);
    }
}
