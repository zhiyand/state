<?php

namespace OffbeatEngineer\State;

class Machine {

    /**
     * Current state of the machine.
     * @var boolean
     */
    protected $state = false;

    /**
     * All possible states of the machine.
     * @var array
     */
    protected $states = [];

    /**
     * All transitions of the machine.
     * @var array
     */
    protected $transitions = [];

    /**
     * Transition listeners.
     * @var array
     */
    protected $listeners = [];

    /**
     * The policy governing this machine.
     * @var Policy
     */
    protected $policy = null;

    /**
     * Key of global listeners in the listeners array.
     * @var string
     */
    protected $globalListenerKey = '__ALL__';

    /**
     * State machine walkers
     * @var array
     */
    protected $walkers = [];

    public function __construct($configuration = '', $start = '', Policy $policy = null)
    {
        $this->loadFromString($configuration);

        $this->state = current($this->states);
        $this->state = $start ? $this->teleport($start) : $this->state;
        $this->policy = $policy ?: new Policy;
    }

    public static function fromFile($path, $start = '', Policy $policy = null)
    {
        $configuration = file_get_contents($path);

        return new self($configuration, $start, $policy);
    }

    /**
     * Get current state of the machine.
     * @return string The current state
     */
    public function state()
    {
        return $this->state;
    }

    /**
     * Get all states of the machine.
     * @return array array of states.
     */
    public function states()
    {
        return $this->states;
    }

    /**
     * Get all possible transitions of the machine.
     * @return array array of transitions.
     */
    public function transitions()
    {
        return $this->transitions;
    }

    /**
     * Set the current state to `$state` by force.
     * No transition listeners are called.
     *
     * @param  string $state
     * @return string current state after teleport
     */
    public function teleport($state)
    {
        if ( ! $this->ready()) {
            throw new MachineException("Machine not initialized.");
        }

        if ( ! $this->has($state)) {
            throw new MachineException("State '$state' is invalid.");
        }

        return $this->state = $state;
    }

    /**
     * Returns true if the machine is initialized.
     * @return boolean
     */
    public function ready()
    {
        return false !== $this->state;
    }

    /**
     * Check if the machine has the state `$state`.
     * @param  string $state
     * @return boolean
     */
    public function has($state)
    {
        return in_array($state, $this->states);
    }

    /**
     * Process a transition.
     * `\OffbeatEngineer\State\MachineException` is thrown when:
     * 1. the machine is not initialized;
     * 2. no transition is defined at current state;
     * 3. the transition is illegal at current state.
     *
     * @param  string $transition  The transition to be processed
     * @param  array  $arguments   The arguments for the transition
     * @return void
     *
     * @throws \OffbeatEngineer\State\MachineException
     */
    public function process($transition, $arguments = [])
    {
        if ( ! $this->ready()) {
            throw new MachineException("Machine not initialized.");
        }

        if ( ! array_key_exists($this->state, $this->transitions)) {
            throw new MachineException("No transitions defined for state '{$this->state}'.");
        }

        $transitions = $this->transitions[$this->state];

        if ( ! array_key_exists($transition, $transitions)) {
            throw new MachineException("Transition '$transition' at state '{$this->state}' is invalid.");
        }

        $to = $transitions[$transition];

        if ($this->policy->denies($transition, $this->state, $to, $arguments)) {
            return false;
        }

        $this->triggerWalkers($transition, $this->state, $to, $arguments);

        $this->triggerListeners($transition, $this->state, $to, $arguments);

        $this->state = $to;

        return true;
    }

    /**
     * Add a state to the machine.
     *
     * @param string $label State label
     */
    public function addState($label)
    {
        if ( ! in_array($label, $this->states)) {
            $this->states[] = $label;
        }

        if (false === $this->state) {
            $this->state = current($this->states);
        }
    }

    /**
     * Add a transition to the machine.
     * States not present in the machine previously will be added.
     * This allows for more consice code.
     *
     * @param string $transition Transition label
     * @param string $from       From state
     * @param string $to         To state
     */
    public function addTransition($transition, $from, $to)
    {
        if ( ! $this->hasState($from)) {
            $this->addState($from);
        }

        if ( ! $this->hasState($to)) {
            $this->addState($to);
        }

        if ( ! array_key_exists($from, $this->transitions)) {
            $this->transitions[$from] = [];
        }

        $this->transitions[$from][$transition] = $to;
    }

    /**
     * Check if the machine has a given state
     *
     * @param  string  $label State label to be checked.
     * @return boolean        true if the machine has the given state, false otherwise.
     */
    public function hasState($label)
    {
        return in_array($label, $this->states);
    }

    /**
     * Dynamically invoke the `process()` method to process a transition.
     * Allows more friendly transition API.
     *
     * @param  string $name      The transition name
     * @param  array  $arguments The arguments to the transition
     * @return [type]            [description]
     */
    public function __call($name, $arguments)
    {
        return $this->process($name, $arguments);
    }

    /**
     * Register a transition listener for `$transition`.
     *
     * @param  string   $transition
     * @param  callable $callback
     * @return void
     */
    public function on($transition, $callback = null)
    {
        if (is_callable($transition)) {
            $callback = $transition;
            $transition = $this->globalListenerKey;
        }

        if ( ! array_key_exists($transition, $this->listeners)) {
            $this->listeners[$transition] = [];
        }

        if ( ! in_array($callback, $this->listeners[$transition])) {
            $this->listeners[$transition][] = $callback;
        }
    }

    /**
     * Remove a transition listener for `$transition`.
     *
     * @param  string   $transition
     * @param  callable $callback
     * @return void
     */
    public function off($transition, $callback = null)
    {
        if (is_callable($transition)) {
            $callback = $transition;
            $transition = $this->globalListenerKey;
        }

        if ( ! array_key_exists($transition, $this->listeners)) {
            return;
        }

        if ( ! in_array($callback, $this->listeners[$transition])) {
            return;
        }

        $this->listeners[$transition] = array_filter($this->listeners[$transition], function($item) use ($callback){
            return $callback != $item;
        });
    }

    /**
     * Attach a walker
     * @param  object $walker A walker object
     * @return void
     */
    public function attach($walker)
    {
        if ( ! in_array($walker, $this->walkers)) {
            $this->walkers[] = $walker;
        }
    }

    /**
     * Detach a walker
     * @param  object $walker A walker object
     * @return void
     */
    public function detach($walker)
    {
        if (in_array($walker, $this->walkers)) {
            $this->walkers = array_filter($this->walkers, function($item) use ($walker) {
                return $item != $walker;
            });
        }
    }

    protected function triggerWalkers($transition, $from, $to, $parameters)
    {
        foreach ($this->walkers as $walker) {
            if (method_exists($walker, $transition)) {
                call_user_func_array([$walker, $transition], array_merge([$from, $to], $parameters));
            }elseif (method_exists($walker, '_catchall_')) {
                call_user_func_array([$walker, '_catchall_'], [$transition, $from, $to, $parameters]);
            }
        }
    }

    protected function triggerListeners($transition, $from, $to, $parameters)
    {
        // Call global listeners if available.
        if (array_key_exists($this->globalListenerKey, $this->listeners)) {
            foreach ($this->listeners[$this->globalListenerKey] as $listener) {
                call_user_func_array($listener, [$transition, $from, $to, $parameters]);
            }
        }

        // Call transition-specific listeners if available.
        if (array_key_exists($transition, $this->listeners)) {
            foreach ($this->listeners[$transition] as $listener) {
                call_user_func_array($listener, array_merge([$from, $to], $parameters));
            }
        }
    }

    /**
     * Load machine configuration from string.
     *
     * @param  string $string The configuration string
     * @return void
     */
    protected function loadFromString($string)
    {
        /*
        states: draft, pending, published, archived
        - pend: draft > pending
        - publish: pending > published
        - archive: published > archived
        */
        $string = str_replace("\r\n", "\n", $string);
        $string = str_replace("\r", "\n", $string);

        $lines = explode("\n", $string);

        $states = explode(',', preg_replace('/^states:/', '', array_shift($lines)));

        $this->states = array_filter(array_map('trim', $states));

        foreach ($lines as $line) {
            if ($line == "") {
                continue;
            }
            list($transition, $ends) = explode(':', trim($line, " -"));
            list($from, $to) = array_map('trim', explode('>', $ends));


            if ( ! array_key_exists($from, $this->transitions)) {
                $this->transitions[$from] = [];
            }

            $this->transitions[$from][$transition] = $to;
        }

    }
}
