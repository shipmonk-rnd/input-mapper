<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\String\Data;

use Nette\Utils\Validators;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function is_string;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<mixed>
 */
class UrlValidatorMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     */
    public function map(mixed $data, array $path = []): mixed
    {
        if (is_string($data) && !Validators::isUrl($data)) {
            throw MappingFailedException::incorrectValue($data, $path, 'valid URL');
        }

        return $data;
    }
}
