<?php

namespace ScholarsLab\BagIt\Test;

use ScholarsLab\BagIt\BagIt;
use ScholarsLab\BagIt\BagItUtils;
use ScholarsLab\BagIt\Tests\BagItTestCase;

/**
 * This abuses the unit test framework to do some use case testing.
 *
 * @package ScholarsLab\BagIt\Test
 */
class BagItPhpUseCaseTest extends BagItTestCase
{
    /**
     * Things to remove.
     *
     * @var array
     */
    private $to_rm;

    private function queueRm($dirname)
    {
        array_push($this->to_rm, $dirname);
    }

    public function setUp()
    {
        $this->to_rm = array();
    }

    public function tearDown()
    {
        foreach ($this->to_rm as $dirname) {
            BagItUtils::rrmdir($dirname);
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
     * @group BagItUseCase
     */
    public function testBagProducer()
    {
        $tmpdir = BagItUtils::tmpdir();
        mkdir($tmpdir);
        $this->queueRm($tmpdir);

        $tmpbag = "$tmpdir/BagProducer";

        // 1. Create a new bag;
        $bag = new BagIt($tmpbag);

        $this->assertTrue($bag->isValid());
        $this->assertTrue($bag->isExtended());

        $bagInfo = $bag->getBagInfo();
        $this->assertEquals('1.0', $bagInfo['version']);
        $this->assertEquals('UTF-8', $bagInfo['encoding']);
        $this->assertEquals('sha512', $bagInfo['hash']);

        $this->assertEquals("$tmpbag/data", $bag->getDataDirectory());
        $this->assertArrayEquals(array('sha512'), $bag->getHashEncodings());
        $this->assertEquals(0, count($bag->getBagContents()));
        $this->assertEquals(0, count($bag->getBagErrors()));

        // 2. Add files to the bag;
        $srcdir = __DIR__ . '/TestBag/data';
        $bag->addFile("$srcdir/README.txt", 'data/README.txt');
        $bag->addFile("$srcdir/imgs/uvalib.png", 'data/payloads/uvalib.png');
        // This needs to add data/ to the beginning of the file.
        $bag->addFile(
            "$srcdir/imgs/fibtriangle-110x110.jpg",
            'payloads/fibtri.jpg'
        );

        // 3. Add fetch entries;
        $bag->getFetch()->add('http://www.scholarslab.org/', 'data/index.html');

        // 4. Update the bag; and
        $bag->update();

        // 5. Package the bag.
        $pkgfile = "$tmpdir/BagProducer.tgz";
        $bag->package($pkgfile);

        // Finally, we need to validate the contents of the package.
        $dest = new BagIt($pkgfile);
        $this->queueRm($dest->getBagDirectory());

        // First, verify that the data files are correct.
        $this->assertEquals(
            "BagIt-Version: 1.0\n" .
            "Tag-File-Character-Encoding: UTF-8\n",
            file_get_contents($dest->getBagDirectory() . "/bagit.txt")
        );

        // Second, verify that everything was uncompressed OK.
        $dest->validate();
        $this->assertEquals(0, count($dest->getBagErrors()));

        // Now, check that the file was fetched.
        $dest->getFetch()->download();
        $this->assertFileExists("{$dest->getBagDirectory()}/data/index.html");

        // Also, check that fibtri.jpg was added in the data/ directory.
        $this->assertFalse(
            file_exists("{$dest->getBagDirectory()}/payloads/fibtri.jpg")
        );
        $this->assertFileExists("{$dest->getBagDirectory()}/data/payloads/fibtri.jpg");

        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * This is the use case for consuming a bag from someone else. The user
     * does these actions:
     *
     * <ol>
     * <li>Open the bag;</li>
     * <li>Validate the downloaded contents;</li>
     * <li>Fetch on-line items in the bag;</li>
     * <li>Validate the bag's contents; and</li>
     * <li>Copy items from the bag onto the local disk.</li>
     * </ol>
     * @group BagItUseCase
     */
    public function testBagConsumer()
    {
        $srcbag = __DIR__ . '/TestBag.tgz';

        // 1. Open the bag;
        $bag = new BagIt($srcbag);
        $this->queueRm($bag->getBagDirectory());

        $this->assertTrue($bag->isValid());
        $this->assertTrue($bag->isExtended());

        $bagInfo = $bag->getBagInfo();
        $this->assertEquals('0.96', $bagInfo['version']);
        $this->assertEquals('UTF-8', $bagInfo['encoding']);
        $this->assertEquals('sha1', $bagInfo['hash']);

        $this->assertArrayEquals(array('sha1'), $bag->getHashEncodings());
        $this->assertEquals(7, count($bag->getBagContents()));
        $this->assertEquals(0, count($bag->getBagErrors()));

        // 2. Validate the downloaded contents;
        $bag->validate();
        $this->assertEquals(0, count($bag->getBagErrors()));

        // 3. Fetch on-line items in the bag;
        $bag->getFetch()->download();
        $bag->update();
        $bag->validate();
        $this->assertEquals(8, count($bag->getBagContents()));

        // 4. Validate the bag's contents; and
        $this->assertEquals(0, count($bag->getBagErrors()));

        // 5. Copy items from the bag onto the local disk.
        $tmpdir = BagItUtils::tmpdir();
        mkdir($tmpdir);
        $this->queueRm($tmpdir);

        foreach ($bag->getBagContents() as $bagFile) {
            $basename = basename($bagFile);
            copy($bagFile, "$tmpdir/$basename");
        }

        $this->assertEquals(
            count($bag->getBagContents()) + 2,
            count(scandir($tmpdir))
        );
        $this->assertEquals(
            count($bag->getManifests()['sha1']->getData()) + 2,
            count(scandir($tmpdir))
        );

        BagItUtils::rrmdir($tmpdir);
    }
}
