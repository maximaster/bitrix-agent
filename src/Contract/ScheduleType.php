<?php

declare(strict_types=1);

namespace Maximaster\BitrixAgent\Contract;

use Maximaster\BitrixEnums\Main\Truth;
use MyCLabs\Enum\Enum;

/**
 * Тип расписания.
 *
 * @extends Enum<string>
 * @psalm-immutable
 *
 * @method static self FIXED()
 * @method static self FLEXIBLE()
 */
class ScheduleType extends Enum
{
    /**
     * Фиксированное расписание. Количество запусков будет фиксированным и
     * зависеть исключительно от интервала. Следующее время выполнения
     * определяется по запланированной последней дате выполнения + интервал.
     *
     * Периодические агенты.
     */
    public const FIXED = 'fixed';

    /**
     * Гибкое расписание. Следующее время выполнения определяется по дате
     * завершения выполненя агента + интервал.
     *
     * Непериодические агенты.
     */
    public const FLEXIBLE = 'flexible';

    public static function fromPeriodic(Truth $periodic): self
    {
        return $periodic->equals(Truth::YES())
            ? self::FIXED()
            : self::FLEXIBLE();
    }

    public function toPeriodic(): Truth
    {
        return $this->equals(self::FIXED()) ? Truth::YES() : Truth::NO();
    }
}
