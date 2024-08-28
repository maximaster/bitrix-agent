# maximaster/bitrix-agent

Удобный интерфейс для работы с агентами.

```php
composer require maximaster/bitrix-agent
```

## CRUD пример

```php
use Maximaster\BitrixAgent\Agent\Agent;
use Maximaster\BitrixAgent\AgentRepository\AgentTable;
use Maximaster\BitrixAgent\AgentRepository\BitrixAgentRepository;
use Maximaster\BitrixValueObjects\Main\ModuleId;

$agent = Agent::flexible(
    'hello_world();',
    ModuleId::main(),
    new DateTimeImmutable(),
    new DateInterval('P1D')
);

$agentRepo = new BitrixAgentRepository();
$agentRepo->save($agent);

$agent = $agentRepo->allFit([AgentTable::NAME => 'hello_world();'])->get(0);

$agent->scheduleAt(new DateTimeImmutable('+1 day'));
$agentRepo->save($agent);

$agentRepo->remove($agent);
```

## Можно помечать агенты тегами

```php
use Maximaster\BitrixAgent\Agent\Agent;
use Maximaster\BitrixAgent\AgentRepository\BitrixAgentRepository;

$agent = Agent::flexible(
    'hello_world();',
    ModuleId::main(),
    new DateTimeImmutable(),
    new DateInterval('P1D')
);
$agent->tag('service');

$agentRepo = new BitrixAgentRepository();
$agentRepo->save($agent);

$agents = $agentRepo->allTagged('service');
```
