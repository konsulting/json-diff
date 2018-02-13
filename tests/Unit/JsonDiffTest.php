<?php

namespace Konsulting\Unit\Support;

use Konsulting\JsonDiff;
use PHPUnit\Framework\TestCase;

class JsonDiffTest extends TestCase
{
    /** @test */
    public function it_diffs_simple_json()
    {
        $diff = new JsonDiff('{}');

        $this->assertEquals(['a' => 1], $diff->compareTo('{"a":"1"}')->added);
    }

    /** @test */
    public function it_diffs_simple_json_and_removes_a_column()
    {
        $diff = new JsonDiff('{}');
        $diff->exclude(['b']);

        $this->assertEquals(['a' => 1], $diff->compareTo('{"a":"1","b":"2"}')->added);
    }

    /** @test */
    public function it_decodes_to_an_array_properly()
    {
        $json = stub('initial.json');

        $diff = new JsonDiff();
        $array = $diff->decode($json);

        $this->assertEquals($array[1]['validation'], ['required']);
        $this->assertCount(4, $array);
    }

    /** @test */
    public function it_flattens_a_decoded_array_as_expected()
    {
        $json = '[{
            "name": "surname",
            "validation": [
                "required",
                "dummy"
            ]
        }]';

        $expected = [
            '0||name' => 'surname',
            '0||validation||0' => 'required',
            '0||validation||1' => 'dummy'
        ];

        $diff = new JsonDiff();
        $array = $diff->decode($json);
        $flattened = $diff->flatten($array);

        $this->assertEquals($expected, $flattened);
    }

    /** @test */
    public function it_inflates_a_flattened_array_properly()
    {
        $flattened = [
            '0||name' => 'surname',
            '0||validation||0' => 'required',
            '0||validation||1' => 'dummy'
        ];

        $expected = [
            [
                'name' => 'surname',
                'validation' => ['required', 'dummy']
            ],
        ];

        $diff = new JsonDiff();
        $inflated = $diff->inflate($flattened);

        $this->assertEquals($expected, $inflated);
    }

    /** @test */
    public function it_successfully_diffs_the_stub_files()
    {
        $json1 = stub('initial.json');
        $json2 = stub('compare.json');

        $diff = new JsonDiff();
        $result = $diff->compare($json1, $json2);

        $this->assertCount(3, $result->removed);
        $this->assertEquals(['required'], $result->removed[1]['validation']);
        $this->assertEquals(json_decode(stub('diff.json'), true), $result->diff);
        $this->assertTrue($result->changed);
    }
}
