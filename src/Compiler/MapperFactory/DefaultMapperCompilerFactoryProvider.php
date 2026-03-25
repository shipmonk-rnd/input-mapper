<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

class DefaultMapperCompilerFactoryProvider implements MapperCompilerFactoryProvider
{

    private ?MapperCompilerFactory $mapperCompilerFactory = null;

    public function get(): MapperCompilerFactory
    {
        return $this->mapperCompilerFactory ??= $this->create();
    }

    protected function create(): MapperCompilerFactory
    {
        $config = $this->createParserConfig();
        return new DefaultMapperCompilerFactory($this->createPhpDocLexer($config), $this->createPhpDocParser($config));
    }

    protected function createPhpDocLexer(ParserConfig $config): Lexer
    {
        return new Lexer($config);
    }

    protected function createParserConfig(): ParserConfig
    {
        return new ParserConfig([]);
    }

    protected function createPhpDocParser(ParserConfig $config): PhpDocParser
    {
        $phpDocExprParser = new ConstExprParser($config);
        $phpDocTypeParser = new TypeParser($config, $phpDocExprParser);

        return new PhpDocParser($config, $phpDocTypeParser, $phpDocExprParser);
    }

}
