---
layout: page
title: Transition Monitors
permalink: /monitoring/
---

State machines are no good if you cannot monitor their state to do something about it.
With `State`, you can register transition listeners quite easily. Just attach a
[callable](http://php.net/manual/en/language.types.callable.php) to the transition
you want to monitor:

{% highlight php %}
<?php
$order->state->on('pay', [$order, 'addPayment']);
?>
{% endhighlight %}

This will trigger the `addPayment()` method on `$order` whenever the `pay` transition is
performed. The listener should have the following signature:

{% highlight php %}
<?php
function transitionListener($from, $to, $arg1, $arg2, $arg3 ...);
?>
{% endhighlight %}

It accepts a `$from` state, a `$to` state and all
transition parameters you specified when calling the `process()` method or
transition methods.

Sometimes you may want to monitor all transitions of a given state machine. This
is especially useful for logging purposes (e.g. keeping a state transition log
for orders of your e-commerce system). In this case, you can register a
`global listener` by omitting the `$transition` parameter to the `on()` call:

{% highlight php %}
<?php
$order->state->on([$order, 'logging']);
?>
{% endhighlight %}

The `logging()` method of `$order` will be called when any state transition happens
within the machine. It accepts the `$transition` name, a `$from` state, a `$to`
state, and an array of transition parameters.

{% highlight php %}
<?php
function logging($transition, $from, $to, $parameters)
?>
{% endhighlight %}
