<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use ShipMonk\InputMapper\Runtime\CallbackInputMapper;
use ShipMonk\InputMapper\Runtime\CallbackOutputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\CollectionInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\HierarchicalParentInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\MovieInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\PersonInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\PersonWithNullableAgeInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\PersonWithSourceKeyInput;
use ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data\SimplePersonInput;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\AllOptionalInput;
use ShipMonk\InputMapperTests\Runtime\Data\CardInput;
use ShipMonk\InputMapperTests\Runtime\Data\EmptyInput;
use ShipMonk\InputMapperTests\Runtime\Data\EventInput;
use function sys_get_temp_dir;

class RoundTripTest extends InputMapperTestCase
{

    private InputMapperProvider $inputMapperProvider;

    private OutputMapperProvider $outputMapperProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $tempDir = sys_get_temp_dir();
        $this->inputMapperProvider = new InputMapperProvider($tempDir, autoRefresh: true);
        $this->outputMapperProvider = new OutputMapperProvider($tempDir, autoRefresh: true);
    }

    public function testFlatScalarDto(): void
    {
        $data = ['id' => 1, 'name' => 'John'];
        self::assertSame($data, $this->roundTrip(SimplePersonInput::class, $data));
    }

    public function testNestedObjectsWithOptionalPresent(): void
    {
        $data = [
            'id' => 1,
            'title' => 'The Matrix',
            'description' => 'Sci-fi movie',
            'year' => 1_999,
            'genres' => ['Action', 'Sci-Fi'],
            'director' => ['id' => 7, 'name' => 'Lana Wachowski'],
            'actors' => [
                ['id' => 8, 'name' => 'Keanu Reeves', 'age' => 56],
                ['id' => 9, 'name' => 'Laurence Fishburne'],
            ],
        ];
        self::assertSame($data, $this->roundTrip(MovieInput::class, $data));
    }

    public function testNestedObjectsWithOptionalAbsent(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Test Movie',
            'year' => 2024,
            'genres' => ['Drama'],
            'director' => ['id' => 1, 'name' => 'Director'],
            'actors' => [],
        ];
        self::assertSame($data, $this->roundTrip(MovieInput::class, $data));
    }

    public function testNullableFieldWithValue(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'age' => 30];
        self::assertSame($data, $this->roundTrip(PersonWithNullableAgeInput::class, $data));
    }

    public function testNullableFieldWithNull(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'age' => null];
        self::assertSame($data, $this->roundTrip(PersonWithNullableAgeInput::class, $data));
    }

    public function testOptionalFieldPresent(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'age' => 30];
        self::assertSame($data, $this->roundTrip(PersonInput::class, $data));
    }

    public function testOptionalFieldAbsent(): void
    {
        $data = ['id' => 1, 'name' => 'John'];
        self::assertSame($data, $this->roundTrip(PersonInput::class, $data));
    }

    public function testEnumField(): void
    {
        $data = ['suit' => 'H', 'value' => 10];
        self::assertSame($data, $this->roundTrip(CardInput::class, $data));
    }

    public function testDateTimeField(): void
    {
        $data = ['name' => 'Conference', 'date' => '2024-01-15T10:30:00+00:00'];
        self::assertSame($data, $this->roundTrip(EventInput::class, $data));
    }

    public function testGenericClassWithInts(): void
    {
        /** @var CallbackInputMapper<int> $intInputMapper */
        $intInputMapper = new CallbackInputMapper(static fn (mixed $data): mixed => $data);
        /** @var CallbackOutputMapper<mixed> $intOutputMapper */
        $intOutputMapper = new CallbackOutputMapper(static fn (mixed $data): mixed => $data);

        $inputMapper = $this->inputMapperProvider->get(CollectionInput::class, [$intInputMapper]);
        $outputMapper = $this->outputMapperProvider->get(CollectionInput::class, [$intOutputMapper]);

        $data = ['items' => [1, 2, 3], 'size' => 3];
        $object = $inputMapper->map($data);
        $result = $outputMapper->map($object);
        self::assertSame($data, $result);
    }

    public function testSourceKeyMapping(): void
    {
        $data = ['id' => 1, 'full_name' => 'John'];
        self::assertSame($data, $this->roundTrip(PersonWithSourceKeyInput::class, $data));
    }

    public function testCombinationOfAllTypes(): void
    {
        $data = [
            'id' => 1,
            'title' => 'Complex Movie',
            'description' => 'A movie with all types',
            'year' => 2024,
            'genres' => ['Action', 'Drama', 'Comedy'],
            'director' => ['id' => 10, 'name' => 'Director', 'age' => 45],
            'actors' => [
                ['id' => 11, 'name' => 'Actor One', 'age' => 30],
                ['id' => 12, 'name' => 'Actor Two'],
            ],
        ];
        self::assertSame($data, $this->roundTrip(MovieInput::class, $data));
    }

    public function testEmptyObject(): void
    {
        $data = [];
        self::assertSame($data, $this->roundTrip(EmptyInput::class, $data));
    }

    public function testAllOptionalFieldsAbsent(): void
    {
        $data = [];
        self::assertSame($data, $this->roundTrip(AllOptionalInput::class, $data));
    }

    public function testAllOptionalFieldsPresent(): void
    {
        $data = ['a' => 1, 'b' => 'hello'];
        self::assertSame($data, $this->roundTrip(AllOptionalInput::class, $data));
    }

    public function testAllOptionalFieldsMixed(): void
    {
        $data = ['a' => 1];
        self::assertSame($data, $this->roundTrip(AllOptionalInput::class, $data));
    }

    public function testDeeplyNestedObjects(): void
    {
        // Optional fields (age) placed after non-optional fields in nested objects
        $data = [
            'id' => 1,
            'title' => 'Nested Test',
            'year' => 2024,
            'genres' => ['Test'],
            'director' => ['id' => 1, 'name' => 'D', 'age' => 50],
            'actors' => [
                ['id' => 2, 'name' => 'A1', 'age' => 25],
                ['id' => 3, 'name' => 'A2', 'age' => 35],
                ['id' => 4, 'name' => 'A3'],
            ],
        ];
        self::assertSame($data, $this->roundTrip(MovieInput::class, $data));
    }

    public function testDiscriminatedObjectChildOne(): void
    {
        $inputData = [
            'id' => 1,
            'name' => 'Alice',
            'type' => 'childOne',
            'childOneField' => 'extra',
            'age' => 30,
        ];
        // Child properties appear before inherited properties in output
        $expectedOutput = [
            'childOneField' => 'extra',
            'id' => 1,
            'name' => 'Alice',
            'age' => 30,
            'type' => 'childOne',
        ];
        self::assertSame($expectedOutput, $this->roundTrip(HierarchicalParentInput::class, $inputData));
    }

    public function testDiscriminatedObjectChildTwoWithOptionalAbsent(): void
    {
        $inputData = [
            'id' => 2,
            'name' => 'Bob',
            'type' => 'childTwo',
            'childTwoField' => 42,
        ];
        // Child properties appear before inherited properties in output
        $expectedOutput = [
            'childTwoField' => 42,
            'id' => 2,
            'name' => 'Bob',
            'type' => 'childTwo',
        ];
        self::assertSame($expectedOutput, $this->roundTrip(HierarchicalParentInput::class, $inputData));
    }

    /**
     * @param class-string<T> $className
     * @param array<string, mixed> $data
     *
     * @template T of object
     */
    private function roundTrip(
        string $className,
        array $data,
    ): mixed
    {
        $object = $this->inputMapperProvider->get($className)->map($data);
        return $this->outputMapperProvider->get($className)->map($object);
    }

}
