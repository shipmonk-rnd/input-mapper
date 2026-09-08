<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Codec;

use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Runtime\Codec;

class CodecMapperCompilerProvider implements MapperCompilerProvider
{

    /**
     * @param class-string<Codec<*, *, *, *>> $codecClassName
     * @param class-string $domainClassName
     */
    public function __construct(
        public readonly string $codecClassName,
        public readonly MapperCompiler $intermediateInputMapperCompiler,
        public readonly MapperCompiler $intermediateOutputMapperCompiler,
        public readonly string $domainClassName,
    )
    {
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new CodecInputMapperCompiler(
            $this->codecClassName,
            $this->intermediateInputMapperCompiler,
            $this->domainClassName,
        );
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return new CodecOutputMapperCompiler(
            $this->codecClassName,
            $this->intermediateOutputMapperCompiler,
            $this->domainClassName,
        );
    }

}
