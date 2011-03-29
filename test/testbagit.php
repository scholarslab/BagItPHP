<?php

require_once 'lib/bagit.php';

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

function tmpdir($prefix='bag') {
    $dir = tempnam(sys_get_temp_dir(), $prefix);
    unlink($dir);
    mkdir($dir, 0700);
    return $dir;
}

class BagPhpTest extends PHPUnit_Framework_TestCase {
    var $tmpdir;
    var $bag;

    public function setUp() {
        $this->tmpdir = tmpdir();
        $this->bag = new BagIt($this->tmpdir);
    }

    public function tearDown() {
        rrmdir($this->tmpdir);
    }

    public function testBagDirectory() {
        $this->fail();
    }

    public function testExtended() {
        $this->fail();
    }

    public function testHashEncoding() {
        $this->fail();
    }

    public function testBagMajorVersion() {
        $this->fail();
    }

    public function testBagMinorVersion() {
        $this->fail();
    }

    public function testTagFileEncoding() {
        $this->fail();
    }

    public function testDataDirectory() {
        $this->fail();
    }

    public function testBagitFile() {
        $this->fail();
    }

    public function testManifestFile() {
        $this->fail();
    }

    public function testTagManifestFile() {
        $this->fail();
    }

    public function testFetchFile() {
        $this->fail();
    }

    public function testBagInfoFile() {
        $this->fail();
    }

    public function testManifestContents() {
        $this->fail();
    }

    public function testTagManifestContents() {
        $this->fail();
    }

    public function testFetchContents() {
        $this->fail();
    }

    public function testBagInfoContents() {
        $this->fail();
    }

    public function testBagCompression() {
        $this->fail();
    }

    public function testBagErrors() {
        $this->fail();
    }

    public function testConstructor() {
        $this->fail();
    }

    public function testIsValid() {
        $this->fail();
    }

    public function testIsExtended() {
        $this->fail();
    }

    public function testGetBagInfo() {
        $this->fail();
    }

    public function testGetDataDirectory() {
        $this->fail();
    }

    public function testGetHashEncoding() {
        $this->fail();
    }

    public function testSetHashEncoding() {
        $this->fail();
    }

    public function testShowBagInfo() {
        $this->fail();
    }

    public function testGetBagContents() {
        $this->fail();
    }

    public function testGetBagErrors() {
        $this->fail();
    }

    public function testValidate() {
        $this->fail();
    }

    public function testUpdate() {
        $this->fail();
    }

    public function testFetch() {
        $this->fail();
    }

    public function testAddFetchEntries() {
        $this->fail();
    }

    public function testPackage() {
        $this->fail();
    }

}

?>
