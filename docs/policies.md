---
layout: page
title: Policies
permalink: /policies/
---

Sometimes you may want to do some checking before a transition and allow or deny
that transition conditionally. You can do exactly this with `State` using poilcies.

To create a policy for your machine, simply extend the Zhibaihe\State\Policy class
and pass an instance of your policy class to the `Machine` constructor as the third
argument.

{% highlight php %}
<?php
use Zhibaihe\State\Policy;

class OrderPolicy extends Policy {
}

$order->state = new Machine("...", "...", $policy);
?>
{% endhighlight %}

Before each transition, the state machine will consult the policy by invoking a method
named after the transition. It then proceeds only if that method call returns true.
As an example:

{% highlight php %}
<?php
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
?>
{% endhighlight %}

Transitions not specified in the policy are allowed by default.
If you prefer a safer policy scheme, override the `$denial` property on
your policy class to `true`. That way, unspecified transitions are denied
by default unless you explicitly allow them in your policy.

{% highlight php %}
<?php
class OrderPolicy extends Policy {

    protected $denial = true;

    /* your other code */
}
?>
{% endhighlight %}

If no policy is specified upon machine instantiation, a policy allows
all transitions will be used by default.