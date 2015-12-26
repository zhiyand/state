<?php

namespace Zhibaihe\State;

class Policy {

    public function allows($transition, $from, $to, $load)
    {
        if (method_exists($this, $transition)) {
            return call_user_func_array([$this, $transition],
                array_merge([$from, $to], $load));
        }
        return true;
    }

    public function denies($transition, $from, $to, $load)
    {
        return ! $this->allows($transition, $from, $to, $load);
    }

}