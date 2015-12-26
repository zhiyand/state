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

followed by lines each describing one possible transaction:

```
- pay: placed  > payed
```

The above line defines a transition named `pay` that changes the state of the machine
from `placed` to `payed`.

**Perform transitions**

To perform a transition on a state machine, simply call a function with name of the
transition. For example:

``` php
$order->state->pay();
```

This will perform the `pay` transition on the state machine we created just now.

Parameters can be passed as well:

``` php
$order->state->pay($gateway, $reference, $notes);
```

Alternatively, you can call the `process()` method and specify the desired transition
name.

``` php
$order->state->process('pay');

// or

$order->state->process('pay', $gateway, $reference, $notes);
```