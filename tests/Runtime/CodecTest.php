<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use DateTimeImmutable;
use LogicException;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactory;
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactoryProvider;
use ShipMonk\InputMapper\Runtime\CodecRegistry;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\AccountId;
use ShipMonk\InputMapperTests\Runtime\Data\AlternativeMoneyCodec;
use ShipMonk\InputMapperTests\Runtime\Data\CodecConflictProbeInput;
use ShipMonk\InputMapperTests\Runtime\Data\Duration;
use ShipMonk\InputMapperTests\Runtime\Data\DurationCodec;
use ShipMonk\InputMapperTests\Runtime\Data\EmptyInput;
use ShipMonk\InputMapperTests\Runtime\Data\FactoryProbeInput;
use ShipMonk\InputMapperTests\Runtime\Data\FixedDuration;
use ShipMonk\InputMapperTests\Runtime\Data\HexColor;
use ShipMonk\InputMapperTests\Runtime\Data\InheritedHexColorCodec;
use ShipMonk\InputMapperTests\Runtime\Data\LegacyPaymentInput;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec;
use ShipMonk\InputMapperTests\Runtime\Data\MoneyValue;
use ShipMonk\InputMapperTests\Runtime\Data\OrderId;
use ShipMonk\InputMapperTests\Runtime\Data\OrderInput;
use ShipMonk\InputMapperTests\Runtime\Data\PrefixedSuffixedInput;
use ShipMonk\InputMapperTests\Runtime\Data\ReversedTokenInput;
use ShipMonk\InputMapperTests\Runtime\Data\SpecialRequirementsDatesInput;
use ShipMonk\InputMapperTests\Runtime\Data\TypedIdCodec;
use ShipMonk\InputMapperTests\Runtime\Data\UnboundGenericCodec;
use function sys_get_temp_dir;

class CodecTest extends InputMapperTestCase
{

    private MapperProvider $mapperProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $this->mapperProvider->registerCodec(new MoneyCodec());
        $this->mapperProvider->registerCodec(new DurationCodec());
    }

    public function testCodecInputMapping(): void
    {
        $mapper = $this->mapperProvider->getInputMapper(MoneyValue::class);
        /** @var MoneyValue $result */
        $result = $mapper->map(['currency' => 'USD', 'cents' => 1_299]);

        self::assertSame('USD', $result->currency);
        self::assertSame(1_299, $result->cents);
    }

    public function testCodecOutputMapping(): void
    {
        $mapper = $this->mapperProvider->getOutputMapper(MoneyValue::class);
        $result = $mapper->map(new MoneyValue('EUR', 500));

        self::assertSame(['currency' => 'EUR', 'cents' => 500], $result);
    }

    public function testCodecRoundTrip(): void
    {
        $data = ['currency' => 'USD', 'cents' => 1_299];
        $inputMapper = $this->mapperProvider->getInputMapper(MoneyValue::class);
        $outputMapper = $this->mapperProvider->getOutputMapper(MoneyValue::class);

        $object = $inputMapper->map($data);
        $result = $outputMapper->map($object);

        self::assertSame($data, $result);
    }

    public function testCodecInputMappingWithInvalidIntermediateType(): void
    {
        $mapper = $this->mapperProvider->getInputMapper(MoneyValue::class);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected array, got "not an array"',
            static fn () => $mapper->map('not an array'),
        );
    }

    public function testCodecInputMappingWithMissingField(): void
    {
        $mapper = $this->mapperProvider->getInputMapper(MoneyValue::class);

        self::assertException(
            MappingFailedException::class,
            null,
            static fn () => $mapper->map(['currency' => 'USD']),
        );
    }

    public function testCodecDecodeValidation(): void
    {
        $mapper = $this->mapperProvider->getInputMapper(MoneyValue::class);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /cents: Expected non-negative integer, got -100',
            static fn () => $mapper->map(['currency' => 'USD', 'cents' => -100]),
        );
    }

    public function testCodecAsFieldInObject(): void
    {
        $inputMapper = $this->mapperProvider->getInputMapper(OrderInput::class);
        $outputMapper = $this->mapperProvider->getOutputMapper(OrderInput::class);

        $data = ['id' => 42, 'total' => ['currency' => 'USD', 'cents' => 1_299]];
        /** @var OrderInput $order */
        $order = $inputMapper->map($data);

        self::assertSame(42, $order->id);
        self::assertSame('USD', $order->total->currency);
        self::assertSame(1_299, $order->total->cents);

        $result = $outputMapper->map($order);
        self::assertSame($data, $result);
    }

    public function testCodecRegistryGetUnregistered(): void
    {
        $registry = new CodecRegistry();

        self::assertException(
            LogicException::class,
            "No codec factory registered for 'ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec'.",
            static fn () => $registry->get(MoneyCodec::class, MoneyValue::class),
        );
    }

    public function testCodecFactoryInputMapping(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodecFactory(TypedIdCodec::class, TypedIdCodec::create(...));

        $result = $mapperProvider->getInputMapper(AccountId::class)->map('abc-123');
        self::assertSame('abc-123', $result->value);
    }

    public function testCodecFactoryOutputMapping(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodecFactory(TypedIdCodec::class, TypedIdCodec::create(...));

        $result = $mapperProvider->getOutputMapper(AccountId::class)->map(new AccountId('abc-123'));
        self::assertSame('abc-123', $result);
    }

    public function testCodecFactoryDifferentSubclasses(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodecFactory(TypedIdCodec::class, TypedIdCodec::create(...));

        $accountId = $mapperProvider->getInputMapper(AccountId::class)->map('acc-1');
        self::assertSame('acc-1', $accountId->value);

        $orderId = $mapperProvider->getInputMapper(OrderId::class)->map('ord-2');
        self::assertSame('ord-2', $orderId->value);
    }

    public function testCodecFactoryRoundTrip(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodecFactory(TypedIdCodec::class, TypedIdCodec::create(...));

        $inputMapper = $mapperProvider->getInputMapper(AccountId::class);
        $outputMapper = $mapperProvider->getOutputMapper(AccountId::class);

        $object = $inputMapper->map('abc-123');
        $result = $outputMapper->map($object);

        self::assertSame('abc-123', $result);
    }

    public function testAsymmetricCodecInputMapping(): void
    {
        $mapper = $this->mapperProvider->getInputMapper(FixedDuration::class);
        $result = $mapper->map(90);

        self::assertSame(90, $result->toSeconds());
    }

    public function testAsymmetricCodecOutputMapping(): void
    {
        $mapper = $this->mapperProvider->getOutputMapper(Duration::class);
        $result = $mapper->map(new FixedDuration(90));

        self::assertSame(90, $result);
    }

    public function testConflictingCodecRegistrationsThrow(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodec(new MoneyCodec());
        $mapperProvider->registerCodec(new AlternativeMoneyCodec());

        self::assertException(
            LogicException::class,
            "Multiple codecs registered for domain class 'ShipMonk\InputMapperTests\Runtime\Data\MoneyValue': ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec, ShipMonk\InputMapperTests\Runtime\Data\AlternativeMoneyCodec.",
            static fn () => $mapperProvider->getInputMapper(CodecConflictProbeInput::class),
        );
    }

    public function testInlineCodecAttribute(): void
    {
        $inputMapper = $this->mapperProvider->getInputMapper(SpecialRequirementsDatesInput::class);

        /** @var SpecialRequirementsDatesInput $result */
        $result = $inputMapper->map([
            'deadline' => '2024-02-29',
            'earliest' => '2024-01-15T10:30:00+00:00',
        ]);

        self::assertSame('2024-02-29', $result->deadline->format('Y-m-d'));
        self::assertNotNull($result->earliest);
        self::assertSame('2024-01-15T10:30:00+00:00', $result->earliest->format('Y-m-d\TH:i:sP'));
    }

    public function testInlineCodecAttributeWithNullAndAbsentValues(): void
    {
        $inputMapper = $this->mapperProvider->getInputMapper(SpecialRequirementsDatesInput::class);

        /** @var SpecialRequirementsDatesInput $resultWithNull */
        $resultWithNull = $inputMapper->map(['deadline' => '2024-02-29', 'earliest' => null]);
        self::assertNull($resultWithNull->earliest);

        /** @var SpecialRequirementsDatesInput $resultWithAbsent */
        $resultWithAbsent = $inputMapper->map(['deadline' => '2024-02-29']);
        self::assertNull($resultWithAbsent->earliest);
    }

    public function testInlineCodecAttributeWithInvalidValue(): void
    {
        $inputMapper = $this->mapperProvider->getInputMapper(SpecialRequirementsDatesInput::class);

        self::assertException(
            MappingFailedException::class,
            "Failed to map data at path /deadline: Expected date-time string in format 'Y-m-d', got \"whatever\"",
            static fn () => $inputMapper->map(['deadline' => 'whatever']),
        );
    }

    public function testInlineCodecAttributeOutputMapping(): void
    {
        $outputMapper = $this->mapperProvider->getOutputMapper(SpecialRequirementsDatesInput::class);

        $result = $outputMapper->map(new SpecialRequirementsDatesInput(
            deadline: new DateTimeImmutable('2024-02-29'),
            earliest: new DateTimeImmutable('2024-01-15T10:30:00+00:00'),
        ));

        self::assertSame(['deadline' => '2024-02-29', 'earliest' => '2024-01-15T10:30:00+00:00'], $result);

        $resultWithNull = $outputMapper->map(new SpecialRequirementsDatesInput(deadline: new DateTimeImmutable('2024-02-29')));
        self::assertSame(['deadline' => '2024-02-29', 'earliest' => null], $resultWithNull);
    }

    public function testMapCodecWrapperAttribute(): void
    {
        $inputMapper = $this->mapperProvider->getInputMapper(LegacyPaymentInput::class);
        $outputMapper = $this->mapperProvider->getOutputMapper(LegacyPaymentInput::class);

        $data = ['amount' => 'USD:1299', 'refund' => 'EUR-500'];
        /** @var LegacyPaymentInput $result */
        $result = $inputMapper->map($data);

        self::assertSame('1299', $result->amount);
        self::assertSame('500', $result->refund);
        self::assertSame($data, $outputMapper->map($result));

        $dataWithNull = ['amount' => 'USD:1299', 'refund' => null];
        /** @var LegacyPaymentInput $resultWithNull */
        $resultWithNull = $inputMapper->map($dataWithNull);

        self::assertNull($resultWithNull->refund);
        self::assertSame($dataWithNull, $outputMapper->map($resultWithNull));
    }

    public function testCodecRegisteredAfterUnrelatedCompileIsPickedUp(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->getInputMapper(EmptyInput::class);

        $mapperProvider->registerCodecFactory(TypedIdCodec::class, TypedIdCodec::create(...));

        $result = $mapperProvider->getInputMapper(AccountId::class)->map('abc-123');
        self::assertSame('abc-123', $result->value);
    }

    public function testConflictingCodecRegistrationsThrowDeterministically(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodec(new MoneyCodec());
        $mapperProvider->registerCodec(new AlternativeMoneyCodec());

        $expectedMessage = "Multiple codecs registered for domain class 'ShipMonk\InputMapperTests\Runtime\Data\MoneyValue': ShipMonk\InputMapperTests\Runtime\Data\MoneyCodec, ShipMonk\InputMapperTests\Runtime\Data\AlternativeMoneyCodec.";

        self::assertException(
            LogicException::class,
            $expectedMessage,
            static fn () => $mapperProvider->getInputMapper(CodecConflictProbeInput::class),
        );

        // retry after a caught conflict must fail the same way, not silently succeed with a partial codec map
        self::assertException(
            LogicException::class,
            $expectedMessage,
            static fn () => $mapperProvider->getOutputMapper(CodecConflictProbeInput::class),
        );
    }

    public function testCodecWithoutInferableDomainClassIsRejected(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodec(new UnboundGenericCodec());

        self::assertException(
            LogicException::class,
            "Codec 'ShipMonk\InputMapperTests\Runtime\Data\UnboundGenericCodec' does not declare any class or interface as its domain type, check its @implements annotation.",
            static fn () => $mapperProvider->getInputMapper(CodecConflictProbeInput::class),
        );
    }

    public function testCodecInheritingImplementsFromParentClass(): void
    {
        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true);
        $mapperProvider->registerCodec(new InheritedHexColorCodec());

        $inputMapper = $mapperProvider->getInputMapper(HexColor::class);
        $outputMapper = $mapperProvider->getOutputMapper(HexColor::class);

        self::assertSame('#ff0000', $outputMapper->map($inputMapper->map('#ff0000')));
    }

    public function testTwoCodecAttributesOnPromotedParameterRoundTrip(): void
    {
        $inputMapper = $this->mapperProvider->getInputMapper(PrefixedSuffixedInput::class);
        $outputMapper = $this->mapperProvider->getOutputMapper(PrefixedSuffixedInput::class);

        $data = ['amount' => 'USD:100!'];
        /** @var PrefixedSuffixedInput $result */
        $result = $inputMapper->map($data);

        self::assertSame('100', $result->amount);
        self::assertSame($data, $outputMapper->map($result));
    }

    public function testInputOnlyProviderCodecAttributeIsNotDropped(): void
    {
        $inputMapper = $this->mapperProvider->getInputMapper(ReversedTokenInput::class);
        $outputMapper = $this->mapperProvider->getOutputMapper(ReversedTokenInput::class);

        /** @var ReversedTokenInput $result */
        $result = $inputMapper->map(['token' => 'abc']);

        self::assertSame('cba', $result->token);
        self::assertSame(['token' => 'abc'], $outputMapper->map($result));
    }

    public function testFactoryConfiguredBeforeMapperProviderIsPreserved(): void
    {
        $factoryProvider = new DefaultMapperCompilerFactoryProvider();
        $factory = $factoryProvider->get();
        self::assertInstanceOf(DefaultMapperCompilerFactory::class, $factory);
        $factory->setMapperCompilerFactory(FactoryProbeInput::class, static fn () => throw new LogicException('custom factory used'));

        $mapperProvider = new MapperProvider(sys_get_temp_dir(), autoRefresh: true, mapperCompilerFactoryProvider: $factoryProvider);
        $mapperProvider->registerCodec(new MoneyCodec());

        self::assertException(
            LogicException::class,
            'custom factory used',
            static fn () => $mapperProvider->getInputMapper(FactoryProbeInput::class),
        );
    }

    public function testCodecRegistryReRegistrationReplacesCachedInstances(): void
    {
        $registry = new CodecRegistry();
        $registry->register(new MoneyCodec());
        $first = $registry->get(MoneyCodec::class, MoneyValue::class);

        $replacement = new MoneyCodec();
        $registry->register($replacement);

        self::assertSame($replacement, $registry->get(MoneyCodec::class, MoneyValue::class));
        self::assertNotSame($first, $replacement);
    }

}
