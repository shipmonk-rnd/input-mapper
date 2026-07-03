<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use LogicException;
use function str_starts_with;

class CodecRegistry
{

    /**
     * @var array<class-string<Codec<*, *, *, *>>, callable(class-string, self): Codec<*, *, *, *>>
     */
    private array $codecFactories = [];

    /**
     * @var array<string, Codec<*, *, *, *>>
     */
    private array $codecs = [];

    /**
     * @param Codec<*, *, *, *> $codec
     */
    public function register(
        Codec $codec,
    ): void
    {
        $this->registerFactory($codec::class, static fn (): Codec => $codec);
    }

    /**
     * @param class-string<T> $codecClassName
     * @param callable(class-string, self): T $factory
     *
     * @template T of Codec<*, *, *, *>
     */
    public function registerFactory(
        string $codecClassName,
        callable $factory,
    ): void
    {
        foreach ($this->codecs as $key => $codec) {
            if (str_starts_with($key, $codecClassName . ':')) {
                unset($this->codecs[$key]);
            }
        }

        $this->codecFactories[$codecClassName] = $factory;
    }

    /**
     * @return array<class-string<Codec<*, *, *, *>>, callable(class-string, self): Codec<*, *, *, *>>
     */
    public function getFactories(): array
    {
        return $this->codecFactories;
    }

    /**
     * @param class-string<T> $codecClassName
     * @param class-string $domainClassName
     * @return T
     *
     * @template T of Codec<*, *, *, *>
     */
    public function get(
        string $codecClassName,
        string $domainClassName,
    ): Codec
    {
        $key = $codecClassName . ':' . $domainClassName;

        return $this->codecs[$key] // @phpstan-ignore return.type
            ??= $this->createFromFactory($codecClassName, $domainClassName);
    }

    /**
     * @param class-string<Codec<*, *, *, *>> $codecClassName
     * @param class-string $domainClassName
     * @return Codec<*, *, *, *>
     */
    private function createFromFactory(
        string $codecClassName,
        string $domainClassName,
    ): Codec
    {
        $factory = $this->codecFactories[$codecClassName]
            ?? throw new LogicException("No codec factory registered for '{$codecClassName}'.");

        return $factory($domainClassName, $this);
    }

}
