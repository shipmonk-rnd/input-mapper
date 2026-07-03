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

    private ?CodecRegistry $lastCodecRegistry = null;

    public function __construct(
        protected readonly ?PropertyNameTransformer $propertyNameTransformer = null,
    )
    {
    }

    public function get(?CodecRegistry $codecRegistry = null): MapperCompilerFactory
    {
        if ($this->mapperCompilerFactory === null || $codecRegistry !== $this->lastCodecRegistry) {
            $this->lastCodecRegistry = $codecRegistry;
            $this->mapperCompilerFactory = $this->create($codecRegistry);
        }

        return $this->mapperCompilerFactory;
    }

    protected function create(?CodecRegistry $codecRegistry = null): MapperCompilerFactory
    {
        $config = $this->createParserConfig();
        return new DefaultMapperCompilerFactory(
            $this->createPhpDocLexer($config),
            $this->createPhpDocParser($config),
            [],
            $this->propertyNameTransformer,
            $codecRegistry ?? new CodecRegistry(),
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
