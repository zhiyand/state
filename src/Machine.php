<?php

namespace Zhibaihe\State;

class Machine {

    protected $states = [];

    protected $transitions = [];

    public function __construct($string = '')
    {
        $this->loadFromString($string);

        $this->state = current($this->states);
    }

    public function state()
    {
        return $this->state;
    }

    public function states()
    {
        return $this->states;
    }

    public function transitions()
    {
        return $this->transitions;
    }

    public function process($transition)
    {
        $transitions = $this->transitions[$this->state];

        if ( ! array_key_exists($transition, $transitions)) {
            throw new StateException("Transition '$name' at state '{$this->state}' is invalid.");
        }

        $this->state = $transitions[$transition];
    }

    public function __call($name, $arguments)
    {
        $this->process($name);
    }

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