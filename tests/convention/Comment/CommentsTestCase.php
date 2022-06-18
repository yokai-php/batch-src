<?php

declare(strict_types=1);

namespace Yokai\Batch\Sources\Tests\Convention\Comment;

use PHPUnit\Framework\TestCase;

abstract class CommentsTestCase extends TestCase
{
    protected static function assertAllSeeDocAreSurroundedWithBrackets(string $comment): void
    {
        if (!\preg_match_all('/.@see [^ }]+./', $comment, $matches)) {
            self::assertTrue(true);
            return;
        }

        foreach ($matches[0] as $match) {
            self::assertStringStartsWith('{', $match);
            self::assertStringEndsWith('}', $match);
        }
    }
}
