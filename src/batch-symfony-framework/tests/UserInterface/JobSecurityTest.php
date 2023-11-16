<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\Symfony\Framework\UserInterface;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Yokai\Batch\Bridge\Symfony\Framework\UserInterface\JobSecurity;
use Yokai\Batch\JobExecution;

final class JobSecurityTest extends TestCase
{
    private const LIST = 'ROLE_LIST';
    private const VIEW = 'ROLE_VIEW';
    private const TRACES = 'ROLE_TRACES';
    private const LOGS = 'ROLE_LOGS';

    public function testWithNoSecurity(): void
    {
        $security = new JobSecurity(null, 'ROLE_UNUSED', 'ROLE_UNUSED', 'ROLE_UNUSED', 'ROLE_UNUSED');
        $expected = [
            self::LIST => true,
            self::VIEW => true,
            self::TRACES => true,
            self::LOGS => true,
        ];
        self::assertEquals($expected, $this->test($security));
    }

    /**
     * @dataProvider withSecurity
     */
    public function testWithSecurity(array $attributes, array $expected): void
    {
        $authorizationChecker = new class($attributes) implements AuthorizationCheckerInterface {
            public function __construct(private array $attributes)
            {
            }

            public function isGranted(mixed $attribute, mixed $subject = null): bool
            {
                return in_array($attribute, $this->attributes, true)
                    && ($subject === null || $subject instanceof JobExecution);
            }
        };

        $security = new JobSecurity($authorizationChecker, self::LIST, self::VIEW, self::TRACES, self::LOGS);
        self::assertEquals($expected, $this->test($security));
    }

    public static function withSecurity(): \Generator
    {
        $defaults = [self::LIST => false, self::VIEW => false, self::TRACES => false, self::LOGS => false];

        yield [
            [self::LIST],
            [self::LIST => true] + $defaults,
        ];
        yield [
            [self::VIEW],
            [self::VIEW => true] + $defaults,
        ];
        yield [
            [self::TRACES],
            [self::TRACES => true] + $defaults,
        ];
        yield [
            [self::LOGS],
            [self::LOGS => true] + $defaults,
        ];
        yield [
            [self::LIST, self::VIEW, self::TRACES, self::LOGS],
            [self::LIST => true, self::VIEW => true, self::TRACES => true, self::LOGS => true],
        ];
    }

    private function test(JobSecurity $security): array
    {
        $execution = JobExecution::createRoot('64edbf4d9ec24', 'foo');

        $votes = [
            self::LIST => true,
            self::VIEW => true,
            self::TRACES => true,
            self::LOGS => true,
        ];

        try {
            $security->denyAccessUnlessGrantedList();
        } catch (AccessDeniedException) {
            $votes[self::LIST] = false;
        }

        try {
            $security->denyAccessUnlessGrantedView($execution);
        } catch (AccessDeniedException) {
            $votes[self::VIEW] = false;
        }

        try {
            $security->denyAccessUnlessGrantedTraces($execution);
        } catch (AccessDeniedException) {
            $votes[self::TRACES] = false;
        }

        try {
            $security->denyAccessUnlessGrantedLogs($execution);
        } catch (AccessDeniedException) {
            $votes[self::LOGS] = false;
        }

        return $votes;
    }
}
