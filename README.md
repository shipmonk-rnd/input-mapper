# ShipMonk Input Mapper

High performance (compile-based) input mapper for PHP with support for generics, array shapes and nullable types.

## Installation:

```sh
composer require shipmonk/input-mapper
```

## Usage:

### Write Input Class

```php
use ShipMonk\InputMapper\Runtime\Optional;

class Person
{
    /**
     * @param Optional<string>     $email
     * @param list<string>         $hobbies
     * @param Optional<list<self>> $friends
     */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly Optional $email,
        public readonly array $hobbies,
        public readonly Optional $friends,
    ) {}
}
```

### Map Input

```php
$tempDir = sys_get_temp_dir() . '/input-mapper';
$autoRefresh = true; // set to false in production
$mapperProvider = new ShipMonk\InputMapper\Runtime\MapperProvider($tempDir, $autoRefresh);
$mapper = $mapperProvider->get(Person::class);

$person = $mapper->map([
    'name' => 'John',
    'age' => 30,
    'hobbies' => ['hiking', 'reading'],
    'friends' => [
        [
            'name' => 'Jane',
            'age' => 28,
            'hobbies' => ['hiking', 'reading'],
        ],
        [
            'name' => 'Jack',
            'age' => 28,
            'hobbies' => ['hiking', 'reading'],
        ],
    ],
]);
```


## Contributing
- Check your code by `composer check`
- Autofix coding-style by `composer fix:cs`
- All functionality must be tested
