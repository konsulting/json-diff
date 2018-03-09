<?php

namespace Konsulting;

use Konsulting\Exceptions\JsonDecodeFailed;

class JsonDiff
{
    protected $exclude = [];
    protected $original;
    protected $new;
    protected $divider = '||';

    /**
     * JsonDiff constructor.
     *
     * @param string $original
     */
    public function __construct($original = '')
    {
        $this->original = $original;
    }

    /**
     * Set indices to ignore when checking the diff
     *
     * @param string|array $value
     *
     * @return $this
     */
    public function exclude($value)
    {
        if (! empty($value)) {
            $this->exclude = is_array($value) ? $value : [$value];
        }

        return $this;
    }

    /**
     * Clear our exclusions
     *
     * @return $this
     */
    public function clearExclusions()
    {
        $this->exclude = [];

        return $this;
    }

    /**
     * Set the divider used for the flatten and inflate operations
     *
     * @param null $divider
     *
     * @return $this
     */
    public function setDivider($divider = null)
    {
        $this->divider = $divider ?: '||';

        return $this;
    }

    /**
     * Allow us to set the original json for comparison
     *
     * @param $value
     *
     * @return $this
     */
    public function setOriginal($value)
    {
        $this->original = $value;

        return $this;
    }

    /**
     * Simple factory method for cleaner usage
     *
     * @param $original
     *
     * @return static
     */
    public static function original($original)
    {
        return new static($original);
    }

    /**
     * Simple call to do everything tidyly
     *
     * @param string $original
     * @param string $new
     * @param array  $exclude
     *
     * @return \Konsulting\JsonDiffResult
     * @throws \Konsulting\Exceptions\JsonDecodeFailed
     */
    public static function compare($original, $new, $exclude = null)
    {
        return static::original($original)->exclude($exclude)->compareTo($new);
    }

    /**
     * Perform comparison of the original to this new JSON.
     *
     * @param string $new
     *
     * @return JsonDiffResult
     * @throws \Konsulting\Exceptions\JsonDecodeFailed
     */
    public function compareTo($new)
    {
        $original = $this->decode($this->original);
        $new = $this->decode($new);

        // we flatten the whole array into a single level array with the
        // indices representing the full depth, this lets us diff in
        // a good level of detail.
        $flatOriginal = $this->flatten($original);
        $flatNew = $this->flatten($new);

        // array_diff_assoc only returns the values in array1 that are not in array2
        // so if we don't find any, we're going to check the other way around too
        // otherwise we may miss some important changes, that sucks big-time.
        $added = array_diff_assoc($flatNew, $flatOriginal);
        $removed = array_diff_assoc($flatOriginal, $flatNew);

        return new JsonDiffResult($original, $new, $this->inflate($added), $this->inflate($removed));
    }

    /**
     * Try to decode the JSON we were passed
     *
     * @param $json
     *
     * @return array
     * @throws \Konsulting\Exceptions\JsonDecodeFailed
     */
    public function decode($json)
    {
        $array = json_decode($json, true);

        if (json_last_error() !== 0) {
            throw new JsonDecodeFailed('Failed decoding json');
        }

        return $this->stripColumns($this->exclude, $array);
    }

    /**
     * Strip data that we want to ignore for diff-ing. It is a deep operation, digging into the data.
     *
     * @param array $columns
     * @param array $array
     *
     * @return array
     */
    public function stripColumns($columns = [], $array = [])
    {
        if (! is_array($array)) {
            return $array;
        }

        $array = array_diff_key($array, array_fill_keys($columns, null));

        foreach($array as $k => $v) {
            if(is_array($v)) {
                $array[$k] = $this->stripColumns($columns, $v);
            }
        }

        return $array;
    }

    /**
     * Flatten the json into a single level array with keys that represent the nested data positions.
     *
     * @param        $toFlatten
     * @param string $base
     *
     * @return array
     */
    public function flatten($toFlatten, $base = "")
    {
        if (! is_array($toFlatten)) {
            return $toFlatten;
        }

        $flattened = [];
        foreach ($toFlatten as $key => $value) {
            $flattenedValue = $this->flatten($value, $base.$key.$this->divider);

            $flattened = array_merge(
                $flattened,
                is_array($flattenedValue) ? $flattenedValue : [$base.$key => $flattenedValue]
            );
        }

        return $flattened;
    }

    /**
     * Inflate the single level array back up to a multi-dimensional PHP array
     *
     * @param        $toInflate
     *
     * @return mixed
     */
    public function inflate($toInflate)
    {
        if (!is_array($toInflate)) {
            return $toInflate;
        }

        $inflated = [];

        foreach ($toInflate as $complexKey => $value) {
            $this->inflateByComplexKey($inflated, $complexKey, $value);
        }

        return $inflated;
    }

    /**
     * Add a value to the array, by working through the nesting and popping the
     * value in the correct place. The array is passed by reference and
     * worked on directly.
     *
     * @param $inflated
     * @param $complexKey
     * @param $value
     */
    protected function inflateByComplexKey(&$inflated, $complexKey, $value)
    {
        $divider = '/' . preg_quote($this->divider, '/') . '/';
        $keys = preg_split($divider, $complexKey, -1, PREG_SPLIT_NO_EMPTY);
        $finalKey = array_pop($keys);

        foreach ($keys as $key) {
            if (!isset($inflated[$key])) {
                $inflated[$key] = [];
            }
            $inflated = &$inflated[$key];
        }

        $inflated[$finalKey] = $value;
    }
}
