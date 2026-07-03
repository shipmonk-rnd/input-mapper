<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\MapperFactory;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use ShipMonk\InputMapper\Compiler\PropertyNameTransformer\PropertyNameTransformer;
use ShipMonk\InputMapper\Runtime\CodecRegistry;

class DefaultMapperCompilerFactoryProvider implements MapperCompilerFactoryProvider
{

    private ?MapperCompilerFactory $mapperCompilerFactory = null;

    protected readonly CodecRegistry $codecRegistry;

    public function __construct(
        protected readonly ?PropertyNameTransformer $propertyNameTransformer = null,
        ?CodecRegistry $codecRegistry = null,
    )
    {
        $this->codecRegistry = $codecRegistry ?? new CodecRegistry();
    }

    public function get(): MapperCompilerFactory
    {
        return $this->mapperCompilerFactory ??= $this->create();
    }

    public function getCodecRegistry(): CodecRegistry
    {
        return $this->codecRegistry;
    }

    protected function create(): MapperCompilerFactory
    {
        $config = $this->createParserConfig();
        return new DefaultMapperCompilerFactory(
            $this->createPhpDocLexer($config),
            $this->createPhpDocParser($config),
            [],
            $this->propertyNameTransformer,
            $this->codecRegistry,
        );
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
