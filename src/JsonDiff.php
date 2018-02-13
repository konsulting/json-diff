<?php

namespace Konsulting;

use Konsulting\Exceptions\JsonDecodeFailed;

class JsonDiff
{
    protected $exclude = [];
    protected $original;
    protected $new;

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
     * @param string|array $value
     *
     * @return $this
     */
    public function exclude($value)
    {
        $value = ! $value ? [] : $value;

        $this->exclude = is_array($value) ? $value : [$value];

        return $this;
    }

    /**
     * @param string $original
     * @param string $new
     *
     * @return array
     * @throws \Exception
     */
    public function compare($original, $new)
    {
        $this->original = $original;

        return $this->compareTo($new);
    }

    /**
     * @param string $new
     *
     * @return array
     * @throws \Konsulting\Exceptions\JsonDecodeFailed
     */
    public function compareTo($new)
    {
        $original = $this->decode($this->original);
        $new = $this->decode($new);

        $flatOriginal = $this->flatten($original);
        $flatNew = $this->flatten($new);

        // array_diff_assoc only returns the values in array1 that are not in array2
        // so if we don't find any, we're going to check the other way around too
        // otherwise we may miss some important changes, that sucks big-time.
        $added = array_diff_assoc($flatNew, $flatOriginal);
        $removed = array_diff_assoc($flatOriginal, $flatNew);

        $diff = [
            'added' => $this->inflate($added),
            'removed' => $this->inflate($removed),
        ];

        $changed = ! empty($added) || ! empty($removed);

        return compact('original', 'new', 'diff', 'changed');
    }

    /**
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
     * @param        $arr
     * @param string $base
     * @param string $divider_char
     *
     * @return array
     */
    public function flatten($arr, $base = "", $divider_char = "||")
    {
        $ret = [];
        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    $tmp_array = $this->flatten($v, $base.$k.$divider_char, $divider_char);
                    $ret = array_merge($ret, $tmp_array);
                } else {
                    $ret[$base.$k] = $v;
                }
            }
        }
        return $ret;
    }

    /**
     * @param        $arr
     * @param string $divider_char
     *
     * @return array|bool
     */
    public function inflate($arr, $divider_char = "||")
    {
        if (!is_array($arr)) {
            return false;
        }

        $split = '/' . preg_quote($divider_char, '/') . '/';

        $ret = [];
        foreach ($arr as $key => $val) {
            $parts = preg_split($split, $key, -1, PREG_SPLIT_NO_EMPTY);
            $leafpart = array_pop($parts);
            $parent = &$ret;
            foreach ($parts as $part) {
                if (!isset($parent[$part])) {
                    $parent[$part] = [];
                } elseif (!is_array($parent[$part])) {
                    $parent[$part] = [];
                }
                $parent = &$parent[$part];
            }

            if (empty($parent[$leafpart])) {
                $parent[$leafpart] = $val;
            }
        }
        return $ret;
    }
}
