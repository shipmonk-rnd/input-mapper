<?php declare(strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

class AnimalDogInput extends AnimalInput
{

    public function __construct(
        int                    $id,
        AnimalType             $type,
        public readonly string $dogField,
    )
    {
        parent::__construct($id, $type);
    }

}
