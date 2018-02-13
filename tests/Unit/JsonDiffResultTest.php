<?php

namespace Konsulting\Unit\Support;

use Konsulting\JsonDiff;
use PHPUnit\Framework\TestCase;

class JsonDiffResultTest extends TestCase
{
    /** @test */
    public function array_access_works()
    {
        $diff = JsonDiff::compare('{}', '{"a":"1"}');

        $this->assertEquals($diff['original'], []);
        $this->assertEquals($diff['new'], ["a" => 1]);

        $this->assertEquals($diff['added'], ["a" => 1]);
        $this->assertEquals($diff['removed'], []);

        $this->assertEquals($diff['diff'], ['added' => ["a" => 1], 'removed' => []]);
    }

    /** @test */
    public function object_access_works()
    {
        $diff = JsonDiff::compare('{}', '{"a":"1"}');

        $this->assertEquals($diff->original, []);
        $this->assertEquals($diff->new, ["a" => 1]);

        $this->assertEquals($diff->added, ["a" => 1]);
        $this->assertEquals($diff->removed, []);

        $this->assertEquals($diff->diff, ['added' => ["a" => 1], 'removed' => []]);
    }

    /** @test */
    public function to_array_works()
    {
        $diff = JsonDiff::compare('{}', '{"a":"1"}');

        $this->assertEquals(
            $diff->toArray(),
            [
                "original" => [],
                "new" => ["a" => 1],
                "diff" => [
                    "added" => ["a" => 1],
                    "removed" => [],
                ],
                "changed" => true,
            ]);
    }

    /** @test */
    public function to_json_works()
    {
        $diff = JsonDiff::compare('{}', '{"a":"1"}');

        $this->assertJsonStringEqualsJsonString(
            $diff->toJson(),
            '{
                "original": [],
                "new": {"a": 1},
                "diff": {
                    "added": {"a": 1},
                    "removed": []
                },
                "changed": true
            }');
    }
}
