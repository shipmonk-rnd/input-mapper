<?php declare(strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\Discriminator;

#[Discriminator(
    'type',
    mapping: [
        AnimalType::Cat->value => AnimalCatInput::class,
        AnimalType::Dog->value => AnimalDogInput::class,
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
