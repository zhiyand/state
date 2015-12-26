<?php

namespace Zhibaihe\State;

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

    public function __construct($string = '')
    {
        $this->loadFromString($string);

        $this->state = current($this->states);
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
     * Process a transition.
     * `\Zhibaihe\State\MachineException` is thrown when:
     * 1. the machine is not initialized;
     * 2. no transition is defined at current state;
     * 3. the transition is illegal at current state.
     * 
     * @param  string $transition  The transition to be processed
     * @param  array  $arguments   The arguments for the transition
     * @return void
     *
     * @throws \Zhibaihe\State\MachineException
     */
    public function process($transition, $arguments = [])
    {
        if (false === $this->state) {
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

        $this->triggerListeners($transition, $this->state, $to, $arguments);

        $this->state = $to;
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
        $this->process($name, $arguments);
    }

    public function on($transition, $callback)
    {
        if ( ! array_key_exists($transition, $this->listeners)) {
            $this->listeners[$transition] = [];
        }

        if ( ! in_array($callback, $this->listeners[$transition])) {
            $this->listeners[$transition][] = $callback;
        }
    }

    public function off($transition, $callback)
    {
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

    protected function triggerListeners($transition, $from, $to, $parameters)
    {
        if ( ! array_key_exists($transition, $this->listeners) || 0 == count($this->listeners[$transition])) {
            return;
        }

        foreach ($this->listeners[$transition] as $listener) {
            call_user_func_array($listener, [$from, $to, $parameters]);
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
            list($transition, $ends) = explode(':', trim($line, " -"));
            list($from, $to) = array_map('trim', explode('>', $ends));


            if ( ! array_key_exists($from, $this->transitions)) {
                $this->transitions[$from] = [];
            }

            $this->transitions[$from][$transition] = $to;
        }

    }
}