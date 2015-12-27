# State - State machines made simple.

[![Build Status](https://travis-ci.org/zhibaihe/state.svg?branch=master)](https://travis-ci.org/zhibaihe/state)
[![Total Downloads](https://poser.pugx.org/zhibaihe/state/downloads)](https://packagist.org/packages/zhibaihe/state)
[![Latest Stable Version](https://poser.pugx.org/zhibaihe/state/v/stable)](https://packagist.org/packages/zhibaihe/state)
[![Latest Unstable Version](https://poser.pugx.org/zhibaihe/state/v/unstable)](https://packagist.org/packages/zhibaihe/state)
[![License](https://poser.pugx.org/zhibaihe/state/license)](https://packagist.org/packages/zhibaihe/state)

`State` is a state machine implementation in PHP.

## Install

Pull `State` in from Composer:

``` shell
composer require zhibaihe/state ^1.2
```

## Usage

### Create a state machine

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

Following the states line, subsequent lines each describes one possible
transition. E.g.:

```
- pay: placed  > payed
```

The above line defines a transition named `pay` that changes the state of the machine
from `placed` to `payed`.

You can provide an optional second argument to the constructor to indicate the
current state of the machine.

``` php
$order->state = new Machine("...", "payed"); // machine set to 'payed' state
```

A `teleport()` method is at your disposal if you would like to set the machine
to a specific state after instantiation.

``` php
$order->state = new Machine("..."); // machine already instantiated
...
$order->teleport("shipped"); // machine set to 'shipped' state
```

Note that the `teleport()` method does nothing but setting the machine state. None
of the transition listeners will be called since no transition is performed.
What happened here is a _teleport_! :P

### Perform transitions

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

### Monitor transitions

State machines are no good if you cannot monitor their state to do something about it.
With `State`, you can register transition listeners quite easily. Just attach a
[callable](http://php.net/manual/en/language.types.callable.php) to the transition
you want to monitor:

``` php
$order->state->on('pay', [$order, 'addPayment']);
```

This will trigger the `addPayment()` method on `$order` whenever the `pay` transition is
performed. The listener should have the following signature:

``` php
function transitionListener($from, $to, $arg1, $arg2, $arg3 ...);
```

It accepts a `$from` state, a `$to` state and all
transition parameters you specified when calling the `process()` method or
transition methods.

Sometimes you may want to monitor all transitions of a given state machine. This
is especially useful for logging purposes (e.g. keeping a state transition log
for orders of your e-commerce system). In this case, you can register a
`global listener` by omitting the `$transition` parameter to the `on()` call:

``` php
$order->state->on([$order, 'logging']);
```

The `logging()` method of `$order` will be called when any state transition happens
within the machine. It accepts the `$transition` name, a `$from` state, a `$to`
state, and an array of transition parameters.

``` php
function logging($transition, $from, $to, $parameters)
```

### Machine Walkers

Instead of registering transition listeners for each individual transition separately,
you can provide an object as the machine `walker`, which walks along the edges between
states. When a transition is performed, the method named after that transition on the
`walker` object will be triggered, just like a transition listener.

``` php
class OrderWalker {
    public function pay($from, $to, $gateway) {
        // do something here.
    }
}

$order->state->attach(new OrderWalker); // attach the walker

$order->state->pay('stripe'); // OrderWalker::pay() gets called
```

The `attach()` method is used to associate a `walker` with the state machine.
You can define an optional `_catchall_()` method on your `walker` object to
monitor those transitions you didn't explicitly catch through a method.

``` php
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
```

The `_catchall_()` method follows the same signature of global listeners.

### Control transitions using a policy

When initializing the machine, you can specify a policy that gives you fine-grained
control over whether each individual transition should be performed or not.

To create a policy for your machine, simply extend the Zhibaihe\State\Policy class
and pass an instance of your policy class to the `Machine` constructor as the third
argument.

``` php
use Zhibaihe\State\Policy;

class OrderPolicy extends Policy {
}

$order->state = new Machine("...", "...", $policy);
```

Before each transition, the state machine will consult the policy by invoking a method
named after the transition. It then proceeds only if that method call returns true.
As an example:

``` php
class OrderPolicy extends Policy {

    public function pay($gateway){
        return 'stripe' == $gateway;
    }

}
...
// This transition will be denied,
// machine state stays unchanged.
$order->state->pay('paypal');

// This transition will be allowed,
// machine state changed to 'payed'.
$order->state->pay('stripe');
```

Transitions not specified in the policy are allowed by default.
If you prefer a safer policy scheme, override the `$denial` property on
your policy class to `true`. That way, unspecified transitions are denied
by default unless you explicitly allow them in your policy.

``` php
class OrderPolicy extends Policy {

    protected $denial = true;

    /* your other code */
}
```

If no policy is specified upon machine instantiation, a policy allows
all transitions will be used by default.