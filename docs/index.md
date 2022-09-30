---
layout: page
title: State - State machines made simple.
---

[![Build Status](https://travis-ci.org/zhibaihe/state.svg?branch=master)](https://travis-ci.org/zhibaihe/state)
[![Total Downloads](https://poser.pugx.org/zhibaihe/state/downloads)](https://packagist.org/packages/zhibaihe/state)
[![Latest Stable Version](https://poser.pugx.org/zhibaihe/state/v/stable)](https://packagist.org/packages/zhibaihe/state)
[![Latest Unstable Version](https://poser.pugx.org/zhibaihe/state/v/unstable)](https://packagist.org/packages/zhibaihe/state)
[![License](https://poser.pugx.org/zhibaihe/state/license)](https://packagist.org/packages/zhibaihe/state)

`State` is a state machine implementation in PHP.

## Install

Pull `State` in from Composer:

{% highlight php %}
composer require zhibaihe/state ^1.3
{% endhighlight %}

## Create a state machine

Creating a state machine with `State` is a cinch. Just pass a `machine spec`, which
is a specially formatted string description, to the machine into the constructor,
like the following:

{% highlight php %}
<?php
use Zhibaihe\State\Machine;

...

$order->state = new Machine("states: placed, payed, shipped, completed
               - pay:      placed  > payed
               - ship:     payed   > shipped
               - complete: shipped > completed");
?>
{% endhighlight %}

The `machine spec` starts with one line enumerating all states, namely `placed`,
`payed`, `shipped` and `completed`. The first state (`placed`) will be treated
as the initial state.

{% highlight php %}
states: placed, payed, shipped, completed
{% endhighlight %}

Following the states line, subsequent lines each describes one possible
transition. E.g.:

{% highlight php %}
- pay: placed  > payed
{% endhighlight %}

The above line defines a transition named `pay` that changes the state of the machine
from `placed` to `payed`.

You can provide an optional second argument to the constructor to indicate the
current state of the machine.

{% highlight php %}
<?php
$order->state = new Machine("...", "payed"); // machine set to 'payed' state
?>
{% endhighlight %}

## Teleporting

A `teleport()` method is at your disposal if you would like to set the machine
to a specific state after instantiation.

{% highlight php %}
<?php
$order->state = new Machine("..."); // machine already instantiated
...
$order->teleport("shipped"); // machine set to 'shipped' state
?>
{% endhighlight %}

Note that the `teleport()` method does nothing but setting the machine state. None
of the transition listeners will be called since no transition is performed.
What happened here is a _teleport_! :P
