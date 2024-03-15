<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use ShipMonk\InputMapper\Compiler\Type\GenericTypeParameter;

interface GenericMapperCompiler extends MapperCompiler
{

    /**
     * @return list<GenericTypeParameter>
     */
    public function getGenericParameters(): array;

}
