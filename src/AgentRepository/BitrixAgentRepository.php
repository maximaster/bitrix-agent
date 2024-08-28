<?php

declare(strict_types=1);

namespace Maximaster\BitrixAgent\AgentRepository;

use Bitrix\Main\Type\DateTime as BitrixDateTime;
use CAgent;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Maximaster\BitrixAgent\Agent\Agent;
use Maximaster\BitrixAgent\Collection\AgentCollection;
use Maximaster\BitrixAgent\Contract\Agent as AgentContract;
use Maximaster\BitrixAgent\Contract\AgentRepository;
use Maximaster\BitrixAgent\Contract\ScheduleType;
use Maximaster\BitrixAgent\Exception\Exception as LibException;
use Maximaster\BitrixEnums\Main\DateTimeFormat;
use Maximaster\BitrixEnums\Main\Truth;
use Maximaster\BitrixValueObjects\Main\ModuleId;
use Webmozart\Assert\Assert;

/**
 * Репозиторий агентов на базе API-методов Битрикс.
 */
class BitrixAgentRepository implements AgentRepository
{
    public const TAG_PREFIX = '//@';
    /**
     * @psalm-var ArrayCollection<int, AgentContract>
     */
    private ArrayCollection $managed;

    public function __construct()
    {
        $this->managed = new ArrayCollection();
    }

    /**
     * @throws Exception
     */
    public function allFit(array $filter, array $order = []): AgentCollection
    {
        $agents = [];

        $res = AgentTable::getList(compact('filter', 'order'));
        while ($rawAgent = $res->fetch()) {
            $agent = $this->buildObject($rawAgent);

            $earlierManaged = $this->managed
                ->filter(static fn (AgentContract $managedAgent) => $managedAgent->id() === $agent->id());

            $earlierManaged->map(static function (AgentContract $alreadyManaged) use ($agent) {
                $alreadyManaged->syncFrom($agent);

                return $alreadyManaged;
            });

            if ($earlierManaged->count() === 0) {
                $this->managed->add($agent);
            }

            $agents[] = $agent;
        }

        return new AgentCollection($agents);
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function allTagged(string $tag): AgentCollection
    {
        Assert::stringNotEmpty($tag);

        return $this->allFit(['%' . AgentTable::NAME => self::TAG_PREFIX . $tag]);
    }

    /**
     * @throws LibException
     */
    public function remove(AgentContract $agent): void
    {
        $agentId = $agent->id();
        Assert::positiveInteger($agentId, 'Невозможно удалить ещё не сохранённый агент.');

        if (CAgent::Delete($agentId) === false) {
            throw new LibException('Не удалось удалить агент  по неизвестной причине.');
        }

        $this->managed->removeElement($agent);
    }

    /**
     * {@inheritDoc}
     *
     * @throws LibException
     */
    public function save(AgentContract $agent): void
    {
        switch ($agent->id()) {
            case null:
                $this->insertAgent($agent);
                break;
            default:
                $this->updateAgent($agent);
        }

        if ($this->managed->contains($agent) === false) {
            $this->managed->add($agent);
        }
    }

    /**
     * @throws Exception
     *
     * @psalm-param array<mixed> $rawAgent
     */
    private function buildObject(array $rawAgent): Agent
    {
        $row = AgentTable::normalize($rawAgent);

        [$procedure, $tags] = $this->parseProcedureTags($row[AgentTable::NAME]);

        return Agent::wakeUp(
            $row[AgentTable::ID],
            $procedure,
            new ModuleId($row[AgentTable::MODULE_ID]),
            ScheduleType::fromPeriodic(Truth::from($row[AgentTable::IS_PERIOD])),
            self::buildDateTimeImmutable($row[AgentTable::NEXT_EXEC]),
            new DateInterval(sprintf('PT%dS', $row[AgentTable::AGENT_INTERVAL])),
            Truth::from($row[AgentTable::ACTIVE])->toNative(),
            $row[AgentTable::SORT],
            self::buildNullDateTimeImmutable($row[AgentTable::LAST_EXEC]),
            self::buildNullDateTimeImmutable($row[AgentTable::DATE_CHECK]),
            Truth::from($row[AgentTable::RUNNING])->toNative(),
            $tags
        );
    }

    /**
     * Разбирает имя агента на процедуру и теги.
     *
     * @psalm-param non-empty-string $name
     * @psalm-return array{non-empty-string, list<non-empty-string>}
     */
    private function parseProcedureTags(string $name): array
    {
        [$procedure, $rawTags] = explode(PHP_EOL, $name, 2);

        return [$procedure, $this->parseTags($rawTags)];
    }

    /**
     * @psalm-return array{
     *     ID: positive-int|null,
     *     MODULE_ID: non-empty-string,
     *     SORT: int,
     *     NAME: non-empty-string,
     *     ACTIVE: 'Y'|'N',
     *     LAST_EXEC: non-empty-string|null,
     *     NEXT_EXEC: non-empty-string,
     *     DATE_CHECK: non-empty-string|null,
     *     AGENT_INTERVAL: int,
     *     IS_PERIOD: 'Y'|'N',
     *     USER_ID: int|null,
     *     RUNNING: 'Y'|'N'
     * }
     */
    private function buildRow(AgentContract $agent): array
    {
        return [
            AgentTable::ID => $agent->id(),
            AgentTable::MODULE_ID => strval($agent->module()),
            AgentTable::SORT => $agent->sort(),
            // phpcs:ignore Generic.Files.LineLength.TooLong
            AgentTable::NAME => $agent->procedure() . PHP_EOL . implode(PHP_EOL, preg_replace('/^/', '//@', $agent->tags()) ?? []),
            AgentTable::ACTIVE => Truth::fromBoolean($agent->active())->getValue(),
            AgentTable::LAST_EXEC => $this->persistableTime($agent->executedAt()),
            AgentTable::NEXT_EXEC => $this->persistableTime($agent->scheduledAt()),
            AgentTable::DATE_CHECK => $this->persistableTime($agent->retryAt()),
            AgentTable::AGENT_INTERVAL => $agent->secondsInterval(),
            AgentTable::IS_PERIOD => $agent->scheduleType()->toPeriodic()->getValue(),
            AgentTable::USER_ID => null, // TODO
            AgentTable::RUNNING => Truth::fromBoolean($agent->running())->getValue(),
        ];
    }

    /**
     * @psalm-return ($time is DateTimeImmutable ? non-empty-string : null)
     */
    private function persistableTime(?DateTimeImmutable $time): ?string
    {
        if ($time === null) {
            return null;
        }

        // @phpstan-ignore-next-line why:dependency:mistyping
        return FormatDate(DateTimeFormat::FULL, $time->getTimestamp());
    }

    private static function buildDateTimeImmutable(BitrixDateTime $bitrixTime): DateTimeImmutable
    {
        return (new DateTimeImmutable())->setTimestamp($bitrixTime->getTimestamp());
    }

    private static function buildNullDateTimeImmutable(?BitrixDateTime $bitrixTime): ?DateTimeImmutable
    {
        if ($bitrixTime === null) {
            return null;
        }

        return (new DateTimeImmutable())->setTimestamp($bitrixTime->getTimestamp());
    }

    /**
     * @psalm-return list<non-empty-string>
     */
    private function parseTags(string $name): array
    {
        $tags = [];
        foreach (explode(PHP_EOL, $name) as $line) {
            if (str_starts_with($line, self::TAG_PREFIX) === false) {
                continue;
            }

            $tags[] = str_replace(self::TAG_PREFIX, '', $line);
        }

        return $tags;
    }

    /**
     * @throws LibException
     */
    private function insertAgent(AgentContract $agent): void
    {
        $row = $this->buildRow($agent);
        unset($row[AgentTable::ID]);

        $added = CAgent::Add($row);
        if (is_int($added) === false || $added < 1) {
            throw new LibException('Не удалось добавить агент.');
        }

        $agent->persistAs($added);
    }

    /**
     * @throws LibException
     */
    private function updateAgent(AgentContract $agent): void
    {
        $agentId = $agent->id();
        Assert::positiveInteger($agentId, 'Ожидался агент с указанным идентификатором для обновления.');
        $row = $this->buildRow($agent);
        unset($row[AgentTable::ID]);

        $updated = CAgent::Update($agentId, $row);
        if ($updated === false) {
            throw new LibException('Не удалось обновить агент.');
        }
    }
}
