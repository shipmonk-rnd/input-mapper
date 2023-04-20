<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;

class DefaultMapperCompilerFactoryProvider implements MapperCompilerFactoryProvider
{

    private ?MapperCompilerFactory $mapperCompilerFactory = null;

    public function get(): MapperCompilerFactory
    {
        return $this->mapperCompilerFactory ??= $this->create();
    }

    protected function create(): MapperCompilerFactory
    {
        return new DefaultMapperCompilerFactory($this->createPhpDocLexer(), $this->createPhpDocParser());
    }

    protected function createPhpDocLexer(): Lexer
    {
        return new Lexer();
    }

    protected function createPhpDocParser(): PhpDocParser
    {
        $phpDocExprParser = new ConstExprParser(unescapeStrings: true);
        $phpDocTypeParser = new TypeParser($phpDocExprParser);

        return new PhpDocParser($phpDocTypeParser, $phpDocExprParser);
    }

}
