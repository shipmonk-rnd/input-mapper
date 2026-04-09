<?php declare(strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

enum AnimalType: string
{

    case Dog = 'dog';
    case Cat = 'cat';

}
