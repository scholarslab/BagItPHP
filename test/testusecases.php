<?php

require_once 'lib/bagit.php';

/**
 * Recursively delete a directory.
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir")
                    rrmdir($dir . "/" . $object);
                else
                    unlink($dir . "/" . $object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 * Get a temporary name and create a directory there.
 */
function tmpdir($prefix='bag') {
    $dir = tempnam(sys_get_temp_dir(), $prefix);
    unlink($dir);
    return $dir;
}

/**
 * This abuses the unit test framework to do some use case testing.
 */
class BagPhpUseCaseTest extends PHPUnit_Framework_TestCase {
    var $bags;

    public function setUp()
    {
        $this->bags = array();
    }

    public function tearDown()
    {
        foreach ($this->bags as $bag)
        {
            rrmdir($bag->bagDirectory);
        }
    }

    /**
     * This is a use case for creating and populating a new bag. The user
     * does these actions:
     *
     * <ol>
     * <li>Create a new bag;</li>
     * <li>Add files to the bag;</li>
     * <li>Add fetch entries;</li>
     * <li>Update the bag; and</li>
     * <li>Package the bag.</li>
     * </ol>
     */
    public function testBagProducer()
    {
        $tmpdir = tmpdir();
        mkdir($tmpdir);
        $tmpbag = "$tmpdir/BagProducer";

        // 1. Create a new bag;
        $bag = new BagIt($tmpbag);
        array_push($this->bags, $bag);

        $this->assertTrue($bag->isValid());
        $this->assertTrue($bag->isExtended());

        $bagInfo = $bag->getBagInfo();
        $this->assertEquals('0.96',  $bagInfo['version']);
        $this->assertEquals('UTF-8', $bagInfo['encoding']);
        $this->assertEquals('sha1',  $bagInfo['hash']);

        $this->assertEquals("$tmpbag/data", $bag->getDataDirectory());
        $this->assertEquals('sha1', $bag->getHashEncoding());
        $this->assertEquals(0, count($bag->getBagContents()));
        $this->assertEquals(0, count($bag->getBagErrors()));

        // 2. Add files to the bag;

        // 3. Add fetch entries;

        // 4. Update the bag; and

        // 5. Package the bag.

    }


    /**
     * This is the use case for consuming a bag from someone else. The user 
     * does these actions:
     *
     * <ol>
     * <li>Open the bag;</li>
     * <li>Fetch on-line items in the bag;</li>
     * <li>Validate the bag's contents; and</li>
     * <li>Copy items from the bag onto the local disk.</li>
     * </ol>
     */
    public function testBagConsumer()
    {
        $this->fail();
    }

}

?>
