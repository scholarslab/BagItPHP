<?php

namespace ScholarsLab\BagIt\Test;

use PHPUnit\Framework\TestCase;
use ScholarsLab\BagIt\BagItManifest;
use ScholarsLab\BagIt\BagItUtils;

/**
 * Class BagItManifestTest
 * @package ScholarsLab\BagIt\Test
 * @coversDefaultClass \ScholarsLab\BagIt\BagItManifest
 */
class BagItManifestTest extends TestCase
{
    /**
     * @var string
     */
    private $tmpdir;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var \ScholarsLab\BagIt\BagItManifest
     */
    private $manifest;

    public function setUp()
    {
        $this->tmpdir = BagItUtils::tmpdir();
        mkdir($this->tmpdir);

        $this->prefix = __DIR__ . '/TestBag';
        $src = "{$this->prefix}/manifest-sha1.txt";
        $dest = "{$this->tmpdir}/manifest-sha1.txt";

        copy($src, $dest);
        $this->manifest = new BagItManifest($dest, $this->prefix . '/');
    }

    public function tearDown()
    {
        BagItUtils::rrmdir($this->tmpdir);
    }

    /**
     * Test the path prefix is set correctly.
     * @group BagItManifest
     * @covers ::getPathPrefix
     */
    public function testPathPrefix()
    {
        $this->assertEquals($this->prefix . '/', $this->manifest->getPathPrefix());
    }

    /**
     * Test settings file encoding works properly.
     * @group BagItManifest
     * @covers ::getFileEncoding
     */
    public function testFileEncoding()
    {
        $this->assertEquals('UTF-8', $this->manifest->getFileEncoding());

        $manifest = new BagItManifest(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->prefix,
            'ISO-8859-1'
        );
        $this->assertEquals('ISO-8859-1', $manifest->getFileEncoding());
    }

    /**
     * Test filename is set correctly.
     * @group BagItManifest
     * @covers ::getFileName
     */
    public function testFileName()
    {
        $this->assertEquals(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->manifest->getFileName()
        );
    }

    /**
     * Test internal data is stored correctly.
     * @group BagItManifest
     * @covers ::getData
     */
    public function testData()
    {
        $data = $this->manifest->getData();

        $this->assertInternalType('array', $data);
        $this->assertCount(7, $data);


        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $data['data/imgs/109x109xcoins1-150x150.jpg']
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $data['data/imgs/109x109xprosody.png']
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $data['data/imgs/110x108xmetaphor1.png']
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $data['data/imgs/fellows1-150x150.png']
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $data['data/imgs/fibtriangle-110x110.jpg']
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $data['data/imgs/uvalib.png']
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $data['data/README.txt']
        );
    }

    /**
     * Test setting hash encoding.
     * @group BagItManifest
     * @covers ::getHashEncoding
     */
    public function testHashEncoding()
    {
        $this->assertEquals('sha1', $this->manifest->getHashEncoding());

        $md5 = "{$this->tmpdir}/manifest-md5.txt";
        touch($md5);
        $md5Manifest = new BagItManifest($md5, $this->prefix);
        $this->assertEquals('md5', $md5Manifest->getHashEncoding());
    }

    /**
     * Test loading manifest file data from current file.
     * @group BagItManifest
     * @covers ::read
     */
    public function testRead()
    {
        file_put_contents(
            "{$this->tmpdir}/manifest-sha1.txt",
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file1.txt\n" .
            "abababababababababababababababababababab file2.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd file3.txt\n"
        );

        $data = $this->manifest->read();

        $this->assertTrue($this->manifest->getData() === $data);

        $this->assertCount(3, $data);

        $keys = array_keys($data);
        sort($keys);
        $this->assertEquals('file1.txt', $keys[0]);
        $this->assertEquals('file2.txt', $keys[1]);
        $this->assertEquals('file3.txt', $keys[2]);
    }

    /**
     * Test loading manifest file data from new file.
     * @group BagItManifest
     * @covers ::read
     */
    public function testReadFileName()
    {
        $filename = "{$this->tmpdir}/manifest-md5.txt";
        file_put_contents(
            $filename,
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file-a.txt\n" .
            "abababababababababababababababab file-b.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcd file-c.txt\n"
        );

        $data = $this->manifest->read($filename);

        $this->assertTrue($this->manifest->getData() === $data);

        $this->assertCount(3, $data);

        $keys = array_keys($data);
        sort($keys);
        $this->assertEquals('file-a.txt', $keys[0]);
        $this->assertEquals('file-b.txt', $keys[1]);
        $this->assertEquals('file-c.txt', $keys[2]);

        $this->assertEquals($filename, $this->manifest->getFileName());
        $this->assertEquals('md5', $this->manifest->getHashEncoding());
    }

    /**
     * Test clearing manifest data.
     * @group BagItManifest
     * @covers ::clear
     */
    public function testClear()
    {
        $this->manifest->clear();

        $this->assertCount(0, $this->manifest->getData());
        $this->assertEquals(0, filesize($this->manifest->getFileName()));
    }

    /**
     * Test update manifest and verify it actions.
     * @group BagItManifest
     * @covers ::update
     */
    public function testUpdate()
    {
        // First, clear it out and verify it.
        $this->manifest->clear();
        $this->assertCount(0, $this->manifest->getData());
        $this->assertEquals(0, filesize($this->manifest->getFileName()));

        // Now, regenerate it and test.
        $this->manifest->update(BagItUtils::rls("{$this->prefix}/data"));
        $this->assertCount(7, $this->manifest->getData());

        $data = $this->manifest->getData();
        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $data['data/imgs/109x109xcoins1-150x150.jpg']
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $data['data/imgs/109x109xprosody.png']
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $data['data/imgs/110x108xmetaphor1.png']
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $data['data/imgs/fellows1-150x150.png']
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $data['data/imgs/fibtriangle-110x110.jpg']
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $data['data/imgs/uvalib.png']
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $data['data/README.txt']
        );
    }

    /**
     * Test calculating file hash.
     * @group BagItManifest
     * @covers ::calculateHash
     */
    public function testCalculateHash()
    {
        $fileName = "{$this->tmpdir}/testCalculateHash";
        $data = "This space intentionally left blank.\n";
        file_put_contents($fileName, $data);

        $hash = $this->manifest->calculateHash($fileName);

        $this->assertEquals('a5c44171ca6618c6ee24c3f3f3019df8df09a2e0', $hash);
    }

    /**
     * Test writing data to the default file.
     *
     * This test used to make use of access to internal $data, now we read and
     * write, which might reduce the usefulness of this test.
     *
     * @group BagItManifest
     * @covers ::write
     */
    public function testWrite()
    {

        $filename = "{$this->tmpdir}/manifest-md5.txt";
        file_put_contents(
            $filename,
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file-1.txt\n" .
            "abababababababababababababababab file-2.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcd file-3.txt\n"
        );
        $this->manifest->read($filename);
        $data = array(
            'file-1.txt' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'file-2.txt' => 'abababababababababababababababab',
            'file-3.txt' => 'abcdabcdabcdabcdabcdabcdabcdabcd',
        );
        $this->assertEquals($data, $this->manifest->getData());

        unlink($filename);
        $this->assertFileNotExists($filename);

        $this->manifest->write();

        $this->assertEquals(
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file-1.txt\n" .
            "abababababababababababababababab file-2.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcd file-3.txt\n",
            file_get_contents($this->manifest->getFileName())
        );
    }

    /**
     * Test writing data to the specific file.
     *
     * This test used to make use of access to internal $data, now we read and
     * write, which might reduce the usefulness of this test.
     *
     * @group BagItManifest
     * @covers ::write
     */
    public function testWriteFileName()
    {
        $data = array(
            'file-1.txt' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'file-2.txt' => 'abababababababababababababababababababab',
            'file-3.txt' => 'abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd'
        );
        $file_contents = '';
        foreach ($data as $key => $datum) {
            $file_contents .= "{$datum} {$key}\n";
        }

        $firstFile = "{$this->tmpdir}/test-sha1.txt";

        file_put_contents($firstFile, $file_contents);

        $this->manifest->read($firstFile);

        unlink($firstFile);

        $fileName = "{$this->tmpdir}/writetest-sha1.txt";
        $this->manifest->write($fileName);

        $this->assertEquals(
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa file-1.txt\n" .
            "abababababababababababababababababababab file-2.txt\n" .
            "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcd file-3.txt\n",
            file_get_contents($fileName)
        );
        $this->assertEquals($fileName, $this->manifest->getFileName());
    }

    /**
     * Test getting hashes for individual files using relative file paths.
     * @group BagItManifest
     * @covers ::getHash
     */
    public function testGetHash()
    {
        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $this->manifest->getHash('data/imgs/109x109xcoins1-150x150.jpg')
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $this->manifest->getHash('data/imgs/109x109xprosody.png')
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $this->manifest->getHash('data/imgs/110x108xmetaphor1.png')
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $this->manifest->getHash('data/imgs/fellows1-150x150.png')
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $this->manifest->getHash('data/imgs/fibtriangle-110x110.jpg')
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $this->manifest->getHash('data/imgs/uvalib.png')
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $this->manifest->getHash('data/README.txt')
        );
    }

    /**
     * Test getting hash for missing file.
     * @group BagItManifest
     * @covers ::getHash
     */
    public function testGetHashMissing()
    {
        $this->assertNull($this->manifest->getHash('data/missing'));
    }

    /**
     * Test getting hashes for individual files using absolute file paths.
     * @group BagItManifest
     * @covers ::getHash
     */
    public function testGetHashAbsolute()
    {
        $pre = $this->prefix;

        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $this->manifest->getHash("$pre/data/imgs/109x109xcoins1-150x150.jpg")
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $this->manifest->getHash("$pre/data/imgs/109x109xprosody.png")
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $this->manifest->getHash("$pre/data/imgs/110x108xmetaphor1.png")
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $this->manifest->getHash("$pre/data/imgs/fellows1-150x150.png")
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $this->manifest->getHash("$pre/data/imgs/fibtriangle-110x110.jpg")
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $this->manifest->getHash("$pre/data/imgs/uvalib.png")
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $this->manifest->getHash("$pre/data/README.txt")
        );
    }

    /**
     * Test getting data.
     * @group BagItManifest
     * @covers ::getData
     */
    public function testGetData()
    {
        $data = $this->manifest->getData();

        $this->assertInternalType('array', $data);
        $this->assertEquals(7, count($data));

        $this->assertEquals(
            '547b21e9c710f562d448a6cd7d32f8257b04e561',
            $data['data/imgs/109x109xcoins1-150x150.jpg']
        );
        $this->assertEquals(
            'fba552acae866d24fb143fef0ddb24efc49b097a',
            $data['data/imgs/109x109xprosody.png']
        );
        $this->assertEquals(
            '4beed314513ad81e1f5fad42672a3b1bd3a018ea',
            $data['data/imgs/110x108xmetaphor1.png']
        );
        $this->assertEquals(
            '4372383348c55775966bb1deeeb2b758b197e2a1',
            $data['data/imgs/fellows1-150x150.png']
        );
        $this->assertEquals(
            'b8593e2b3c2fa3756d2b206a90c7259967ff6650',
            $data['data/imgs/fibtriangle-110x110.jpg']
        );
        $this->assertEquals(
            'aec60202453733a976433833c9d408a449f136b3',
            $data['data/imgs/uvalib.png']
        );
        $this->assertEquals(
            '0de174b95ebacc2d91b0839cb2874b2e8f604b98',
            $data['data/README.txt']
        );
    }

    /**
     * Test getFileName
     * @group BagItManifest
     * @covers ::getFileName
     */
    public function testGetFileName()
    {
        $this->assertEquals(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->manifest->getFileName()
        );
    }

    /**
     * Tests getting of file encoding via methods.
     * @group BagItManifest
     * @covers ::getFileEncoding
     */
    public function testGetFileEncoding()
    {
        $this->assertEquals('UTF-8', $this->manifest->getFileEncoding());

        $manifest = new BagItManifest(
            "{$this->tmpdir}/manifest-sha1.txt",
            $this->prefix,
            'ISO-8859-1'
        );
        $this->assertEquals('ISO-8859-1', $manifest->getFileEncoding());
    }

    /**
     * Test setting file encoding via methods.
     * @group BagItManifest
     * @covers ::setFileEncoding
     */
    public function testSetFileEncoding()
    {
        $this->assertEquals('UTF-8', $this->manifest->getFileEncoding());
        $this->manifest->setFileEncoding('ISO-8859-1');
        $this->assertEquals('ISO-8859-1', $this->manifest->getFileEncoding());
    }

    /**
     * Test getting hash encoding set by manifest constructor.
     * @group BagItManifest
     * @covers ::getHashEncoding
     */
    public function testGetHashEncoding()
    {
        $this->assertEquals('sha1', $this->manifest->getHashEncoding());

        $md5 = "{$this->tmpdir}/manifest-md5.txt";
        touch($md5);
        $md5Manifest = new BagItManifest($md5, $this->prefix);
        $this->assertEquals('md5', $md5Manifest->getHashEncoding());
    }

    /**
     * Utility to set hash encoding.
     * @param $hashEncoding
     */
    private function setHashEncodingUtil($hashEncoding)
    {
        $fileName = $this->manifest->getFileName();

        $this->manifest->setHashEncoding($hashEncoding);

        $this->assertEquals($hashEncoding, $this->manifest->getHashEncoding());
        $this->assertEquals(
            "{$this->tmpdir}/manifest-{$hashEncoding}.txt",
            $this->manifest->getFileName()
        );

        if ($fileName != $this->manifest->getFileName()) {
            $this->assertFalse(file_exists($fileName));
        }
        $this->assertFileExists($this->manifest->getFileName());
    }

    /**
     * Test setting all valid hash algorithms.
     * @group BagItManifest
     * @covers ::setHashEncoding
     */
    public function testSetHashEncoding()
    {
        $algorithms = array_keys(BagItManifest::HASH_ALGORITHMS);
        foreach ($algorithms as $algorithm) {
            $this->setHashEncodingUtil($algorithm);
        }
    }

    /**
     * Test setting an invalid hash encoding.
     * @group BagItManifest
     * @covers ::setHashEncoding
     */
    public function testSetHashEncodingERR()
    {
        $this->setHashEncodingUtil('err');
    }

    /**
     * Test internal validation.
     * @group BagItManifest
     * @covers ::validate
     */
    public function testValidateOK()
    {
        $errors = array();
        $this->assertTrue($this->manifest->validate($errors));
        $this->assertCount(0, $errors);
    }

    /**
     * Test validation failure on missing manifest file.
     * @group BagItManifest
     * @covers ::validate
     */
    public function testValidateMissingManifest()
    {
        $manifest = new BagItManifest(
            '/tmp/probably/does/not/exist/missing.txt'
        );

        $errors = array();
        $this->assertFalse($manifest->validate($errors));
        $this->assertTrue(BagItUtils::seenAtKey($errors, 0, 'missing.txt'));
        $this->assertTrue(BagItUtils::seenAtKey($errors, 1, 'missing.txt does not exist.'));
    }

    /**
     * Test validation failure at missing data file.
     * @group BagItManifest
     * @covers ::validate
     */
    public function testValidateMissingData()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        $filename = "{$tmp}/manifest-AAA.txt";

        file_put_contents(
            $filename,
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa data/missing.txt'
        );

        $this->manifest->read($filename);


        $errors = array();
        $this->assertFalse($this->manifest->validate($errors));

        $this->assertTrue(BagItUtils::seenAtKey($errors, 0, 'data/missing.txt'));
        $this->assertTrue(BagItUtils::seenAtKey($errors, 1, 'Missing data file.'));

        BagItUtils::rrmdir($tmp);
    }

    /**
     * Test validation failure on bad checksum.
     * @group BagItManifest
     * @covers ::validate
     */
    public function testValidateChecksum()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        mkdir($tmp . '/data');
        file_put_contents(
            "$tmp/manifest-sha1.txt",
            "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa data/missing.txt\n"
        );

        touch("$tmp/data/missing.txt");

        $manifest = new BagItManifest("$tmp/manifest-sha1.txt", "$tmp/");
        $errors = array();
        $this->assertFalse($manifest->validate($errors));

        $this->assertTrue(BagItUtils::seenAtKey($errors, 0, 'data/missing.txt'));
        $this->assertTrue(BagItUtils::seenAtKey($errors, 1, 'Checksum mismatch.'));

        BagItUtils::rrmdir($tmp);
    }
}
