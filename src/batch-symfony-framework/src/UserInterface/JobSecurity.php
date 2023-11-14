<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Yokai\Batch\JobExecution;

/**
 * User interface security facade.
 * The security may not be installed, or configured, but this class will still be called.
 */
final class JobSecurity
{
    public function __construct(
        private ?AuthorizationCheckerInterface $authorizationChecker,
        private string $listAttribute,
        private string $viewAttribute,
        private string $tracesAttribute,
        private string $logsAttribute,
    ) {
    }

    /**
     * Deny access unless granted to access {@see JobExecution} list.
     *
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGrantedList(): void
    {
        $this->denyAccessUnlessGranted($this->listAttribute);
    }

    /**
     * Deny access unless granted to access {@see JobExecution} detail.
     *
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGrantedView(JobExecution $execution): void
    {
        $this->denyAccessUnlessGranted($this->viewAttribute, $execution);
    }

    /**
     * Deny access unless granted to access {@see JobExecution} traces.
     *
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGrantedTraces(JobExecution $execution): void
    {
        $this->denyAccessUnlessGranted($this->tracesAttribute, $execution);
    }

    /**
     * Deny access unless granted to access {@see JobExecution} logs.
     *
     * @throws AccessDeniedException
     */
    public function denyAccessUnlessGrantedLogs(JobExecution $execution): void
    {
        $this->denyAccessUnlessGranted($this->logsAttribute, $execution);
    }

    /**
     * @throws AccessDeniedException
     */
    private function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null): void
    {
        if ($this->authorizationChecker === null) {
            return;
        }
        if (!$this->authorizationChecker->isGranted($attribute, $subject)) {
            $exception = new AccessDeniedException();
            $exception->setAttributes([$attribute]);
            $exception->setSubject($subject);

            throw $exception;
        }
    }
}
