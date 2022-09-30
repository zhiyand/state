---
layout: page
title: Transitions
permalink: /transitions/
---

To perform a transition on a state machine, simply call a function with the name of the transition you specified in the `machine spec`. For example:

{% highlight php %}
<?php
$order->state->pay();
?>
{% endhighlight %}

This will perform the `pay` transition and bring the state of the machine from `placed`
to `payed`.

These `transition functions` accept parameters, which will be passed to [Transition Monitors]({{ site.baseurl }}/monitoring) for processing.

{% highlight php %}
<?php
$order->state->pay($gateway, $reference, $notes);
?>
{% endhighlight %}

Alternatively, you can call the `process()` method and specify the desired transition name.

{% highlight php %}
<?php
$order->state->process('pay');

// or with parameters

$order->state->process('pay', $gateway, $reference, $notes);
?>
{% endhighlight %}
