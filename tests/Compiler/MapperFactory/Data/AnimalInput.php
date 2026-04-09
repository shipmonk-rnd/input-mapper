<?php declare(strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

#[Discriminator(
    'type',
    mapping: [
        'cat' => AnimalCatInput::class,
        'dog' => AnimalDogInput::class,
    ],
)]
abstract class AnimalInput
{

    public function __construct(
        public readonly int      $id,
        public readonly AnimalType $type,
    )
    {
    }

}
