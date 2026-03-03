<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use LogicException;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;

class DefaultOutputMapperCompilerFactory implements MapperCompilerFactory
{

    public function __construct(
        protected readonly Lexer $phpDocLexer,
        protected readonly PhpDocParser $phpDocParser,
    )
    {
    }

    public function create(
        TypeNode $type,
        array $options = [],
    ): MapperCompiler
    {
        throw new LogicException('DefaultOutputMapperCompilerFactory is not yet implemented.');
    }

}
