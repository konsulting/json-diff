<?php

namespace Konsulting;

use ArrayAccess;
use Konsulting\Exceptions\CannotChangeDiffResult;

class JsonDiffResult implements ArrayAccess
{
    protected $original;
    protected $new;
    protected $added = [];
    protected $removed = [];
    protected $changed = false;
    protected $diff = [];

    public function __construct($original, $new, $added = [], $removed = [])
    {
        $this->original = $original;
        $this->new = $new;
        $this->added = $added;
        $this->removed = $removed;
        $this->changed = !empty($this->added) || !empty($this->removed);

        $this->diff = $this->diff();
    }

    protected function diff()
    {
        return [
            'added'   => $this->added,
            'removed' => $this->removed,
        ];
    }

    public function toArray()
    {
        return [
            'original' => $this->original,
            'new' => $this->new,
            'diff' => $this->diff,
            'changed' => $this->changed,
        ];
    }

    public function toJson($options = JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK)
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    public function __get($name)
    {
        return isset($this->{$name}) ? $this->{$name} : null;
    }

    // Array Access
    public function offsetExists($offset)
    {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }

    public function offsetGet($offset)
    {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }

    public function offsetSet($offset, $value)
    {
        throw new CannotChangeDiffResult;
    }

    public function offsetUnset($offset) {
         throw new CannotChangeDiffResult;
    }
}
