<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

class MapperContext
{

    public function __construct(
        public readonly ?MapperContext $parent,
        public readonly string|int $key,
    )
    {
    }

    public static function append(
        ?MapperContext $parent,
        string|int $key,
    ): self
    {
        return new self($parent, $key);
    }

    /**
     * @param list<string|int> $path
     */
    public static function fromPath(array $path): ?self
    {
        $context = null;

        foreach ($path as $key) {
            $context = new self($context, $key);
        }

        return $context;
    }

    /**
     * @return list<string|int>
     */
    public function getPath(): array
    {
        return $this->parent === null
            ? [$this->key]
            : [...$this->parent->getPath(), $this->key];
    }

}
