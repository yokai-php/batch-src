<?php

declare(strict_types=1);

namespace Yokai\Batch\Tests\Bridge\OpenSpout;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

/**
 * Builds OpenSpout objects the same way with openspout/openspout 4.x and 5.x.
 * In 4.x, options and styles are mutable objects, configured with public properties and setters.
 * In 5.x, they are readonly objects, configured with constructor arguments.
 */
final class OpenSpoutCompat
{
    public static function isV5(): bool
    {
        return \method_exists(Row::class, 'fromValuesWithStyle');
    }

    /**
     * Arguments are named after 5.x constructor arguments,
     * with 4.x each of them is assigned to the public property with the same name.
     *
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $arguments
     *
     * @return T
     */
    public static function options(string $class, array $arguments): object
    {
        if (self::isV5()) {
            return new $class(...$arguments);
        }

        $options = new $class();
        foreach ($arguments as $property => $value) {
            $options->{$property} = $value;
        }

        return $options;
    }

    /**
     * Writer options (ODS/XLSX) with a style applied to every cell:
     * named "DEFAULT_ROW_STYLE" with 4.x, and "FALLBACK_STYLE" with 5.x.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function writerOptionsWithStyle(string $class, Style $style): object
    {
        return self::options($class, [(self::isV5() ? 'FALLBACK_STYLE' : 'DEFAULT_ROW_STYLE') => $style]);
    }

    /**
     * Arguments are named after 5.x constructor arguments,
     * with 4.x each of them is passed to the matching setter (e.g. "fontBold" to "setFontBold()").
     * Because some 4.x setters have no argument (e.g. "setFontBold()"), boolean arguments must be true.
     *
     * @param array<string, mixed> $arguments
     */
    public static function style(array $arguments): Style
    {
        if (self::isV5()) {
            return new Style(...$arguments);
        }

        $style = new Style();
        foreach ($arguments as $name => $value) {
            $style->{'set' . \ucfirst($name)}($value);
        }

        return $style;
    }

    /**
     * Row with a style applied to every cell.
     *
     * @param list<bool|\DateInterval|\DateTimeInterface|float|int|string|null> $values
     */
    public static function row(array $values, Style $style): Row
    {
        if (self::isV5()) {
            return Row::fromValuesWithStyle($values, $style);
        }

        return Row::fromValues($values, $style);
    }
}
