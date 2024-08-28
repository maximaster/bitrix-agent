<?php

declare(strict_types=1);

namespace Maximaster\BitrixAgent\AgentRepository;

use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Exception;
use Maximaster\BitrixEnums\Main\Truth;
use Webmozart\Assert\Assert;

class AgentTable extends DataManager
{
    public const ID = 'ID';
    public const MODULE_ID = 'MODULE_ID';
    public const SORT = 'SORT';
    public const NAME = 'NAME';
    public const ACTIVE = 'ACTIVE';
    public const LAST_EXEC = 'LAST_EXEC';
    public const NEXT_EXEC = 'NEXT_EXEC';
    public const DATE_CHECK = 'DATE_CHECK';
    public const AGENT_INTERVAL = 'AGENT_INTERVAL';
    public const IS_PERIOD = 'IS_PERIOD';
    public const USER_ID = 'USER_ID';
    public const RUNNING = 'RUNNING';

    public static function getTableName()
    {
        return 'b_agent';
    }

    /**
     * @return Field[]
     *
     * @throws SystemException
     *
     * @psalm-return array<string|int, Field|array<non-empty-string, mixed>>
     */
    public static function getMap(): array
    {
        $stringBoolean = ['values' => ['N', 'Y'], 'default_value' => 'Y'];

        return [
            'ID' => new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            'MODULE_ID' => new StringField('MODULE_ID'),
            'SORT' => new IntegerField('SORT'),
            'NAME' => new StringField('NAME'),
            'ACTIVE' => new BooleanField('ACTIVE', $stringBoolean),
            'LAST_EXEC' => new DatetimeField('LAST_EXEC'),
            'NEXT_EXEC' => new DatetimeField('NEXT_EXEC'),
            'DATE_CHECK' => new DatetimeField('DATE_CHECK'),
            'AGENT_INTERVAL' => new IntegerField('AGENT_INTERVAL'),
            'IS_PERIOD' => new BooleanField('IS_PERIOD', $stringBoolean),
            'USER_ID' => new IntegerField('USER_ID'),
            'RUNNING' => new BooleanField('RUNNING', $stringBoolean),
        ];
    }

    /**
     * @throws Exception
     *
     * @psalm-param array<mixed> $row
     * @psalm-return array{
     *     ID: positive-int,
     *     MODULE_ID: non-empty-string,
     *     SORT: int,
     *     NAME: non-empty-string,
     *     ACTIVE: 'Y'|'N',
     *     LAST_EXEC: DateTime|null,
     *     NEXT_EXEC: DateTime,
     *     DATE_CHECK: DateTime|null,
     *     AGENT_INTERVAL: int,
     *     IS_PERIOD: 'Y'|'N',
     *     USER_ID: int|null,
     *     RUNNING: 'Y'|'N'
     * }
     */
    public static function normalize(array $row): array
    {
        $normalized = [];

        $normalizers = self::normalizers();
        foreach ($normalizers as $key => $normalizer) {
            if (array_key_exists($key, $row) === false) {
                throw new Exception(sprintf('Запись не содержит ожидаемое поле [%s].', $key));
            }

            $normalized[$key] = $normalizer($row[$key]);
        }

        // @phpstan-ignore-next-line why:correct
        return $normalized;
    }

    /**
     * @psalm-return array<non-empty-string, callable(mixed):mixed>
     */
    private static function normalizers(): array
    {
        return [
            self::ID => static function ($value) {
                Assert::numeric($value);
                $value = (int) $value;
                Assert::positiveInteger($value);

                return $value;
            },
            self::MODULE_ID => static function ($value) {
                Assert::stringNotEmpty($value);

                return $value;
            },
            self::SORT => static function ($value) {
                Assert::numeric($value);

                return (int) $value;
            },
            self::NAME => static function ($value) {
                Assert::stringNotEmpty($value);

                return $value;
            },
            self::ACTIVE => static function ($value) {
                Assert::inArray($value, Truth::toArray());

                return $value;
            },
            self::LAST_EXEC => static function ($value) {
                Assert::nullOrIsAOf($value, DateTime::class);

                return $value;
            },
            self::NEXT_EXEC => static function ($value) {
                Assert::isAOf($value, DateTime::class);

                return $value;
            },
            self::DATE_CHECK => static function ($value) {
                Assert::nullOrIsAOf($value, DateTime::class);

                return $value;
            },
            self::AGENT_INTERVAL => static function ($value) {
                Assert::numeric($value);

                return (int) $value;
            },
            self::IS_PERIOD => static function ($value) {
                Assert::inArray($value, Truth::toArray());

                return $value;
            },
            self::USER_ID => static function ($value) {
                Assert::nullOrNumeric($value);
                $value = $value === null ? null : (int) $value;
                Assert::nullOrPositiveInteger($value);

                return $value;
            },
            self::RUNNING => static function ($value) {
                Assert::inArray($value, Truth::toArray());

                return $value;
            },
        ];
    }
}
