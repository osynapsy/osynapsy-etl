<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\Assert;

class Assertion
{
    public static function between($value, $lowerLimit, $upperLimit, $message)
    {
        if ($lowerLimit > $value || $value > $upperLimit) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function digit($value, $message)
    {
        if (!\ctype_digit($value)){
            self::raiseException($message);
        }
        return $value;
    }

    public static function equal($value1, $value2, $message)
    {
        if ($value1 != $value2) {
            self::raiseException($message);
        }
        return $value1;
    }

    public static function inArray($value, array $values, $message)
    {
        if (!in_array($value, $values)) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function integer($value, $message)
    {
        if (!is_int($value)) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function isAssoc(array $array, $message)
    {
        if (array() === $array) {
            self::raiseException($message);
        }
        if (array_keys($array) !== range(0, count($array) - 1)) {
            self::raiseException($message);
        }
        return $array;
    }

    public static function isEmpty($value, $message)
    {
        if (!empty($value)) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function isFalse($value, $message)
    {
        if ($value !== false) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function isTrue($value, $message)
    {
        if ($value !== true) {
            self::raiseException($message);
        }
        return true;
    }

    public static function isValidEmailAddress($value, $message)
    {
        if (!filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function greaterThan($value, $limit, $message)
    {
        if ($value <= $limit) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function greaterOrEqualThan($value, $limit, $message)
    {
        if ($value < $limit) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function lessThan($value, $limit, $message)
    {
        if ($value >= $limit) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function lessOrEqualThan($value, $limit, $message)
    {
        if ($value > $limit) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function notEmpty($value, $message)
    {
        if (empty($value)) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function notEmptyOrZero($value, $message)
    {
        if (empty($value) && !in_array($value, [0,'0'])) {
            self::raiseException($message);
        }
        return $value;
    }

    public static function notEqual($value1, $value2, $message)
    {
        if ($value1 == $value2) {
            self::raiseException($message);
        }
        return $value1;
    }

    protected static function raiseException($message, $code = null)
    {
        throw new AssertException($message, $code);
    }
}
