<?php

/**
 * Idempotent demo dataset for local styling review (forms, lists, ITIL, KB, assets).
 *
 * All generated records use the name prefix "BK-DEMO" so they can be purged safely.
 *
 * ---------------------------------------------------------------------
 *
 * Copyright (C) 2026 Distillect
 * GPL-3.0-or-later
 *
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Brandkit;

use Change;
use ChangeTask;
use Computer;
use Contact;
use Contract;
use Entity;
use ITILCategory;
use KnowbaseItem;
use Location;
use Monitor;
use NetworkEquipment;
use Phone;
use Printer;
use Problem;
use ProblemTask;
use Project;
use Session;
use Software;
use Supplier;
use Planning;
use Ticket;
use TicketTask;
use User;

final class DemoDataSeeder
{
    public const MARKER = 'brandkit_demo_v1';
    public const PREFIX = 'BK-DEMO';

    /** @var array<string, int> */
    private array $ids = [];

    public function __construct(
        private readonly bool $force = false,
        private readonly bool $purge = false,
    ) {
    }

    /**
     * @return array{created: int, skipped: bool, lines: list<string>}
     */
    public function run(): array
    {
        $lines = [];
        $created = 0;

        if ($this->purge) {
            $lines = array_merge($lines, $this->purgeAll());
            return ['created' => 0, 'skipped' => false, 'lines' => $lines];
        }

        $requester_id = (int) (Session::getLoginUserID() ?: 2);

        if (!$this->force && $this->alreadySeeded()) {
            $plan_lines = $this->seedPlannedTasks($requester_id);
            $lines = ['Demo data already present (computers prefixed ' . self::PREFIX . '). Use --force or --purge.'];
            $lines = array_merge($lines, $plan_lines);

            return [
                'created' => count($plan_lines) > 0 ? 1 : 0,
                'skipped' => true,
                'lines' => $lines,
            ];
        }

        $entity_id = $this->ensureEntity();
        $lines[] = 'Entity: ' . self::PREFIX . ' Site (id ' . $entity_id . ')';
        ++$created;

        $location_id = $this->ensureItemByName(Location::class, [
            'name'        => self::PREFIX . ' HQ',
            'entities_id' => $entity_id,
            'comment'     => self::MARKER,
        ]);
        $lines[] = 'Location ' . $location_id;

        $computers = [];
        foreach (
            [
                'LAP-01 Finance',
                'LAP-02 Support',
                'WS-03 Design',
                'SRV-04 App',
                'SRV-05 DB',
                'VM-06 Staging',
            ] as $suffix
        ) {
            $computers[] = $this->ensureItemByName(Computer::class, [
                'name'         => self::PREFIX . ' ' . $suffix,
                'entities_id'  => $entity_id,
                'locations_id' => $location_id,
                'comment'      => self::MARKER,
                'serial'       => 'SN-' . str_replace([' ', '-'], '', $suffix),
            ]);
        }
        $lines[] = count($computers) . ' computers';
        $created += count($computers);

        foreach (['Monitor 27"', 'Monitor ultrawide'] as $suffix) {
            $this->ensureItemByName(Monitor::class, [
                'name'         => self::PREFIX . ' ' . $suffix,
                'entities_id'  => $entity_id,
                'locations_id' => $location_id,
                'comment'      => self::MARKER,
            ]);
            ++$created;
        }

        foreach (['MFC Floor 2', 'Label printer IT'] as $suffix) {
            $this->ensureItemByName(Printer::class, [
                'name'         => self::PREFIX . ' ' . $suffix,
                'entities_id'  => $entity_id,
                'locations_id' => $location_id,
                'comment'      => self::MARKER,
            ]);
            ++$created;
        }

        foreach (['Desk phone', 'Mobile handset'] as $suffix) {
            $this->ensureItemByName(Phone::class, [
                'name'         => self::PREFIX . ' ' . $suffix,
                'entities_id'  => $entity_id,
                'locations_id' => $location_id,
                'comment'      => self::MARKER,
            ]);
            ++$created;
        }

        $this->ensureItemByName(NetworkEquipment::class, [
            'name'         => self::PREFIX . ' Core switch',
            'entities_id'  => $entity_id,
            'locations_id' => $location_id,
            'comment'      => self::MARKER,
        ]);
        ++$created;

        $software_id = $this->ensureItemByName(Software::class, [
            'name'        => self::PREFIX . ' Analytics',
            'entities_id' => $entity_id,
            'comment'     => self::MARKER,
        ]);
        $lines[] = 'Software id ' . $software_id;
        ++$created;

        $supplier_id = $this->ensureItemByName(Supplier::class, [
            'name'        => self::PREFIX . ' Supplies Ltd',
            'entities_id' => $entity_id,
            'comment'     => self::MARKER,
        ]);
        $this->ensureItemByName(Contact::class, [
            'name'         => self::PREFIX . ' Alex Vendor',
            'entities_id'  => $entity_id,
            'suppliers_id' => $supplier_id,
            'comment'      => self::MARKER,
        ]);
        $created += 2;

        $this->ensureItemByName(Contract::class, [
            'name'        => self::PREFIX . ' Support contract',
            'entities_id' => $entity_id,
            'comment'     => self::MARKER,
            'num'         => 'CNT-2026-001',
        ]);
        ++$created;

        $project_id = $this->ensureItemByName(Project::class, [
            'name'        => self::PREFIX . ' Workspace refresh',
            'entities_id' => $entity_id,
            'comment'     => self::MARKER,
            'content'     => 'Roll out new laptops and docking stations.',
        ]);
        $lines[] = 'Project id ' . $project_id;
        ++$created;

        $category_id = $this->defaultItilCategoryId();

        $ticket_specs = [
            ['[BK-DEMO] VPN drops on Wi‑Fi', Ticket::INCOMING, 'Users lose VPN several times per day.'],
            ['[BK-DEMO] Printer jam — floor 2', Ticket::ASSIGNED, 'MFC shows paper path error 03.'],
            ['[BK-DEMO] New laptop request', Ticket::PLANNED, 'Onboarding for finance team member.'],
            ['[BK-DEMO] Software licence renewal', Ticket::WAITING, 'Waiting on vendor quote.'],
            ['[BK-DEMO] Shared mailbox access', Ticket::SOLVED, 'Granted delegate rights.'],
            ['[BK-DEMO] Monitor flicker', Ticket::CLOSED, 'Replaced DisplayPort cable.'],
            ['[BK-DEMO] Slow SAP login', Ticket::INCOMING, 'High load between 08:00–09:00.'],
            ['[BK-DEMO] MFA reset', Ticket::ASSIGNED, 'User lost phone; needs backup codes.'],
        ];

        foreach ($ticket_specs as [$title, $status, $content]) {
            $this->ensureItemByName(Ticket::class, [
                'name'               => $title,
                'content'            => $content,
                'entities_id'        => $entity_id,
                'status'             => $status,
                'itilcategories_id'  => $category_id,
                'users_id_recipient' => $requester_id,
                '_users_id_requester' => $requester_id,
                '_skip_rules'        => true,
                'comment'            => self::MARKER,
            ]);
            ++$created;
        }
        $lines[] = count($ticket_specs) . ' tickets';

        foreach (
            [
                ['[BK-DEMO] Email delivery delays', 'Outbound mail queue backing up.'],
                ['[BK-DEMO] Wi‑Fi coverage warehouse', 'Dead zones near loading bay.'],
            ] as [$title, $content]
        ) {
            $this->ensureItemByName(Problem::class, [
                'name'                => $title,
                'content'             => $content,
                'entities_id'         => $entity_id,
                'itilcategories_id'   => $category_id,
                '_users_id_requester' => $requester_id,
                '_skip_rules'         => true,
                'comment'             => self::MARKER,
            ]);
            ++$created;
        }

        foreach (
            [
                ['[BK-DEMO] Patch Tuesday window', 'Deploy security updates to servers.'],
                ['[BK-DEMO] Firewall rule change', 'Allow new SaaS integration.'],
            ] as [$title, $content]
        ) {
            $this->ensureItemByName(Change::class, [
                'name'                => $title,
                'content'             => $content,
                'entities_id'         => $entity_id,
                'itilcategories_id'   => $category_id,
                '_users_id_requester' => $requester_id,
                '_skip_rules'         => true,
                'comment'             => self::MARKER,
            ]);
            ++$created;
        }

        $kb_topics = [
            ['Reset corporate password', '<p>Use the self-service portal, then wait 15 minutes for sync.</p>'],
            ['Connect to VPN', '<p>Install the client, import the profile, use MFA.</p>'],
            ['Request new hardware', '<p>Submit a ticket with cost centre and manager approval.</p>'],
            ['Clean cards UI checklist', '<p>Review tickets, assets, KB, and entity forms after applying BrandKit.</p>'],
        ];
        foreach ($kb_topics as [$title, $answer]) {
            $this->ensureItemByName(KnowbaseItem::class, [
                'name'   => self::PREFIX . ' — ' . $title,
                'answer' => $answer,
                'is_faq' => 1,
            ]);
            ++$created;
        }
        $lines[] = count($kb_topics) . ' knowledge base articles';

        $lines = array_merge($lines, $this->seedPlannedTasks($requester_id));

        return ['created' => $created, 'skipped' => false, 'lines' => $lines];
    }

    /**
     * Planned ITIL tasks so user tabs show the Planification column (begin/end set).
     *
     * @return list<string>
     */
    private function seedPlannedTasks(int $tech_user_id): array
    {
        global $DB;

        $lines = [];
        $slots = [
            [Ticket::class, Ticket::getTable(), 'tickets_id', TicketTask::class, 4],
            [Problem::class, Problem::getTable(), 'problems_id', ProblemTask::class, 2],
            [Change::class, Change::getTable(), 'changes_id', ChangeTask::class, 2],
        ];

        $day_offset = 0;
        foreach ($slots as [$parentClass, $parentTable, $fk, $taskClass, $limit]) {
            /** @var class-string<\CommonDBTM> $parentClass */
            /** @var class-string<\CommonDBTM> $taskClass */
            $taskTable = $taskClass::getTable();
            $iterator = $DB->request([
                'FROM'  => $parentTable,
                'WHERE' => ['name' => ['LIKE', '[BK-DEMO]%']],
                'ORDER' => 'id ASC',
                'LIMIT' => $limit,
            ]);

            $added = 0;
            foreach ($iterator as $row) {
                $parent_id = (int) $row['id'];
                $planned = $DB->request([
                    'FROM'  => $taskTable,
                    'WHERE' => [
                        $fk           => $parent_id,
                        'NOT'         => ['begin' => null],
                    ],
                    'LIMIT' => 1,
                ]);
                if (count($planned) > 0) {
                    continue;
                }

                $begin = date('Y-m-d 09:00:00', strtotime('+' . (1 + $day_offset) . ' days'));
                ++$day_offset;

                $this->addItem($taskClass, [
                    $fk               => $parent_id,
                    'content'         => '[BK-DEMO] Scheduled work window (' . self::MARKER . ')',
                    'users_id'        => $tech_user_id,
                    'users_id_tech'   => $tech_user_id,
                    'state'           => Planning::TODO,
                    'plan'            => [
                        'begin'     => $begin,
                        '_duration' => 2 * HOUR_TIMESTAMP,
                    ],
                ]);
                ++$added;
            }

            if ($added > 0) {
                $lines[] = $added . ' planned ' . $taskClass . ' (Planification column)';
            }
        }

        if ($lines === []) {
            $lines[] = 'Planned tasks: already present on BK-DEMO ITIL items (or no BK-DEMO tickets found).';
        }

        return $lines;
    }

    private function ensureEntity(): int
    {
        $name = self::PREFIX . ' Site';
        $entity = new Entity();
        if ($entity->getFromDBbyCrit(['name' => $name, 'entities_id' => 0])) {
            return (int) $entity->getID();
        }

        return $this->addItem(Entity::class, [
            'name'        => $name,
            'entities_id' => 0,
            'comment'     => self::MARKER,
        ]);
    }

    private function alreadySeeded(): bool
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => Computer::getTable(),
            'WHERE' => ['comment' => self::MARKER],
            'LIMIT' => 1,
        ]);

        return count($iterator) > 0;
    }

    private function defaultItilCategoryId(): int
    {
        $cat = new ITILCategory();
        if ($cat->getFromDBbyCrit(['name' => 'General'])) {
            return (int) $cat->getID();
        }
        if ($cat->getFromDBbyCrit(['id' => 1])) {
            return 1;
        }

        return 0;
    }

    /**
     * Re-use an existing record (same name, and entity when applicable) or create it.
     *
     * @param class-string<\CommonDBTM> $class
     * @param array<string, mixed> $input
     */
    private function ensureItemByName(string $class, array $input): int
    {
        $item = new $class();
        $nameField = $item::getNameField();
        $name = (string) ($input[$nameField] ?? '');
        if ($name !== '') {
            $crit = [$nameField => $name];
            $match_name_globally = in_array($class, [Ticket::class, Problem::class, Change::class, KnowbaseItem::class], true);
            if (
                !$match_name_globally
                && isset($input['entities_id'])
                && $item->isField('entities_id')
            ) {
                $crit['entities_id'] = (int) $input['entities_id'];
            }
            if ($item->getFromDBbyCrit($crit)) {
                return (int) $item->getID();
            }
        }

        return $this->addItem($class, $input);
    }

    /**
     * @param class-string<\CommonDBTM> $class
     * @param array<string, mixed> $input
     */
    private function addItem(string $class, array $input): int
    {
        $item = new $class();
        $id = $item->add($input);
        if (!$id) {
            throw new \RuntimeException('Failed to create ' . $class . ': ' . json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? []));
        }

        return (int) $id;
    }

    /**
     * @return list<string>
     */
    private function purgeAll(): array
    {
        $lines = [];
        $types = [
            Ticket::class,
            Problem::class,
            Change::class,
            KnowbaseItem::class,
            Project::class,
            Contract::class,
            Computer::class,
            Monitor::class,
            Printer::class,
            Phone::class,
            NetworkEquipment::class,
            Software::class,
            Contact::class,
            Supplier::class,
            Location::class,
        ];

        foreach ($types as $type) {
            $item = new $type();
            $table = $item::getTable();
            $namefield = $item::getNameField();
            global $DB;
            $iterator = $DB->request([
                'FROM'  => $table,
                'WHERE' => [
                    'OR' => [
                        [$namefield => ['LIKE', self::PREFIX . '%']],
                        ['name' => ['LIKE', '[BK-DEMO]%']],
                        ['comment' => self::MARKER],
                    ],
                ],
            ]);
            $count = 0;
            foreach ($iterator as $row) {
                $obj = new $type();
                if ($obj->delete(['id' => $row['id']], true)) {
                    ++$count;
                }
            }
            if ($count > 0) {
                $lines[] = 'Purged ' . $count . ' ' . $type;
            }
        }

        $entity = new Entity();
        if ($entity->getFromDBbyCrit(['name' => self::PREFIX . ' Site'])) {
            $entity->delete(['id' => $entity->getID()], true);
            $lines[] = 'Purged demo entity';
        }

        if ($lines === []) {
            $lines[] = 'Nothing to purge.';
        }

        return $lines;
    }
}
