<?php

namespace OffbeatEngineer\State;

class Policy {

    protected $denial = false;

    public function allows($transition, $from, $to, $load)
    {
        if (method_exists($this, $transition)) {
            return call_user_func_array([$this, $transition],
                array_merge([$from, $to], $load));
        }
        return $this->denial ? false : true;
    }

    public function denies($transition, $from, $to, $load)
    {
        return ! $this->allows($transition, $from, $to, $load);
    }

}
