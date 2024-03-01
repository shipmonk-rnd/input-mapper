<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapObject;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;
use function array_diff_key;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function is_int;
use function is_string;

/**
 * Generated mapper by {@see MapObject}. Do not edit directly.
 *
 * @implements Mapper<MovieInput>
 */
class MovieMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @throws MappingFailedException
     */
    public function map(mixed $data, ?MapperContext $context = null): MovieInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'array');
        }

        if (!array_key_exists('id', $data)) {
            throw MappingFailedException::missingKey($context, 'id');
        }

        if (!array_key_exists('title', $data)) {
            throw MappingFailedException::missingKey($context, 'title');
        }

        if (!array_key_exists('year', $data)) {
            throw MappingFailedException::missingKey($context, 'year');
        }

        if (!array_key_exists('genres', $data)) {
            throw MappingFailedException::missingKey($context, 'genres');
        }

        if (!array_key_exists('director', $data)) {
            throw MappingFailedException::missingKey($context, 'director');
        }

        if (!array_key_exists('actors', $data)) {
            throw MappingFailedException::missingKey($context, 'actors');
        }

        $knownKeys = ['id' => true, 'title' => true, 'description' => true, 'year' => true, 'genres' => true, 'director' => true, 'actors' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($context, array_keys($extraKeys));
        }

        return new MovieInput(
            $this->mapId($data['id'], MapperContext::append($context, 'id')),
            $this->mapTitle($data['title'], MapperContext::append($context, 'title')),
            array_key_exists('description', $data) ? $this->mapDescription($data['description'], MapperContext::append($context, 'description')) : Optional::none($context, 'description'),
            $this->mapYear($data['year'], MapperContext::append($context, 'year')),
            $this->mapGenres($data['genres'], MapperContext::append($context, 'genres')),
            $this->mapDirector($data['director'], MapperContext::append($context, 'director')),
            $this->mapActors($data['actors'], MapperContext::append($context, 'actors')),
        );
    }

    /**
     * @throws MappingFailedException
     */
    private function mapId(mixed $data, ?MapperContext $context = null): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        return $data;
    }

    /**
     * @throws MappingFailedException
     */
    private function mapTitle(mixed $data, ?MapperContext $context = null): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'string');
        }

        return $data;
    }

    /**
     * @return Optional<string>
     * @throws MappingFailedException
     */
    private function mapDescription(mixed $data, ?MapperContext $context = null): Optional
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'string');
        }

        return Optional::of($data);
    }

    /**
     * @throws MappingFailedException
     */
    private function mapYear(mixed $data, ?MapperContext $context = null): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'int');
        }

        return $data;
    }

    /**
     * @return list<string>
     * @throws MappingFailedException
     */
    private function mapGenres(mixed $data, ?MapperContext $context = null): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            if (!is_string($item)) {
                throw MappingFailedException::incorrectType($item, MapperContext::append($context, $index), 'string');
            }

            $mapped[] = $item;
        }

        return $mapped;
    }

    /**
     * @throws MappingFailedException
     */
    private function mapDirector(mixed $data, ?MapperContext $context = null): PersonInput
    {
        return $this->provider->get(PersonInput::class)->map($data, $context);
    }

    /**
     * @return list<PersonInput>
     * @throws MappingFailedException
     */
    private function mapActors(mixed $data, ?MapperContext $context = null): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $context, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            $mapped[] = $this->provider->get(PersonInput::class)->map($item, MapperContext::append($context, $index));
        }

        return $mapped;
    }
}
