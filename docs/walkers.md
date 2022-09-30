---
layout: page
title: Machine Walkers
permalink: /walkers/
---

Instead of registering transition listeners for each individual transition separately,
you can provide an object as the machine `walker`, which walks along the edges between
states. When a transition is performed, the method named after that transition on the
`walker` object will be triggered, just like a transition listener.

{% highlight php %}
<?php
class OrderWalker {
    public function pay($from, $to, $gateway) {
        // do something here.
    }
}

$order->state->attach(new OrderWalker); // attach the walker

$order->state->pay('stripe'); // OrderWalker::pay() gets called
?>
{% endhighlight %}

The `attach()` method is used to associate a `walker` with the state machine.
You can define an optional `_catchall_()` method on your `walker` object to
monitor those transitions you didn't explicitly catch through a method.

{% highlight php %}
<?php
class OrderWalker {
    public function pay($from, $to, $gateway) {
        // do something here.
    }

    public function _catchall_($transition, $from, $to, array $parameters) {
        // transitions without a corresponding method will
        // get passed to this one
    }
}

$order->state->attach(new OrderWalker); // attach the walker

$order->state->pay('stripe'); // OrderWalker::pay() gets called

$order->state->ship(); // OrderWalker::_catchall_() gets called
?>
{% endhighlight %}

The `_catchall_()` method follows the same signature of global listeners.
