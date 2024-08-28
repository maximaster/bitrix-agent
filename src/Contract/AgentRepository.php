<?php

declare(strict_types=1);

namespace Maximaster\BitrixAgent\Contract;

use Maximaster\BitrixAgent\Collection\AgentCollection;

/**
 * Репозиторий агентов.
 */
interface AgentRepository
{
    /**
     * @psalm-param array<non-empty-string, mixed> $filter
     * @psalm-param array<non-empty-string, 'ASC'|'DESC'> $order
     */
    public function allFit(array $filter, array $order = []): AgentCollection;

    /**
     * Возвращает все агенты с определённым тегом.
     *
     * @psalm-param non-empty-string $tag
     */
    public function allTagged(string $tag): AgentCollection;

    /**
     * Удаляет из репозитория указанный агент.
     */
    public function remove(Agent $agent): void;

    /**
     * Сохранить агент.
     */
    public function save(Agent $agent): void;
}
