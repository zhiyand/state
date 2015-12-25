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
            $this->transitions[$transition] = array_map('trim', explode('>', $ends));
        }

    }
}