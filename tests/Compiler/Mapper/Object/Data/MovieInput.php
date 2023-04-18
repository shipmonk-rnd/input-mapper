<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Runtime\Optional;

class MovieInput
{

    /**
     * @param  Optional<string>  $description
     * @param  list<string>      $genres
     * @param  list<PersonInput> $actors
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly Optional $description,
        public readonly int $year,
        public readonly array $genres,
        public readonly PersonInput $director,
        public readonly array $actors,
    )
    {
    }

}
