<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Type;

enum GenericTypeVariance
{

    case Invariant;
    case Covariant;
    case Contravariant;

}
