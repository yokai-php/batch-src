<?php

declare(strict_types=1);

namespace Yokai\Batch\Bridge\Symfony\Framework\UserInterface;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Yokai\Batch\JobExecution;

/**
 * Twig utils for user interface.
 */
final class TwigExtension extends AbstractExtension
{
    public function __construct(
        private JobSecurity $security,
    ) {
    }

    public function getFunctions(): array
    {
        $isGranted = function (\Closure $function): bool {
            try {
                $function();
            } catch (AccessDeniedException) {
                return false;
            }

            return true;
        };

        return [
            new TwigFunction(
                'yokai_batch_grant_list',
                fn() => $isGranted(
                    fn() => $this->security->denyAccessUnlessGrantedList(),
                ),
            ),
            new TwigFunction(
                'yokai_batch_grant_view',
                fn(JobExecution $execution) => $isGranted(
                    fn() => $this->security->denyAccessUnlessGrantedView($execution),
                ),
            ),
            new TwigFunction(
                'yokai_batch_grant_traces',
                fn(JobExecution $execution) => $isGranted(
                    fn() => $this->security->denyAccessUnlessGrantedTraces($execution),
                ),
            ),
            new TwigFunction(
                'yokai_batch_grant_logs',
                fn(JobExecution $execution) => $isGranted(
                    fn() => $this->security->denyAccessUnlessGrantedLogs($execution),
                ),
            ),
        ];
    }
}
