#  JSON Diff

Get a readable diff from two bits of JSON.

## Installation

`composer require konsulting/json-diff`

## Usage

There are a few ways to use the class. It depends on your preference, see the example below.
It is also possible to exclude a set of keys from the diff, also shown below.

```php
<?php

use Konsulting\JsonDiff;

// Using 'new'
$diff = (new JsonDiff('["json"]'))->exclude(['key'])->compareTo('["different_json"]');

// Using a simple factory method
$diff = JsonDiff::original('["json"]')->exclude(['key'])->compareTo('["different_json"]');

// Using a simple 'all-in-one' static method.
$diff = JsonDiff::compare($original = '["json"]', $new = '["different_json"]', $exclude = ['key']);

```

The output is a JsonDiffResult that stores the following information:

 - *original* - the original json as an array
 - *new* - the new json as an array
 - *added* - what was added
 - *removed* - what was removed
 - *diff* - a combined diff of added and removed as an array
 - *changed* - a boolean signifying if there was a relevant change
 
 The JsonDiffResult allows access of the result in several ways:

```php
<?php
    $diff->toArray();
    
    [
        'original' => ['...'],
        'new' => ['...'],
        'diff' => [
            'added' => ['...'],
            'removed' => ['...']
        ],
        'changed' => true // or false if nothing changed
    ];
    
    $diff->toJson(); // Json representation of toArray()
    
    // All the properties can be accessed using array or object notation;
    // original, new, added, removed, diff, changed.
    
    $diff->diff;
    $diff['diff'];
```

## Contributing

Contributions are welcome and will be fully credited. We will accept contributions by Pull Request.

Please:

* Use the PSR-2 Coding Standard
* Add tests, if you’re not sure how, please ask.
* Document changes in behaviour, including readme.md.

## Testing
We use [PHPUnit](https://phpunit.de)

Run tests using PHPUnit: `vendor/bin/phpunit`
