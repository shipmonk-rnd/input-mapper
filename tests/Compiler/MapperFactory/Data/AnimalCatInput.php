<?php declare(strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

class AnimalCatInput extends AnimalInput
{

    public function __construct(
        int        $id,
        AnimalType $type,
    )
    {
        parent::__construct($id, $type);
    }

}
