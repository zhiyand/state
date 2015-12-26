<?php

use Zhibaihe\State\Machine;
use Zhibaihe\State\Policy;

class DoorPolicy extends Policy {
    public function lock($from, $to, $time)
    {
        return $time > 10;
    }
}

class MachineTest extends PHPUnit_Framework_TestCase {

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
     * @expectedException \Zhibaihe\State\MachineException
     */
    public function it_complains_about_invalid_transitions()
    {
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
     * @expectedException \Zhibaihe\State\MachineException
     */
    public function it_complains_when_an_uninitialized_machine_is_used()
    {
        $machine = new Machine;

        $machine->process('dummy');
    }

    /** @test */
    public function it_triggers_transition_listeners()
    {
        $machine = $this->makeMachine();

        $callback = $this->getMock('stdClass', array('handle'));
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

        $callback = $this->getMock('stdClass', array('handle'));
        $callback->expects($this->exactly(0))
            ->method('handle')
            ->with('draft', 'pending', ['zhiyan']);

        $machine->on('pend', [$callback, 'handle']);
        $machine->off('pend', [$callback, 'handle']);

        $machine->pend();
    }

    /** @test */
    public function it_follows_a_given_policy()
    {
        $door = new Machine("states: unlocked, locked
            - unlock: locked   > unlocked
            - lock:   unlocked > locked", new DoorPolicy);

        // Only values greater than 10 is allowed by policy
        $this->assertFalse($door->lock(1));
        $this->assertTrue($door->lock(11));

        // No policy on 'unlock', anything would work.
        $this->assertTrue($door->unlock(0));
    }

    protected function makeMachine()
    {
        return new Machine("states: draft, pending, published, archived
                            - pend: draft > pending
                            - publish: pending > published
                            - archive: published > archived");
    }
}