<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

interface GenericMapperCompiler extends MapperCompiler
{

    /**
     * @return list<GenericMapperParameter>
     */
    public function getGenericParameters(): array;

}
