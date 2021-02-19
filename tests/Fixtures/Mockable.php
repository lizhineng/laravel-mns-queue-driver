<?php

namespace Zhineng\QueueMns\Tests\Fixtures;

trait Mockable
{
    public static function mock(array $attributes = []): self
    {
        $model = new static;

        foreach ($attributes as $item => $value) {
            $model->{$item} = $value;
        }

        return $model;
    }
}