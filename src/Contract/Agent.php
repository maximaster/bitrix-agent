<?php

declare(strict_types=1);

namespace Maximaster\BitrixAgent\Contract;

use DateInterval;
use DateTimeImmutable;
use Maximaster\BitrixAgent\Exception\Exception;
use Maximaster\BitrixValueObjects\Main\ModuleId;

/**
 * Агент.
 */
interface Agent
{
    /**
     * Идентификатор.
     *
     * @psalm-return positive-int|null
     */
    public function id(): ?int;

    /**
     * Процедура на выполнение.
     */
    public function procedure(): string;

    /**
     * Модуль.
     */
    public function module(): ModuleId;

    /**
     * Порядок сортировки.
     */
    public function sort(): int;

    /**
     * Признак активности.
     */
    public function active(): bool;

    /**
     * Метка времени последнего выполнения.
     */
    public function executedAt(): ?DateTimeImmutable;

    /**
     * Метка времени запланированного выполнения.
     */
    public function scheduledAt(): DateTimeImmutable;

    /**
     * Метка времени повтора исполнения.
     *
     * Если агент отмечен как исполяемый (RUNNING), но пришло время указанное
     * в данном свойстве, значит выполнение агента было неудачным (зависло или
     * упало) и нужно повторить запуск.
     */
    public function retryAt(): ?DateTimeImmutable;

    /**
     * Интервал между запусками.
     */
    public function interval(): DateInterval;

    /**
     * Интервал между запусками в секундах.
     */
    public function secondsInterval(): int;

    /**
     * Тип расписания.
     */
    public function scheduleType(): ScheduleType;

    /**
     * Запущено ли выполнение.
     */
    public function running(): bool;

    /**
     * Теги агента.
     *
     * @psalm-return list<non-empty-string>
     */
    public function tags(): array;

    /**
     * Добавить тег агенту.
     *
     * @psalm-param non-empty-string $tag
     */
    public function tag(string $tag): void;

    /**
     * Запланировать выполнение на другое время.
     */
    public function scheduleAt(DateTimeImmutable $nextRecurrence): void;

    /**
     * Сохранить агент с указанным id.
     *
     * @psalm-param positive-int $id
     *
     * @throws Exception
     */
    public function persistAs(int $id): void;

    /**
     * Синхронизирует внутренние данные с другим объектом того же id.
     */
    public function syncFrom(Agent $agent): void;
}
