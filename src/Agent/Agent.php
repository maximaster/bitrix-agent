<?php

declare(strict_types=1);

namespace Maximaster\BitrixAgent\Agent;

use DateInterval;
use DateTimeImmutable;
use Maximaster\BitrixAgent\Contract\Agent as AgentContract;
use Maximaster\BitrixAgent\Contract\ScheduleType;
use Maximaster\BitrixAgent\Exception\Exception;
use Maximaster\BitrixValueObjects\Main\ModuleId;
use Webmozart\Assert\Assert;

/**
 * Агент.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods) why:dependency
 */
class Agent implements AgentContract
{
    private const DEFAULT_SORT = 500;

    /** @psalm-var positive-int|null */
    private ?int $id;
    private string $procedure;
    private ModuleId $module;
    private ScheduleType $scheduleType;
    private DateTimeImmutable $scheduledAt;
    private DateInterval $interval;
    private bool $active;
    private int $sort;
    private ?DateTimeImmutable $executedAt;
    private ?DateTimeImmutable $retryAt;
    private bool $running;
    /** @psalm-var list<non-empty-string> */
    private array $tags;

    /**
     * Восстановить ранее созданный, но сохранённый вовне объект. Позволяет
     * указать все параметры, но id обязательно должен быть заполнен.
     *
     * @psalm-param positive-int $id
     * @psalm-param non-empty-string $procedure
     * @psalm-param list<non-empty-string> $tags
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) why:dependency
     */
    public static function wakeUp(
        int $id,
        string $procedure,
        ModuleId $module,
        ScheduleType $scheduleType,
        DateTimeImmutable $scheduledAt,
        DateInterval $interval,
        bool $active,
        int $sort,
        ?DateTimeImmutable $executedAt,
        ?DateTimeImmutable $retryAt,
        bool $running,
        array $tags
    ): self {
        return new self(
            $id,
            $procedure,
            $module,
            $scheduleType,
            $scheduledAt,
            $interval,
            $active,
            $sort,
            $executedAt,
            $retryAt,
            $running,
            $tags
        );
    }

    /**
     * Создать однократно выполняющийся агент.
     *
     * @psalm-param non-empty-string $procedure
     */
    public static function flexible(
        string $procedure,
        ModuleId $module,
        DateTimeImmutable $scheduledAt,
        DateInterval $interval,
        int $sort = self::DEFAULT_SORT
    ): self {
        return new self(
            null,
            $procedure,
            $module,
            ScheduleType::FLEXIBLE(),
            $scheduledAt,
            $interval,
            true,
            $sort,
            null,
            null,
            false,
            []
        );
    }

    /**
     * @psalm-param positive-int|null $id
     * @psalm-param non-empty-string $procedure
     * @psalm-param list<non-empty-string> $tags
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) why:dependency
     */
    private function __construct(
        ?int $id,
        string $procedure,
        ModuleId $module,
        ScheduleType $scheduleType,
        DateTimeImmutable $scheduledAt,
        DateInterval $interval,
        bool $active,
        int $sort,
        ?DateTimeImmutable $executedAt,
        ?DateTimeImmutable $retryAt,
        bool $running,
        array $tags
    ) {
        Assert::nullOrPositiveInteger($id, 'Идентификатор агента должен быть положительным числом или не задан.');
        Assert::stringNotEmpty($procedure, 'Процедура агента не должна быть пустой строкой.');
        Assert::allStringNotEmpty($tags, 'Все теги должны быть не пустыми строками.');

        $this->id = $id;
        $this->procedure = $procedure;
        $this->module = $module;
        $this->scheduleType = $scheduleType;
        $this->scheduledAt = $scheduledAt;
        $this->interval = $interval;
        $this->active = $active;
        $this->sort = $sort;
        $this->executedAt = $executedAt;
        $this->retryAt = $retryAt;
        $this->running = $running;
        $this->tags = $tags;
    }

    /**
     * Синхронизирует внутренние данные с другим объектом того же id.
     */
    public function syncFrom(AgentContract $agent): void
    {
        Assert::eq($agent->id(), $this->id, 'Ожидались одинаковые id агентов для синхронизации.');

        $this->id = $agent->id();
        $this->procedure = $agent->procedure();
        $this->module = $agent->module();
        $this->scheduleType = $agent->scheduleType();
        $this->scheduledAt = $agent->scheduledAt();
        $this->interval = $agent->interval();
        $this->active = $agent->active();
        $this->sort = $agent->sort();
        $this->executedAt = $agent->executedAt();
        $this->retryAt = $agent->retryAt();
        $this->running = $agent->running();
        $this->tags = $agent->tags();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function procedure(): string
    {
        return $this->procedure;
    }

    public function module(): ModuleId
    {
        return $this->module;
    }

    public function sort(): int
    {
        return $this->sort;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function executedAt(): ?DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function scheduledAt(): DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function retryAt(): ?DateTimeImmutable
    {
        return $this->retryAt;
    }

    public function interval(): DateInterval
    {
        return $this->interval;
    }

    public function secondsInterval(): int
    {
        return $this->interval->s + $this->interval->i * 60 + $this->interval->h * 3600
        + $this->interval->d * 86400 + $this->interval->m * 2592000 + $this->interval->y * 31536000;
    }

    public function scheduleType(): ScheduleType
    {
        return $this->scheduleType;
    }

    public function running(): bool
    {
        return $this->running;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function tag(string $tag): void
    {
        $this->tags[] = $tag;
    }

    public function scheduleAt(DateTimeImmutable $nextRecurrence): void
    {
        $this->scheduledAt = $nextRecurrence;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function persistAs(int $id): void
    {
        Assert::positiveInteger($id);

        if ($this->id !== null) {
            throw new Exception(sprintf('Агент уже сохранён под id [%d].', $id));
        }

        $this->id = $id;
    }
}
