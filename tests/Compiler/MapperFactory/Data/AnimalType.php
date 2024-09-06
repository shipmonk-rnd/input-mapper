<?php declare(strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\MapperFactory\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\Discriminator;

enum AnimalType: string
{

    case Dog = 'dog';
    case Cat = 'cat';

}
