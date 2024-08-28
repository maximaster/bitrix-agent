<?php

declare(strict_types=1);

namespace Maximaster\BitrixAgent\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Maximaster\BitrixAgent\Contract\Agent;
use Webmozart\Assert\Assert;

/**
 * Коллекция агентов.
 *
 * @extends ArrayCollection<int, Agent>
 *
 * @method Agent[] getIterator()
 */
class AgentCollection extends ArrayCollection
{
    public function __construct(array $elements = [])
    {
        Assert::allIsAOf($elements, Agent::class);

        parent::__construct($elements);
    }

    /**
     * @psalm-return list<positive-int>
     */
    public function ids(): array
    {
        $ids = [];
        foreach ($this as $agent) {
            $ids[] = $agent->id();
        }

        return array_filter($ids);
    }
}
