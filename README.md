# BagIt PHP

[![Build Status](https://travis-ci.com/ScholarsLab/BagItPHP.svg?branch=develop)](http://travis-ci.com/ScholarsLab/BagItPHP)

This is a PHP implementation of the [BagIt
1.0 specification](https://tools.ietf.org/html/rfc8493)

## Supported Features:

* bag compiling
* manifest and tagmanifest generation
* generation of tag files, bag-info.txt and bagit.txt
* fetching remote files (fetch.txt)
* bag validation
* support for multiple manifest/tagmanifest files with different hash algorithms
* support for the following hash algorithms (if your PHP installation supports them)
   * md5, sha-1, sha-256, sha-384, sha-512, sha3-224, sha3-256, sha3-384, sha3-512

## Installation

BagItPHP requires the [zip](https://www.php.net/manual/en/book.zip.php), [hash](https://www.php.net/manual/en/book.hash.php)
and [iconv](https://www.php.net/manual/en/book.iconv.php) PHP extensions to be installed. Composer will attempt
to verify these extensions exist before installing.

### Using Composer

BagItPHP can be installed using [composer](https://getcomposer.org/) using a
package repository. 

```bash
% composer require scholarslab/bagit
```

### Cloning this Repository

You can also clone this repository to try out the BagItPHP library.

```bash
% git clone git://github.com/scholarslab/BagItPHP.git
% cd BagItPHP
% composer install
```

## Example: Creating a bag

```php
require_once '<path to BagItPHP>/vendor/autoload.php';

use ScholarsLab\BagIt\BagIt;

define('BASE_DIR', 'testbag');

// create a new bag at BASE_DIR
$bag = new BagIt(BASE_DIR);

// add a file; these are relative to the data directory
$bag->addFile('../phpunit.xml', 'phpunit.xml');

// update the hashes
$bag->update();

// create a tarball
$bag->package('testbag');

// the bag package will be created at ./testbag.tgz
```

## Example: Creating an extended bag, with fetch.txt and bag-info.txt entries

```php
require_once '<path to BagItPHP>/vendor/autoload.php';

use ScholarsLab\BagIt\BagIt;

define('BASE_DIR', 'testbag');

// define some metadata to add to bag-info.txt
$baginfo = array('First-Tag' => 'This is the first tag value',
  'Second-Tag' => 'This is the second tag value'
);

// create a new bag at BASE_DIR
$bag = new BagIt(BASE_DIR, true, true, true, $baginfo);

// add a file; these are relative to the data directory
$bag->addFile('../phpunit.xml', 'phpunit.xml');

// add additional metadata to bag-info.txt
$bag->setBagInfoData('Third-Tag', 'This is the third tag value');

// add some entries to fetch.txt
$bag->fetch->add('http://example.com/bar.htm', 'bar.htm');
$bag->fetch->add('http://example.com/baz.htm', 'baz.htm');

// update the hashes
$bag->update();

// create a tarball
$bag->package('testbag');

// the bag package will be created at ./testbag.tgz
```

## Example: Validating an existing bag

```php
require_once '<path to BagItPHP>/vendor/autoload.php';

use ScholarsLab\BagIt\BagIt;

// use an existing bag
$bag = new BagIt('test/TestBag.tgz');

// check validity
var_dump((bool)$bag->isValid());
```

## Example: Reading a bag

```php
require_once '<path to BagItPHP>/vendor/autoload.php';

use ScholarsLab\BagIt\BagIt;

// use an existing bag
$bag = new BagIt('test/TestBag.tgz');

// validate the bag
$bag->validate();

// only execute if a valid bag
if (count($bag->getBagErrors()) == 0) {
  // retrieve remote files
  $bag->fetch->download();

  // copy files
  foreach ($bag->getBagContents() as $filename) {
    copy($filename, 'final/destination/' . basename($filename));
  }
}
```

## Feedback

We are relying on the [GitHub issues tracker][issues] linked from the above for
feedback. File bugs or other issues [here][issues].

[issues]: http://github.com/scholarslab/BagItPHP/issues

## Maintainers

* [Mark Jordan](https://github.com/mjordan)
* [Jared Whiklo](https://github.com/whikloj)

## Tests

The BagItPHP library includes unit tests to ensure the quality of the software.
The easiest way to contribute to the project is to to let us know about any bugs,
and include a test case.

## Note on Patches/Pull Requests

* Fork the project
* Make your feature addition/bug fix.
* Add tests for it. This is important so we don't unintentionally break it in a future
  version
* Commit
* Send us a pull request...bonus points for topic branches.

## Kudos

Thanks to everyone who's contributed to this:

* [Wayne Graham](https://github.com/waynegraham)
* [Mark Jordan](https://github.com/mjordan)
* [Eric Rochester](https://github.com/erochest)

## License

Apache 2.0
