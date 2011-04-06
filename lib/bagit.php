<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This is a PHP implementation of the {@link
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really,
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for
 * PHP.
 * 
 * PHP version 5
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless
 * required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 *
 * @category  FileUtils
 * @package   Bagit
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   0.1
 * @link      https://github.com/erochest/BagItPHP
 *
 */


require_once 'Archive/Tar.php';
require_once 'bagit_utils.php';


/**
 * This is a class for all bag exceptions.
 *
 * @category   FileUtils
 * @package    Bagit
 * @subpackage Exception
 * @author     Eric Rochester <erochest@gmail.com>
 * @author     Wayne Graham <wayne.graham@gmail.com>
 * @copyright  2011 The Board and Visitors of the University of Virginia
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version    Release: <package_version>
 * @link       https://github.com/erochest/BagItPHP
 */
class BagItException extends Exception
{

}


/**
 * This is the main class for interacting with a bag.
 *
 * @category  FileUtils
 * @package   Bagit
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   Release: <package_version>
 * @link      https://github.com/erochest/BagItPHP
 */
class BagIt
{

    //{{{ properties

    /**
     * The bag as passed into the constructor. This could be a directory or a
     * file name, and it may not exist.
     *
     * @var string
     */
    var $bag;

    /**
     * Absolute path to the bag directory.
     *
     * @var string
     */
    var $bagDirectory;

    /**
     * True if the bag is extended.
     *
     * @var boolean
     */
    var $extended;

    /**
     * The version information declared in 'bagit.txt'.
     *
     * @var array
     */
    var $bagVersion;

    /**
     * The tag file encoding declared in 'bagit.txt'. Default is 'utf-8'.
     *
     * @var string
     */
    var $tagFileEncoding;

    /**
     * Absolute path to the bagit file.
     *
     * @var string
     */
    var $bagitFile;

    /**
     * Information about the 'manifest-(sha1|md5).txt'.
     *
     * @var BagItManifest
     */
    var $manifest;

    /**
     * Information about the 'tagmanifest-{sha1,md5}.txt' or null.
     *
     * @var BagItManifest
     */
    var $tagManifest;

    /**
     * Absolute path to the 'fetch.txt' file or null.
     *
     * @var string
     */
    var $fetchFile;

    /**
     * Absolute path to the 'bag-info.txt' file or null.
     *
     * @var string
     */
    var $bagInfoFile;

    /**
     * A dictionary array containing the 'fetch.txt' file contents.
     *
     * @var array
     */
    var $fetchData;

    /**
     * A dictionary array containing the 'bag-info.txt' file contents.
     *
     * @var array
     */
    var $bagInfoData;

    /**
     * If the bag came from a compressed file, this contains either 'tgz' or
     * 'zip' to indicate the file's compression format.
     *
     * @var string
     */
    var $bagCompression;

    /**
     * An array of all bag validation errors. Each entries is a two-element
     * array containing the path of the file and the error message.
     *
     * @var array
     */
    var $bagErrors;

    //}}}

    //{{{ Public Methods

    /**
     * Define a new BagIt instance.
     *
     * @param string  $bag      Either a non-existing folder name (will create
     * a new bag here); an existing folder name (this will treat it as a bag
     * and create any missing files or folders needed); or an existing
     * compressed file (this will un-compress it to a temporary directory and
     * treat it as a bag).
     * @param boolean $validate This will validate all files in the bag,
     * including running checksums on all of them. Default is false.
     * @param boolean $extended This will ensure that optional 'bag-info.txt',
     * 'fetch.txt', and 'tagmanifest-{sha1,md5}.txt' are created. Default is
     * true.
     * @param boolean $fetch    If true, it will download all files in
     * 'fetch.txt'. Default is false.
     */
    public function __construct(
        $bag, $validate=false, $extended=true, $fetch=false
    ) {
        $this->bag = $bag;
        $this->extended = $extended;
        $this->bagVersion = array('major' => 0, 'minor' => 96);
        $this->tagFileEncoding = 'UTF-8';
        $this->bagDirectory = null;
        $this->bagitFile = null;
        $this->manifest = null;
        $this->tagManifest = null;
        $this->fetchFile = null;
        $this->bagInfoFile = null;
        $this->fetchData = null;
        $this->bagInfoData = null;
        $this->bagCompression = null;
        $this->bagErrors = array();

        if (file_exists($this->bag)) {
            $this->_openBag();
        } else {
            $this->_createBag();
        }

        if ($fetch) {
            $this->fetch();
        }

        if ($validate) {
            $this->validate();
        }
    }

    /**
     * Test if a Bag is valid
     *
     * @return boolean True if no validation errors occurred.
     */
    public function isValid()
    {
        return (count($this->bagErrors) == 0);
    }

    /**
     * Test if a bag has optional files
     *
     * @return boolean True if the bag contains the optional files
     * 'bag-info.txt', 'fetch.txt', or 'tagmanifest-{sha1,md5}.txt'.
     */
    function isExtended()
    {
        return $this->extended;
    }

    /**
     * Return the info keys
     *
     * @return array A dictionary array containing these keys: 'version',
     * 'encoding', 'hash'.
     */
    function getBagInfo()
    {
        $major = $this->bagVersion['major'];
        $minor = $this->bagVersion['minor'];

        $info = array(
            'version'  => "$major.$minor",
            'encoding' => $this->tagFileEncoding,
            'hash'     => $this->getHashEncoding()
        );
        return $info;
    }

    /**
     * Get the absolute path of the bag's data directory
     *
     * @return string The absolute path to the bag's data directory.
     */
    function getDataDirectory()
    {
        return "{$this->bagDirectory}/data";
    }

    /**
     * Determine hash encoding
     *
     * @return string The bag's checksum encoding scheme.
     */
    function getHashEncoding()
    {
        return $this->manifest->getHashEncoding();
    }

    /**
     * Sets the bag's checksum hash algorithm.
     *
     * @param string $hashAlgorithm The bag's checksum hash algorithm. Must be
     * either 'sha1' or 'md5'.
     *
     * @return void
     */
    function setHashEncoding($hashAlgorithm)
    {
        $hashAlgorithm = strtolower($hashAlgorithm);
        if ($hashAlgorithm != 'md5' && $hashAlgorithm != 'sha1') {
            throw new Exception("Invalid hash algorithim: '$hashAlgorithm'.");
        }

        $this->manifest->setHashEncoding($hashAlgorithm);
        if ($this->tagManifest !== null) {
            $this->manifest->setHashEncoding($hashAlgorithm);
        }
    }

    /**
     * Return an array of all files in the data directory
     *
     * @return array An array of absolute paths for all of the files in the
     * data directory.
     */
    function getBagContents()
    {
        return rls($this->getDataDirectory());
    }

    /**
     * Return errors for a bag
     *
     * @param boolean $validate If true, then it will run this->validate() to
     * verify the integrity first. Default is false.
     *
     * @return array An array of all bag errors.
     */
    function getBagErrors($validate=false)
    {
        if ($validate) {
            $this->validate();
        }
        return $this->bagErrors;
    }

    /**
     * Runs the bag validator on the contents of the bag. This verifies the
     * presence of required iles and folders and verifies the checksum for
     * each file.
     *
     * For the results of validation, check isValid() and getBagErrors().
     *
     * @return array The list of bag errors.
     */
    function validate()
    {
        $errors = array();

        $this->_validateExists($this->bagitFile, $errors);
        $this->_validateExists($this->getDataDirectory(), $errors);
        $this->manifest->validate($errors);

        $this->bagErrors = $errors;
        return $this->bagErrors;
    }

    /**
     * This method is used whenever something is added to or removed from the
     * bag. It performs these steps:
     *
     * <ul>
     * <li>Ensures that required files are present;</li>
     * <li>Sanitizes file names;</li>
     * <li>Makes sure that checksums are up-to-date;</li>
     * <li>Adds checksums and file entries for new files;</li>
     * <li>Removes checksums and file entries for missing files; and</li>
     * <li>If it's an extended bag, makes sure that those files are also
     * up-to-date.</li>
     * </ul>
     *
     * @return void
     */
    function update()
    {
        $this->_clearManifests();
        $this->_cleanDataFileNames();

        $this->_updateManifests();
    }

    /**
     * Downloads every entry in 'fetch.txt'.
     *
     * @param boolean $validate If true, then it also calls update() and
     * validate().
     *
     * @return void
     */
    function fetch($validate=false)
    {
        foreach ($this->fetchData as $fetch) {
            $filename = $this->bagDirectory . '/' . $fetch['filename'];
            if (! file_exists($filename)) {
                $this->_fetchFile($fetch['url'], $filename);
            }
        }

        if ($validate) {
            $this->update();
            $this->validate();
        }
    }

    /**
     * This clears the fetch data.
     *
     * @return void
     */
    function clearFetch()
    {
        $this->fetchData = array();
        writeFileText($this->fetchFile, $this->tagFileEncoding, '');
    }

    /**
     * This adds an entry to the fetch data.
     *
     * @param string $url      This is the URL to load the file from.
     * @param string $filename This is the file name, relative to the bag
     * directory, to save the data to.
     *
     * @return void
     */
    function addFetch($url, $filename)
    {
        array_push(
            $this->fetchData,
            array('url' => $url, 'length' => '-', 'filename' => $filename)
        );
        $this->_writeFetch();
    }

    /**
     * This copies the file specified into the bag at the place given.
     *
     * @param string $src  The file name for the source file.
     * @param string $dest The file name for the destination file. This should 
     * be relative to the bag directory.
     *
     * @return void
     */
    function addFile($src, $dest)
    {
        $fulldest = "{$this->bagDirectory}/$dest";
        $dirname = dirname($fulldest);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        copy($src, $fulldest);
    }


    /**
     * Compresses the bag into a file.
     *
     * @param string $destination The file to put the bag into.
     * @param string $method      Either 'tgz' or 'zip'. Default is 'tgz'.
     *
     * @return void
     */
    function package($destination, $method='tgz')
    {
        $method = strtolower($method);
        if ($method != 'zip' && $method != 'tgz') {
            throw new BagItException("Invalid compression method: '$method'.");
        }

        if (substr_compare($destination, ".$method", -4, 4, true) != 0) {
            $destination = "$destination.$method";
        }

        $package = $this->_compressBag($method);
        rename($package, $destination);
    }
    //}}}

    //{{{ Private Methods


    /**
     * This fetches a single file.
     *
     * On errors, this adds an entry to bagErrors.
     *
     * @param string $url      The URL to fetch.
     * @param string $filename The file name to save to.
     *
     * @return void
     */
    private function _fetchFile($url, $filename)
    {
        $dirname = dirname($filename);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        try {
            saveUrl($url, $filename);
        } catch (Exception $exc) {
            array_push(
                $this->bagErrors,
                array('fetch', "URL $url could down be downloaded.")
            );
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * This cleans up the manifest files.
     *
     * @return void
     */
    private function _clearManifests()
    {
        $this->manifest->clear();
        if ($this->tagManifest !== null) {
            $this->tagManifest->clear();
        }
    }

    /**
     * This updates the manifests' data.
     *
     * @return void
     */
    private function _updateManifests()
    {
        $this->manifest->update(rls($this->getDataDirectory()));
        if ($this->tagManifest !== null) {
            $bagdir = $this->bagDirectory;
            $tagFiles = array(
                "$bagdir/bagit.txt",
                "$bagdir/bag-info.txt",
                "$bagdir/fetch.txt",
                $this->manifest->getFileName()
            );
            $this->tagManifest->update($tagFiles);
        }
    }

    /**
     * This cleans up the file names of all the files in the data/ directory.
     *
     * @return void
     */
    private function _cleanDataFileNames()
    {
        $dataFiles = rls($this->getDataDirectory());
        foreach ($dataFiles as $dataFile) {
            $baseName = basename($dataFile);
            if ($baseName == '.' || $baseName == '..') {
                continue;
            }

            $cleanName = $this->_sanitizeFileName($baseName);
            if ($cleanName === null) {
                unlink($dataFile);
            } else if ($baseName != $cleanName) {
                $dirName = dirname($dataFile);
                rename($dataFile, "$dirName/$cleanName");
            }
        }
    }

    /**
     * This validates that a file or directory exists.
     *
     * @param string $filename The file name to check for.
     * @param array  &$errors  The list of errors to add the message to, if the
     * file doesn't exist.
     *
     * @return boolean True if the file does exist; false otherwise.
     */
    private function _validateExists($filename, &$errors)
    {
        if (! file_exists($filename)) {
            $basename = basename($filename);
            array_push(
                $errors,
                array($basename, "$basename does not exist.")
            );
            return false;
        }
        return true;
    }

    /**
     * Open an existing bag. This expects $bag to be set.
     *
     * @return void
     */
    private function _openBag()
    {
        $this->bagDirectory = ($this->_isCompressed()) ?
            $this->_getCompressedBaseName($this->bag) :
            $this->bagDirectory = realpath($this->bag);

        $this->_readBagIt($this->bagDirectory . '/bagit.txt');

        $files = scandir($this->bagDirectory);
        if (count($files) > 0) {
            $bagdir = $this->bagDirectory;
            $manifestFile = findFirstExisting(
                array("$bagdir/manifest-sha1.txt", "$bagdir/manifest-md5.txt"),
                "$bagdir/manifest-sha1.txt"
            );
            try {
                $this->manifest = new BagItManifest(
                    $manifestFile,
                    $this->bagDirectory . '/',
                    $this->tagFileEncoding
                );
            } catch (Exception $exc) {
                array_push(
                    $this->bagErrors,
                    array('manifest', "Error reading $manifestFile.")
                );
            }

            if ($this->isExtended()) {
                $manifestFile = findFirstExisting(
                    array("$bagdir/tagmanifest-sha1.txt",
                    "$bagdir/tagmanifest-md5.txt"),
                    "$bagdir/tagmanifest-sha1.txt"
                );
                $this->tagManifest = new BagItManifest(
                    $manifestFile,
                    $this->bagDirectory . '/',
                    $this->tagFileEncoding
                );

                $this->_readFetch("{$this->bagDirectory}/fetch.txt");
                $this->_readBagInfo("{$this->bagDirectory}/bag-info.txt");
            }
        }
    }

    /**
     * This returns the base name of a compressed bag.
     *
     * @param string $bag The full bag name.
     *
     * @return string The bag name without the compressed-file extension. This
     * is the bag directory.
     */
    private function _getCompressedBaseName($bag)
    {
        $matches = array();
        $success = preg_match(
            '/^(.*)\.(zip|tar\.gz|tgz)$/',
            basename($bag),
            $matches
        );
        if ($success) {
            $base = $matches[1];
            return $this->_uncompressBag($base);
        } else {
            throw new BagItException(
                "Invalid compressed bag name: $bag."
            );
        }
    }

    /**
     * Create a new bag. This expects $bag to be set.
     *
     * @return void
     */
    private function _createBag()
    {
        mkdir($this->bag);
        $this->bagDirectory = realpath($this->bag);

        mkdir($this->getDataDirectory());

        $this->bagitFile = $this->bagDirectory . '/bagit.txt';
        $this->manifest = new BagItManifest(
            "{$this->bagDirectory}/manifest-sha1.txt",
            $this->bagDirectory . '/',
            $this->tagFileEncoding
        );

        $major = $this->bagVersion['major'];
        $minor = $this->bagVersion['minor'];
        $bagItData
            = "BagIt-Version: $major.$minor\n" .
              "Tag-File-Character-Encoding: {$this->tagFileEncoding}\n";
        writeFileText($this->bagitFile, $this->tagFileEncoding, $bagItData);

        $this->_createExtendedBag();
    }

    /**
     * This creates the files for an extended bag.
     *
     * @return void
     */
    private function _createExtendedBag()
    {
        if ($this->extended) {
            $hashEncoding = $this->getHashEncoding();
            $this->tagManifest = new BagItManifest(
                "{$this->bagDirectory}/tagmanifest-$hashEncoding.txt",
                $this->bagDirectory . '/',
                $this->tagFileEncoding
            );

            $this->fetchFile = $this->bagDirectory . '/fetch.txt';
            touch($this->fetchFile);
            $this->fetchData = array();

            $this->bagInfoFile = $this->bagDirectory . '/bag-info.txt';
            touch($this->bagInfoFile);
            $this->bagInfoData = array();
        }
    }

    /**
     * This reads the fetch.txt file into an array list.
     *
     * This sets $this->fetchData to a sequential array of arrays with the
     * keys 'url', 'length', and 'filename'.
     *
     * @param string $filename If given, this tests whether the file exists,
     * and if it does, it sets the fetchFile parameter before reading the file.
     * If it is set but doesn't exist, then the method returns without reading
     * anything.
     *
     * @return void
     */
    private function _readFetch($filename=null)
    {
        if ($filename !== null) {
            if (file_exists($filename)) {
                $this->fetchFile = $filename;
            } else {
                return;
            }
        }

        try {
            $lines = readLines($this->fetchFile, $this->tagFileEncoding);
            $fetch = array();

            foreach ($lines as $line) {
                $fields = preg_split('/\s+/', $line);
                if (count($fields) == 3) {
                    array_push(
                        $fetch,
                        array('url' => $fields[0],
                              'length' => $fields[1],
                              'filename' => $fields[2])
                    );
                }
            }
            $this->fetchData = $fetch;

        } catch (Exception $exc) {
            array_push(
                $this->bagErrors,
                array('fetch', 'Error reading fetch file.')
            );
        }
    }

    /**
     * This writes the data in fetchData into fetchFile.
     *
     * @return void
     */
    private function _writeFetch()
    {
        $lines = array();

        foreach ($this->fetchData as $fetch) {
            $data = array($fetch['url'], $fetch['length'], $fetch['filename']);
            array_push($lines, join(' ', $data) . "\n");
        }

        writeFileText(
            $this->fetchFile,
            $this->tagFileEncoding,
            join('', $lines)
        );
    }

    /**
     * This reads the bag-info.txt file into an array dictionary.
     *
     * @param string $filename If given, this tests whether the file exists,
     * and if it does, it sets the bagInfoFile parameter before reading the
     * file. If it is set but doesn't exist, then the method returns without
     * reading anything.
     *
     * @return void
     */
    private function _readBagInfo($filename=null)
    {
        if ($filename !== null) {
            if (file_exists($filename)) {
                $this->bagInfoFile = $filename;
            } else {
                return;
            }
        }

        try {
            $lines = readLines($this->bagInfoFile, $this->tagFileEncoding);
            $this->bagInfoData = $this->_parseBagInfo($lines);
        } catch (Exception $exc) {
            array_push(
                $this->bagErrors,
                array('baginfo', 'Error reading bag info file.')
            );
        }
    }

    /**
     * Parse bag info file.
     *
     * @param array $lines An array of lines from the file.
     *
     * @return array The parsed bag-info data.
     */
    private function _parseBagInfo($lines)
    {
        $bagInfo = array();

        $prevKeys = array('');
        foreach ($lines as $line) {
            if (strlen($line) == 0) {
                // Skip.
            } else if ($line[0] == ' ' || $line[1] == '\t') {
                // Continued line.
                $val = $bagInfo[$prevKeys[0]] . ' ' . trim($line);
                foreach ($prevKeys as $pk) {
                    $bagInfo[$pk] = $val;
                }
            } else {
                list($key, $val) = preg_split('/:\s*/', $line, 2);
                $val = trim($val);

                $prevKeys = array($key, strtolower($key), strtoupper($key));
                foreach ($prevKeys as $pk) {
                    $bagInfo[$pk] = $val;
                }
            }
        }

        return $bagInfo;
    }

    /**
     * Tests if a bag is compressed
     *
     * @return True if this is a compressed bag.
     */
    private function _isCompressed()
    {
        if (is_dir($this->bag)) {
            return false;
        } else {
            $bag = strtolower($this->bag);
            if (endsWith($bag, '.zip')) {
                $this->bagCompression = 'zip';
                return true;
            } else if (endsWith($bag, '.tar.gz') || endsWith($bag, '.tgz')) {
                $this->bagCompression = 'tgz';
                return true;
            }
        }
        return false;
    }

    /**
     * This uncompresses a bag.
     *
     * @param string $bagBase The base name for the Bag It directory.
     *
     * @return The bagDirectory.
     */
    private function _uncompressBag($bagBase)
    {
        $dir = tempnam(sys_get_temp_dir(), 'bagit_');
        unlink($dir);
        mkdir($dir, 0700);

        if ($this->bagCompression == 'zip') {
            $zip = new ZipArchive();
            $zip->open($this->bag);
            $zip->extractTo($dir);

        } else if ($this->bagCompression == 'tgz') {
            $tar = new Archive_Tar($this->bag, 'gz');
            $tar->extract($dir);

        } else {
            throw new BagItException(
                "Invalid bag compression format: {$this->bagCompression}."
            );
        }

        return "$dir/$bagBase";
    }

    /**
     * This compresses the bag into a new file.
     *
     * @param string $method Either 'tgz' or 'zip'. Default is 'tgz'.
     *
     * @return string The file name for the file.
     */
    private function _compressBag($method='tgz')
    {
        $output = tempnam(sys_get_temp_dir(), 'bagit_');
        unlink($output);

        $base = basename($this->bagDirectory);
        $stripLen = strlen($this->bagDirectory) - strlen($base);

        if ($method == 'zip') {
            $zip = new ZipArchive();
            $zip->open($output, ZIPARCHIVE::CREATE);

            foreach (rls($this->bagDirectory) as $file) {
                $zip->addFile($file, substr($file, $stripLen));
            }

            $zip->close();

        } else if ($method == 'tgz') {
            $tar = new Archive_Tar($output, 'gz');
            $tar->createModify(
                $this->bagDirectory,
                $base,
                $this->bagDirectory
            );

        }

        return $output;
    }

    /**
     * This reads the information from the bag it file.
     *
     * This sets the bagVersion and tagFileEncoding properties.
     *
     * If it encounters an error, it adds it to bagErrors.
     *
     * @param string $filename If given, this tests whether the file exists,
     * and if it does, it sets the bagitFile parameter before reading the
     * file. If it is set but doesn't exist, then the method returns without
     * reading anything.
     *
     * @return void
     */
    private function _readBagIt($filename=null)
    {
        if ($filename !== null) {
            if (file_exists($filename)) {
                $this->bagitFile = $filename;
            } else {
                return;
            }
        }

        try {
            $this->_parseBagIt(readFileText($filename, $this->tagFileEncoding));
        } catch (Exception $exc) {
            array_push(
                $this->bagErrors,
                array('bagit', 'Error reading the bagit.txt file.')
            );
        }
    }

    /**
     * This parses information from the bagit.txt from the string data read 
     * from that file.
     *
     * @param string $data The data from the bagit.txt file.
     *
     * @return void
     */
    private function _parseBagIt($data)
    {
        $versions = $this->_parseVersionString($data);
        if ($versions === null) {
            throw new Exception();
        }
        $this->bagVersion = $versions;

        $this->tagFileEncoding = $this->_parseEncodingString($data);
    }

    /**
     * This parses the version string from the bagit.txt file.
     *
     * @param string $bagitFileData The contents of the bagit file.
     *
     * @return array A two-item array containing the version string as
     * integers. The keys for this array are 'major' and 'minor'.
     */
    private function _parseVersionString($bagitFileData)
    {
        $matches = array();
        $success = preg_match(
            "/BagIt-Version: (\d+)\.(\d+)/i",
            $bagitFileData,
            $matches
        );

        if ($success) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            if ($major === null || $minor === null) {
                throw new Exception("Invalid bagit version: '{$matches[0]}'.");
            }
            return array('major' => $major, 'minor' => $minor);
        }

        return null;
    }

    /**
     * This parses the encoding string from the bagit.txt file.
     *
     * @param string $bagitFileData The contents of the bagit file.
     *
     * @return string The encoding.
     */
    private function _parseEncodingString($bagitFileData)
    {
        $matches = array();
        $success = preg_match(
            '/Tag-File-Character-Encoding: (.*)/i',
            $bagitFileData,
            $matches
        );

        if ($success) {
            return $matches[1];
        }

        return null;
    }

    /**
     * This cleans up the file name.
     *
     * @param string $filename The file name to clean up.
     *
     * @return string The cleaned up file name.
     */
    private function _sanitizeFileName($filename)
    {
        // White space => underscores.
        $filename = preg_replace('/\s+/', '_', $filename);

        // Remove some characters.
        $filename = preg_replace(
            '/\.{2}|[~\^@!#%&\*\/:\'?\"<>\|]/',
            '',
            $filename
        );

        $forbidden = '/^(CON|PRN|AUX|NUL|COM1|COM2|COM3|COM4|COM5| ' .
            'COM6|COM7|COM8|COM9|LPT1|LPT2|LPT3|LPT4|LPT5|LPT6|' .
            'LPT7|LPT8|LPT9)$/';

        if (preg_match($forbidden, $filename)) {
            $filename = strtolower($filename);
            $suffix = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 12);
            $filename = "{$filename}_{$suffix}";
        }

        return $filename;
    }
    //}}}

}

/* Functional wrappers/facades. */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


?>
