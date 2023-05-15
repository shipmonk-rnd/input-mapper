<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
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
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<MovieInput>
 */
class MovieMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): MovieInput
    {
        if (!is_array($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'array');
        }

        if (!array_key_exists('id', $data)) {
            throw MappingFailedException::missingKey($path, 'id');
        }

        if (!array_key_exists('title', $data)) {
            throw MappingFailedException::missingKey($path, 'title');
        }

        if (!array_key_exists('year', $data)) {
            throw MappingFailedException::missingKey($path, 'year');
        }

        if (!array_key_exists('genres', $data)) {
            throw MappingFailedException::missingKey($path, 'genres');
        }

        if (!array_key_exists('director', $data)) {
            throw MappingFailedException::missingKey($path, 'director');
        }

        if (!array_key_exists('actors', $data)) {
            throw MappingFailedException::missingKey($path, 'actors');
        }

        $knownKeys = ['id' => true, 'title' => true, 'description' => true, 'year' => true, 'genres' => true, 'director' => true, 'actors' => true];
        $extraKeys = array_diff_key($data, $knownKeys);

        if (count($extraKeys) > 0) {
            throw MappingFailedException::extraKeys($path, array_keys($extraKeys));
        }

        return new MovieInput(
            $this->mapId($data['id'], [...$path, 'id']),
            $this->mapTitle($data['title'], [...$path, 'title']),
            array_key_exists('description', $data) ? $this->mapDescription($data['description'], [...$path, 'description']) : Optional::none($path, 'description'),
            $this->mapYear($data['year'], [...$path, 'year']),
            $this->mapGenres($data['genres'], [...$path, 'genres']),
            $this->mapDirector($data['director'], [...$path, 'director']),
            $this->mapActors($data['actors'], [...$path, 'actors']),
        );
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapId(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapTitle(mixed $data, array $path = []): string
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @return Optional<string>
     * @throws MappingFailedException
     */
    private function mapDescription(mixed $data, array $path = []): Optional
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        return Optional::of($data);
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapYear(mixed $data, array $path = []): int
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return $data;
    }

    /**
     * @param  list<string|int> $path
     * @return list<string>
     * @throws MappingFailedException
     */
    private function mapGenres(mixed $data, array $path = []): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            if (!is_string($item)) {
                throw MappingFailedException::incorrectType($item, [...$path, $index], 'string');
            }

            $mapped[] = $item;
        }

        return $mapped;
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    private function mapDirector(mixed $data, array $path = []): PersonInput
    {
        return $this->provider->get(PersonInput::class)->map($data, $path);
    }

    /**
     * @param  list<string|int> $path
     * @return list<PersonInput>
     * @throws MappingFailedException
     */
    private function mapActors(mixed $data, array $path = []): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'list');
        }

        $mapped = [];

        foreach ($data as $index => $item) {
            $mapped[] = $this->provider->get(PersonInput::class)->map($item, [...$path, $index]);
        }

        return $mapped;
    }
}
