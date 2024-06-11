# Job launcher

The [DispatchMessageJobLauncher](../../src/batch-symfony-messenger/src/DispatchMessageJobLauncher.php) execute 
jobs via a symfony command message dispatch.

A [LaunchJobMessage](../../src/batch-symfony-messenger/src/LaunchJobMessage.php) message will be dispatched
and handled by the [LaunchJobMessageHandler](../../src/batch-symfony-messenger/src/LaunchJobMessageHandler.php) 
will be called with that message after being routed.

## How to configure an async transport for the launcher ?

You first need to configure an async transport, like explained
[in Symfony's doc](https://symfony.com/doc/current/messenger.html#transports-async-queued-messages).

Then you will have to route the message to this async transport you created, like explained
[in Symfony's doc](https://symfony.com/doc/current/messenger.html#routing-messages-to-a-transport).

You will end with something like :

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async: "%env(MESSENGER_TRANSPORT_DSN)%"
        routing:
            'Yokai\Batch\Bridge\Symfony\Messenger\LaunchJobMessage':  async
```

## On the same subject

- [What is a job launcher ?](../batch/domain/job-launcher.md)
