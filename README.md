# State - State machines made simple.

[![Build Status](https://travis-ci.org/zhibaihe/state.svg?branch=master)](https://travis-ci.org/zhibaihe/state)

`State` is a state machine implementation in PHP.

## Install

Pull `State` in from Composer:

``` shell
composer require zhibaihe/state
```

## Usage

**Create a state machine**

Creating a state machine with `State` is a cinch. Just pass a `machine spec`, which 
is a specially formatted string description, to the machine into the constructor,
like the following:

``` php
use Zhibaihe\State\Machine;

$order->state = new Machine("states: placed, payed, shipped, completed
							 - pay:      placed  > payed
							 - ship:     payed   > shipped
							 - complete: shipped > completed");
```

The `machine spec` starts with one line enumerating all states, namely `placed`,
`payed`, `shipped` and `completed`. The first state (`placed`) will be treated
as the initial state.

```
states: placed, payed, shipped, completed
```

The following lines each describes one possible transition:

```
- pay: placed  > payed
```

The above line defines a transition named `pay` that changes the state of the machine
from `placed` to `payed`.

**Perform transitions**

To perform a transition on a state machine, simply call a function with the name of the
transition you specified in the `machine spec`. For example:

``` php
$order->state->pay();
```

This will perform the `pay` transition and bring the state of the machine from `placed`
to `payed`.

Parameters can be passed as well:

``` php
$order->state->pay($gateway, $reference, $notes);
```

Alternatively, you can call the `process()` method and specify the desired transition
name.

``` php
$order->state->process('pay');

// or with parameters

$order->state->process('pay', $gateway, $reference, $notes);
```

**Monitor transitions**

State machines are no good if you cannot monitor their state to do something about it.
With `State`, you can register transition listeners quite easily. Just attach a
[callable](http://php.net/manual/en/language.types.callable.php)to the transition
you want to monitor:

``` php
$order->state->on('pay', [$order, 'addPayment']);
```

This will trigger the `addPayment()` method on `$order` whenever the `pay` transition is
performed. The listener should have the following signature:

``` php
function transitionListener($from, $to, $parameters);
```

The first argument is machine state before the transition; the second argument
is the machine state after the transition; and the third argument is an array of all
transition parameters (specified when you call the `process()` method or dynamic methods).