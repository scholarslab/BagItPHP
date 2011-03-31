<?php

/**
 * This is a PHP implementation of the {@link 
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really, 
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for 
 * PHP.
 *
 * @package bagit
 * @author Eric Rochester (erochest@gmail.com)
 * @copyright 2011
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version 0.1
 * @link https://github.com/erochest/BagItPHP
 */


include 'Archive/Tar.php';


/**
 * This is a class for all bag exceptions.
 * @package bagit
 */
class BagItException extends Exception {
}

/**
 * This filters an array by items that match a regex.
 * @param string $regex The regex to filter by.
 * @param array $list The list of items to filter.
 * @return The match objects for items from $list that match $regex.
 */
function filterArrayMatches($regex, $list) {
    $ret = array();

    foreach ($list as $item) {
        $matches = array();
        if (preg_match($regex, $item, $matches)) {
            array_push($ret, $matches);
        }
    }

    return $ret;
}

/**
 * This tests whether a string ends with another string.
 * @param string $main The primary string to test.
 * @param string $suffix The string to test against the end of the other.
 * @return True if $suffix occurs at the end of $main.
 */
function endsWith($main, $suffix) {
    $len = strlen($suffix);
    return substr_compare($main, $suffix, -$len, $len) === 0;
}

/**
 * This is the main class for interacting with a bag.
 * @package bagit
 */
class BagIt {

    /**
     * The bag as passed into the constructor. This could be a directory or a 
     * file name, and it may not exist.
     * @var string
     */
    var $bag;

    /**
     * Absolute path to the bag directory.
     * @var string
     */
    var $bagDirectory;

    /**
     * True if the bag is extended.
     * @var boolean
     */
    var $extended;

    /**
     * 'sha1' or 'md5'. Default is 'sha1'.
     * @var string
     */
    var $hashEncoding;

    /**
     * The major version number declared in 'bagit.txt'. Default is '0'.
     * @var string
     */
    var $bagMajorVersion;

    /**
     * The minor version number declared in 'bagit.txt'. Default is '96'.
     * @var string
     */
    var $bagMinorVersion;

    /**
     * The tag file encoding declared in 'bagit.txt'. Default is 'utf-8'.
     * @var string
     */
    var $tagFileEncoding;

    /**
     * Absolute path to the data directory.
     * @var string
     */
    var $dataDirectory;

    /**
     * Absolute path to the bagit file.
     * @var string
     */
    var $bagitFile;

    /**
     * Absolute path to the 'manifest-{sha1,md5}.txt' file.
     * @var string
     */
    var $manifestFile;

    /**
     * Absolute path to the 'tagmanifest-{sha1,md5}.txt' file or null.
     * @var string
     */
    var $tagManifestFile;

    /**
     * Absolute path to the 'fetch.txt' file or null.
     * @var string
     */
    var $fetchFile;

    /**
     * Absolute path to the 'bag-info.txt' file or null.
     * @var string
     */
    var $bagInfoFile;

    /**
     * A dictionary array containing the manifest file contents.
     * @var array
     */
    var $manifestContents;

    /**
     * A dictionary array containing the tagmanifest file contents.
     * @var array
     */
    var $tagManifestContents;

    /**
     * A dictionary array containing the 'fetch.txt' file contents.
     * @var array
     */
    var $fetchContents;

    /**
     * A dictionary array containing the 'bag-info.txt' file contents.
     * @var array
     */
    var $bagInfoContents;

    /**
     * If the bag came from a compressed file, this contains either 'tgz' or 
     * 'zip' to indicate the file's compression format.
     * @var string
     */
    var $bagCompression;

    /**
     * An array of all bag validation errors. Each entries is a two-element array 
     * containing the path of the file and the error message.
     * @var array
     */
    var $bagErrors;

    /**
     * Define a new BagIt instance.
     *
     * @param string $bag Either a non-existing folder name (will create a new 
     * bag here); an existing folder name (this will treat it as a bag and 
     * create any missing files or folders needed); or an existing compressed 
     * file (this will un-compress it to a temporary directory and treat it as 
     * a bag).
     *
     * @param boolean $validate This will validate all files in the bag, 
     * including running checksums on all of them. Default is false.
     *
     * @param boolean $extended This will ensure that optional 'bag-info.txt', 
     * 'fetch.txt', and 'tagmanifest-{sha1,md5}.txt' are created. Default is 
     * true.
     *
     * @param boolean $fetch If true, it will download all files in 
     * 'fetch.txt'. Default is false.
     */
    public function BagIt($bag, $validate=false, $extended=true, $fetch=false) {
        $this->bag = $bag;
        $this->extended = $extended;
        $this->hashEncoding = 'sha1';
        $this->bagMajorVersion = 0;
        $this->bagMinorVersion = 96;
        $this->tagFileEncoding = 'UTF-8';
        $this->dataDirectory = null;
        $this->bagDirectory = null;
        $this->bagitFile = null;
        $this->manifestFile = null;
        $this->tagManifestFile = null;
        $this->fetchFile = null;
        $this->bagInfoFile = null;
        $this->manifestContents = null;
        $this->tagManifestContents = null;
        $this->fetchContents = null;
        $this->bagInfoContents = null;
        $this->bagCompression = null;
        $this->bagErrors = array();

        if (file_exists($this->bag)) {
            $this->openBag();
            return;
        } else {
            $this->createBag();
        }

        if ($validate) {
            $this->validate();
        }
    }

    /**
     * @return boolean True if no validation errors occurred.
     */
    public function isValid() {
        return (count($this->bagErrors) == 0);
    }

    /**
     * @return boolean True if the bag contains the optional files 
     * 'bag-info.txt', 'fetch.txt', or 'tagmanifest-{sha1,md5}.txt'.
     */
    function isExtended() {
    }

    /**
     * @return array A dictionary array containing these keys: 'version', 
     * 'encoding', 'hash'.
     */
    function getBagInfo() {
    }

    /**
     * @return string The absolute path to the bag's data directory.
     */
    function getDataDirectory() {
    }

    /**
     * @return string The bag's checksum encoding scheme.
     */
    function getHashEncoding() {
    }

    /**
     * Sets the bag's checksum hash algorithm.
     * @param string $hashAlgorithm The bag's checksum hash algorithm. Must be 
     * either 'sha1' or 'md5'.
     */
    function setHashEncoding($hashAlgorithm) {
    }

    /**
     * Prints a number of bag properties.
     */
    function showBagInfo() {
    }

    /**
     * @return array An array of absolute paths for all of the files in the data 
     * directory.
     */
    function getBagContents() {
    }

    /**
     * @param boolean $validate If true, then it will run this->validate() to 
     * verify the integrity first. Default is false.
     * @return array An array of all bag errors.
     */
    function getBagErrors($validate=false) {
    }

    /**
     * Runs the bag validator on the contents of the bag. This verifies the presence of required 
     * files and folders and verifies the checksum for each file.
     *
     * For the results of validation, check isValid() and getBagErrors().
     */
    function validate() {
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
     */
    function update() {
    }

    /**
     * Downloads every entry in 'fetch.txt'.
     * @param boolean $validate If true, then it also calls update() and 
     * validate().
     */
    function fetch($validate=false) {
    }

    /**
     * Writes new entries in 'fetch.txt'.
     *
     * @param array $fetchEntries An array containing the URL and path relative to 
     * the data directory for file.
     * @param boolean $append If false, the current entries in 'fetch.txt' will 
     * be overwritten. Default is true.
     */
    function addFetchEntries($fetchEntries, $append=true) {
    }

    /**
     * Compresses the bag into a file.
     *
     * @param string $destination The file to put the bag into.
     * @param string $method Either 'tgz' or 'zip'. Default is 'tgz'.
     */
    function package($destination, $method) {
    }

    /**
     * Open an existing bag. This expects $bag to be set.
     */
    private function openBag() {
        if ($this->isCompressed()) {
            $matches = array();
            if (preg_match('/^(.*)\.(zip|tar\.gz|tgz)/', $this->bag, $matches)) {
                $base = $matches[1];
                $this->bagDirectory = $this->uncompressBag($base);
            } else {
                throw new BagItException(
                    "Invalid compressed bag name: {$this->bag}."
                );
            }
        } else {
            $this->bagDirectory = realpath($this->bag);
        }

        try {
            $this->bagitFile = $this->bagDirectory . '/bagit.txt';
            $bFileContents = iconv(
                $this->tagFileEncoding,
                'UTF-8',
                file_get_contents($this->bagitFile)
            );

            $versions = $this->parseVersionString($bFileContents);
            $this->bagMajorVersion = $versions[0];
            $this->bagMinorVersion = $versions[1];

            $this->tagFileEncoding = $this->parseEncodingString($bFileContents);

        } catch (Exception $e) {
            array_push(
                $this->bagErrors,
                array('bagit', 'Error reading the bagit.txt file.')
            );
        }

        $ls = scandir($this->bagDirectory);
        if (count($ls) > 0) {
            $manifests = filterArrayMatches('/^manifest-(sha1|md5)\.txt$/', $ls);
            if (count($manifests) > 0) {
                $this->hashEncoding = strtolower($manifests[0][1]);
                $this->manifestFile = "{$this->bagDirectory}/{$manifests[0][0]}";
                $this->readManifestToArray();
            }

            $this->dataDirectory = "{$this->bagDirectory}/data";

            $manifests = filterArrayMatches(
                '/^tagmanifest-(sha1|md5)\.txt$/',
                $ls
            );
            if (count($manifests) > 0) {
                $this->tagManifestFile = "{$this->bagDirectory}/{$manifests[0][0]}";
                $this->readManifestToArray('t');
            }

            if (file_exists("{$this->bagDirectory}/fetch.txt")) {
                $this->fetchFile = "{$this->bagDirectory}/fetch.txt";
                $this->readFetchToArray();
            }

            if (file_exists("{$this->bagDirectory}/bag-info.txt")) {
                $this->bagInfoFile = "{$this->bagDirectory}/bag-info.txt";
                $this->readBagInfoToArray();
            }
        }
    }

    /**
     * Create a new bag. This expects $bag to be set.
     */
    private function createBag() {
        $cwd = getcwd();

        $this->bagDirectory = realpath($this->bag);
        mkdir($this->bagDirectory);

        $this->dataDirectory = $this->bagDirectory . '/data';
        mkdir($this->dataDirectory);

        $versionId = "BagIt-Version: {$this->bagMajorVersion}.{$this->bagMinorVersion}\n";
        $encoding = "Tag-File-Character-Encoding: {$this->tagFileEncoding}\n";

        $this->bagitFile = $this->bagDirectory . '/bagit.txt';
        $this->manifestFile = $this->bagDirectory .
            "/manifest-{$this->hashEncoding}.txt";

        file_put_contents(
            $this->bagitFile,
            iconv('UTF-8', $this->tagFileEncoding, $versionId . $encoding)
        );

        touch($this->manifestFile);
        $this->readManifestToArray();

        if ($this->extended) {
            $this->tagManifestFile = $this->bagDirectory .
                "tagmanifest-{$this->hashEncoding}.txt";

            touch($this->tagManifestFile);
            $this->readManifestToArray('t');

            $this->fetchFile = $this->bagDirectory . '/fetch.txt';
            touch($this->fetchFile);
            $this->readFetchToArray();

            $this->bagInfoFile = $this->bagDirectory . '/bag-info.txt';
            touch($this->bagInfoFile);
            $this->readBagInfoToArray();
        }

    }

    /**
     * This reads the manifest file into manifestContents.
     * @param string $mode The type of manifest to read. <code>t</code> means 
     * reading a tagmanifest file.
     */
    private function readManifestToArray($mode='') {
        $prefix = $mode == 't' ? 'tag' : '';

        array_push(
            $this->bagErrors,
            array('manifest', "Error reading {$prefix}manifest file.")
        );
    }

    /**
     * This reads the fetch.txt file into an array list.
     */
    private function readFetchToArray() {
        array_push(
            $this->bagErrors,
            array('fetch', 'Error reading fetch file.')
        );
    }

    /**
     * This reads the bag-info.txt file into an array dictionary.
     */
    private function readBagInfoToArray() {
        array_push(
            $this->bagErrors,
            array('baginfo', 'Error reading bag info file.')
        );
    }

    /**
     * @return True if this is a compressed bag.
     */
    private function isCompressed() {
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
     * @param string $bagBase The base name for the Bag It directory.
     * @return The bagDirectory.
     */
    private function uncompressBag($bagBase) {
        $dir = tempnam(sys_get_temp_dir(), 'bagit_');
        unlink($dir);
        mkdir($dir, 0700);

        $dir = "{$dir}/{$bagBase}";

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

        return $dir;
    }

    /**
     * This parses the version string from the bagit.txt file.
     * @param string $bagitFileContents The contents of the bagit file.
     * @return A two-item array containing the version string as integers.
     */
    private function parseVersionString($bagitFileContents) {
        $matches = array();
        $success = preg_match(
            '/BagIt-Version: (\d+)\.(\d+)/i',
            $bagitFileContents,
            $matches
        );

        if ($success) {
            return array((int)$matches[1], (int)$matches[2]);
        }
    }

    /**
     * This parses the encoding string from the bagit.txt file.
     * @param string $bagitFileContents The contents of the bagit file.
     * @return The encoding.
     */
    private function parseEncodingString($bagitFileContents) {
        $matches = array();
        $success = preg_match(
            '/Tag-File-Character-Encoding: (.*)/i',
            $bagitFileContents,
            $matches
        );

        if ($success && count($matches) > 0) {
            return $matches[0][1];
        }
    }

}

/* Functional wrappers/facades. */

?>
