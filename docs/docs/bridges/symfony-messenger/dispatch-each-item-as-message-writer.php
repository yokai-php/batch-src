<?php

declare(strict_types=1);

use Symfony\Component\Messenger\MessageBusInterface;
use Yokai\Batch\Bridge\Symfony\Messenger\Writer\DispatchEachItemAsMessageWriter;

/** @var MessageBusInterface $messageBus */

new DispatchEachItemAsMessageWriter(
    messageBus: $messageBus,
);
