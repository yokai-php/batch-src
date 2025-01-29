<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Yokai\Batch\JobExecution;

final class JobVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \str_starts_with($attribute, 'JOB_');
    }

    /**
     * @param JobExecution|null $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // TODO: Implement voteOnAttribute() method.
    }
}
