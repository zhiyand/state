<?php

use OffbeatEngineer\State\Machine;
use OffbeatEngineer\State\Policy;
use PHPUnit\Framework\TestCase;


class DoorPolicy extends Policy {
    public function lock($from, $to, $time)
    {
        return $time > 10;
    }
}

class DoorPolicyDenyAll extends Policy {

    protected $denial = true;

    public function lock($from, $to, $time)
    {
        return $time > 10;
    }
}

class MachineTest extends TestCase {

    protected $machine;

    /** @test */
    public function it_can_be_initialized()
    {
        $machine = new Machine;

        $this->assertInstanceOf(Machine::class, $machine);

        $this->assertEquals([], $machine->states());
        $this->assertEquals([], $machine->transitions());
    }

    /** @test */
    public function it_loads_machine_configuration_from_string()
    {
        $machine = $this->makeMachine();

        $states = $machine->states();
        $transitions = $machine->transitions();

        $this->assertCount(4, $states);
        $this->assertCount(3, $transitions);

        $this->assertEquals(['draft', 'pending', 'published', 'archived'], $states);
        $this->assertEquals([
            'draft' => ['pend' => 'pending'],
            'pending' => ['publish' => 'published'],
            'published' => ['archive' => 'archived'],
        ], $transitions);

        $this->assertEquals('draft', $machine->state());
    }

    /** @test */
    public function it_loads_machine_configuration_from_file()
    {
        $machine = Machine::fromFile(__DIR__ . '/../fixtures/article.spec');

        $states = $machine->states();
        $transitions = $machine->transitions();

        $this->assertCount(4, $states);
        $this->assertCount(3, $transitions);

        $this->assertEquals(['draft', 'pending', 'published', 'archived'], $states);
        $this->assertEquals([
            'draft' => ['pend' => 'pending'],
            'pending' => ['publish' => 'published'],
            'published' => ['archive' => 'archived'],
        ], $transitions);

        $this->assertEquals('draft', $machine->state());
    }


    /** @test */
    public function it_can_be_set_to_a_specific_state()
    {
        $machine = new Machine("states:A, B, C
            - x: A > B
            - y: B > C", 'B');

        $this->assertEquals('B', $machine->state());

        $machine->teleport('C');

        $this->assertEquals('C', $machine->state());
    }

    /** @test */
    public function it_processes_valid_transitions()
    {
        $machine = $this->makeMachine();

        $machine->pend();
        $this->assertEquals('pending', $machine->state());

        $machine->publish();
        $this->assertEquals('published', $machine->state());

        $machine->archive();
        $this->assertEquals('archived', $machine->state());
    }

    /**
     * @test
     */
    public function it_complains_about_invalid_transitions()
    {
        $this->expectException(\OffbeatEngineer\State\MachineException::class);

        $machine = $this->makeMachine();

        $machine->publish();
    }

    /** @test */
    public function it_allows_manual_configuration_through_machine_methods()
    {
        $machine = new Machine;

        /*
        states: A, B, C
        - ab: A > B
        - bc: B > C
        - ac: A > C

        A -> B
          \  |
             C
         */

        $machine->addState('A');
        $machine->addState('B');
        $machine->addState('C');

        $machine->addTransition('ab', 'A', 'B');
        $machine->addTransition('ac', 'A', 'C');
        $machine->addTransition('bc', 'B', 'C');

        $this->assertEquals(['A', 'B', 'C'], $machine->states());
        $this->assertEquals([
            'A' => ['ab' => 'B', 'ac' => 'C'],
            'B' => ['bc' => 'C']
        ], $machine->transitions());
    }

    /** @test */
    public function it_adds_states_automatically_when_adding_transitions()
    {
        $machine = new Machine;

        $machine->addTransition('ab', 'A', 'B');

        $this->assertEquals(['A', 'B'], $machine->states());
    }

    /**
     * @test
     */
    public function it_complains_when_an_uninitialized_machine_is_used()
    {
        $this->expectException(\OffbeatEngineer\State\MachineException::class);

        $machine = new Machine;

        $machine->process('dummy');
    }

    /** @test */
    public function it_triggers_transition_listeners()
    {
        $machine = $this->makeMachine();

        $callback = $this->getMockBuilder(stdClass::class)
                         ->addMethods(array('handle'))
                         ->getMock();
        $callback->expects($this->once())
            ->method('handle')
            ->with('draft', 'pending', 'zhiyan', 'admin');

        $machine->on('pend', [$callback, 'handle']);

        $machine->pend('zhiyan', 'admin');
    }

    /** @test */
    public function it_removes_transition_listeners_properly()
    {
        $machine = $this->makeMachine();

        $callback = $this->getMockBuilder(stdClass::class)
                         ->addMethods(array('handle'))
                         ->getMock();
        $callback->expects($this->exactly(0))
            ->method('handle')
            ->with('draft', 'pending');

        $machine->on('pend', [$callback, 'handle']);
        $machine->off('pend', [$callback, 'handle']);

        $machine->pend();
    }

    /** @test */
    public function it_triggers_global_listeners_properly()
    {
        $machine = $this->makeMachine();

        $callback = $this->getMockBuilder(stdClass::class)
                         ->addMethods(array('handle'))
                         ->getMock();
        $callback->expects($this->exactly(2))
            ->method('handle');

        $machine->on([$callback, 'handle']);

        $machine->pend();
        $machine->publish();

        $machine->off([$callback, 'handle']);

        $callback = $this->getMockBuilder(stdClass::class)
                         ->addMethods(array('handle'))
                         ->getMock();
        $callback->expects($this->exactly(1))
            ->method('handle')
            ->with('archive', 'published', 'archived', ['zhiyan']);

        $machine->on([$callback, 'handle']);

        $machine->archive('zhiyan');
    }

    /** @test */
    public function it_follows_a_given_allow_all_policy()
    {
        $door = new Machine("states: unlocked, locked
            - unlock: locked   > unlocked
            - lock:   unlocked > locked", 'unlocked', new DoorPolicy);

        // Only values greater than 10 is allowed by policy
        $this->assertFalse($door->lock(1));
        $this->assertTrue($door->lock(11));

        // No policy on 'unlock', anything would work.
        $this->assertTrue($door->unlock(0));
    }

    /** @test */
    public function it_follows_a_given_denial_policy()
    {
        $door = new Machine("states: unlocked, locked
            - unlock: locked   > unlocked
            - lock:   unlocked > locked", 'unlocked', new DoorPolicyDenyAll);

        $this->assertTrue($door->lock(110));

        // No policy on 'unlock', gets denied
        $this->assertFalse($door->unlock(0));
    }

    /** @test */
    public function it_supports_walkers_for_simplified_transition_monitoring()
    {
        $machine = $this->makeMachine();

        $walker = $this->getMockBuilder(stdClass::class)
                         ->addMethods(array('pend', 'publish', 'archive'))
                         ->getMock();

        $walker->expects($this->once())
            ->method('pend')
            ->with('draft', 'pending', 'John');
        $walker->expects($this->exactly(1))
            ->method('publish')
            ->with('pending', 'published', 'Jenny');
        $walker->expects($this->exactly(1))
            ->method('archive')
            ->with('published', 'archived', 'Alice');

        $machine->attach($walker);

        $machine->pend('John');
        $machine->publish('Jenny');
        $machine->archive('Alice');

        $machine->detach($walker);

        $genericWalker = $this->getMockBuilder(stdClass::class)
                         ->addMethods(array('_catchall_'))
                         ->getMock();
        $genericWalker->expects($this->exactly(1))
            ->method('_catchall_')->with('pend', 'draft', 'pending', ['John']);

        $machine->attach($genericWalker);

        $machine->teleport('draft');

        $machine->pend('John');
    }

    protected function makeMachine()
    {
        return new Machine("states: draft, pending, published, archived
                            - pend: draft > pending
                            - publish: pending > published
                            - archive: published > archived");
    }
}
