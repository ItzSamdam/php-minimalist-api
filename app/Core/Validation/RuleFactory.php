<?php

namespace App\Core\Validation;

use Respect\Validation\Validator as v;

class RuleFactory
{
    public static function email(): v
    {
        return v::email()->setName('email');
    }

    public static function required(): v
    {
        return v::notEmpty()->setName('field');
    }

    public static function string(): v
    {
        return v::stringType()->setName('string');
    }

    public static function integer(): v
    {
        return v::intVal()->setName('integer');
    }

    public static function numeric(): v
    {
        return v::numeric()->setName('number');
    }

    public static function boolean(): v
    {
        return v::boolType()->setName('boolean');
    }

    public static function array(): v
    {
        return v::arrayType()->setName('array');
    }

    public static function minLength(int $length): v
    {
        return v::stringType()->length($length)->setName('string');
    }

    public static function maxLength(int $length): v
    {
        return v::stringType()->length(null, $length)->setName('string');
    }

    public static function length(int $min, int $max): v
    {
        return v::stringType()->length($min, $max)->setName('string');
    }

    public static function minValue($value): v
    {
        return v::min($value)->setName('value');
    }

    public static function maxValue($value): v
    {
        return v::max($value)->setName('value');
    }

    public static function between($min, $max): v
    {
        return v::between($min, $max)->setName('value');
    }

    public static function in(array $values): v
    {
        return v::in($values)->setName('value');
    }

    public static function date(): v
    {
        return v::date()->setName('date');
    }

    public static function url(): v
    {
        return v::url()->setName('URL');
    }

    public static function phone(): v
    {
        return v::phone()->setName('phone number');
    }

    public static function password(): v
    {
        return v::alnum('!@#$%^&*()_-+={}[]|:;"<>,.?/')
            ->noWhitespace()
            ->length(8, 64)
            ->setName('password');
    }

    public static function uuid(): v
    {
        return v::uuid()->setName('UUID');
    }

    public static function json(): v
    {
        return v::json()->setName('JSON');
    }

    public static function file(array $allowedTypes = [], int $maxSize = 10485760): v
    {
        $validator = v::file();

        if (!empty($allowedTypes)) {
            $validator = $validator->mimeType($allowedTypes);
        }

        if ($maxSize > 0) {
            $validator = $validator->size(null, $maxSize . 'B');
        }

        return $validator->setName('file');
    }

    public static function image(array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']): v
    {
        return self::file($allowedTypes)->setName('image');
    }

    // Custom composite rules
    public static function username(): v
    {
        return v::alnum('_-')->length(3, 32)->noWhitespace()->setName('username');
    }

    public static function name(): v
    {
        return v::alpha(' -')->length(2, 100)->setName('name');
    }

    public static function optional(v $rule): v
    {
        return v::optional($rule);
    }

    public static function oneOf(v ...$rules): v
    {
        return v::oneOf(...$rules);
    }

    public static function allOf(v ...$rules): v
    {
        return v::allOf(...$rules);
    }
}
