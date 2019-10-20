<?php
/**
 * This is a PHP implementation of the {@link
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really,
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for
 * PHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless
 * required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

namespace ScholarsLab\BagIt;

/**
 * This is the main class for interacting with a bag.
 *
 * @category  FileUtils
 * @package   ScholarsLab\BagIt
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @author    Jared Whiklo <jwhiklo@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   Release: 1.0.0
 * @link      https://github.com/whikloj/BagItPHP
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
    private $bag;

    /**
     * Absolute path to the bag directory.
     *
     * @var string
     */
    private $bagDirectory;

    /**
     * True if the bag is extended.
     *
     * @var boolean
     */
    private $extended;

    /**
     * The version information declared in 'bagit.txt'.
     *
     * @var array
     */
    private $bagVersion;

    /**
     * The tag file encoding declared in 'bagit.txt'. Default is 'utf-8'.
     *
     * @var string
     */
    private $tagFileEncoding;

    /**
     * Absolute path to the bagit file.
     *
     * @var string
     */
    private $bagitFile;

    /**
     * Information about the 'manifest-(hash).txt'.
     *
     * Array of one or more BagItManifest objects with hash as key.
     *
     * @var array
     */
    private $manifest = [];

    /**
     * Information about the 'tagmanifest-{hash}.txt'.
     *
     * Array of one or more BagItManifest objects with hash as key.
     *
     * @var array
     */
    private $tagManifest = [];

    /**
     * Information about files that need to be downloaded, listed in fetch.txt.
     *
     * @var \ScholarsLab\BagIt\BagItFetch
     */
    private $fetch;

    /**
     * Absolute path to the 'bag-info.txt' file or null.
     *
     * @var string
     */
    private $bagInfoFile;

    /**
     * A dictionary array containing the 'bag-info.txt' file contents.
     *
     * @var array
     */
    private $bagInfoData;

    /**
     * If the bag came from a compressed file, this contains either 'tgz' or
     * 'zip' to indicate the file's compression format.
     *
     * @var string
     */
    private $bagCompression;

    /**
     * If the bag came from a compressed file, this contains boolean of state.
     *
     * @var bool
     */
    private $bagIsCompressed;

    /**
     * An array of all bag validation errors. Each entries is a two-element
     * array containing the path of the file and the error message.
     *
     * @var array
     */
    private $bagErrors;

    /**
     * The valid algorithms from the current version of PHP filtered to those
     * supported by the BagIt specification. Stored to avoid extraneous calls
     * to hash_algos().
     *
     * @var array
     */
    private $validHashAlgorithms;

    /**
     * The default algorithm to use if one is not specified.
     */
    const DEFAULT_HASH_ALGORITHM = 'sha512';

    /**
     * The default file encoding if one is not specified.
     */
    const DEFAULT_FILE_ENCODING = 'UTF-8';

    /**
     * The default bagit version.
     */
    const DEFAULT_BAGIT_VERSION = array(
        'major' => 1,
        'minor' => 0,
    );

    /**
     * Bag-info fields that MUST not be repeated (in lowercase).
     */
    const BAG_INFO_MUST_NOT_REPEAT = array(
        'payload-oxum'
    );

    /**
     * Reserved element names for Bag-info fields.
     */
    const BAG_INFO_RESERVED_ELEMENTS = array(
        'source-organization',
        'organization-address',
        'contact-name',
        'contact-phone',
        'contact-email',
        'external-description',
        'bagging-date',
        'external-identifier',
        'payload-oxum',
        'bag-size',
        'bag-group-identifier',
        'bag-count',
        'internal-sender-identifier',
        'internal-sender-description',
    );

    //}}}

    //{{{ Public Methods

    /**
     * Define a new BagIt instance.
     *
     * @param string $bag          Either a non-existing folder name (will create
     * a new bag here); an existing folder name (this will treat it as a bag IF bagit.txt exists
     * and create any missing files or folders needed); or an existing
     * compressed file (this will un-compress it to a temporary directory and
     * treat it as a bag).
     * @param boolean $validate    This will validate all files in the bag,
     * including running checksums on all of them. Default is false.
     * @param boolean $extended    This will ensure that optional 'bag-info.txt',
     * 'fetch.txt', and 'tagmanifest-{sha1,md5}.txt' are created. Default is
     * true.
     * @param boolean $fetch       If true, it will download all files in
     * 'fetch.txt'. Default is false.
     * @param array $bagInfoData   If given, this sets the bagInfoData
     * property.
     *
     * @throws \ErrorException     If existing bag is not properly compressed.
     *
     */
    public function __construct(
        $bag,
        $validate = false,
        $extended = true,
        $fetch = false,
        $bagInfoData = null
    ) {
        $this->bag = $bag;
        $this->extended = $extended || (! is_null($bagInfoData));
        $this->bagVersion = self::DEFAULT_BAGIT_VERSION;
        $this->tagFileEncoding = self::DEFAULT_FILE_ENCODING;
        $this->bagDirectory = null;
        $this->bagitFile = null;
        $this->manifest = array();
        $this->tagManifest = array();
        $this->fetch = null;
        $this->bagInfoFile = null;
        $this->bagInfoData = $bagInfoData;
        $this->bagCompression = null;
        $this->bagErrors = array();
        $this->validHashAlgorithms = array_filter(
            hash_algos(),
            array($this, 'filterPhpHashAlgorithms')
        );
        array_walk(
            $this->validHashAlgorithms,
            array($this, 'normalizeHashAlgorithmName')
        );

        if (file_exists($this->bag) &&
            ($this->checkCompressed() || file_exists("{$this->bag}/bagit.txt"))
        ) {
            $this->openBag();
        } else {
            $this->createBag();
        }

        if ($fetch) {
            $this->fetch->download();
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
    public function isExtended()
    {
        return $this->extended;
    }

    /**
     * Return the bag declaration (bagit.txt) values.
     *
     * @return array A dictionary array containing these keys:
     *  - 'version'
     *  - 'version_parts'
     *  - 'encoding'
     *  - 'hash'
     */
    public function getBagInfo()
    {
        $major = $this->bagVersion['major'];
        $minor = $this->bagVersion['minor'];

        $info = array(
            'version'  => "$major.$minor",
            'version_parts' => $this->bagVersion,
            'encoding' => $this->tagFileEncoding,
            'hash'     => implode(",", $this->getHashEncodings()),
        );
        return $info;
    }

    /**
     * Return the main directory of the bag.
     *
     * @return string|null The absolute path to the bag.
     */
    public function getBagDirectory()
    {
        return $this->bagDirectory;
    }

    /**
     * Get the absolute path of the bag's data directory
     *
     * @return string The absolute path to the bag's data directory.
     */
    public function getDataDirectory()
    {
        return "{$this->bagDirectory}/data";
    }

    /**
     * Return information about files to be downloaded or null if none exists.
     *
     * @return \ScholarsLab\BagIt\BagItFetch|null
     */
    public function getFetch()
    {
        return $this->fetch;
    }

    /**
     * Determine hash encoding.
     *
     * @deprecated Only provides one of the potentially numerous hash algorithms.
     * Use getHashEncodings() for an array of all encodings instead.
     *
     * @return string The bag's checksum encoding scheme.
     */
    public function getHashEncoding()
    {
        $encodings = $this->getHashEncodings();
        return reset($encodings);
    }

    /**
     * Return all current hash algorithms.
     *
     * @return array List of hash algorithms in this bag.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function getHashEncodings()
    {
        return array_keys($this->manifest);
    }

    /**
     * Sets the bag's checksum hash algorithm, but removes any existing ones. Use addHashEncoding() to add additional
     * hash encodings.
     *
     * @param string $hashAlgorithm The bag's checksum hash algorithm.
     *
     * @throws \InvalidArgumentException If hash algorithm is not supported.
     */
    public function setHashEncoding($hashAlgorithm)
    {
        $hashAlgorithm = strtolower($hashAlgorithm);
        // Add first to validate the algorithm is valid before removing any.
        $this->addHashEncoding($hashAlgorithm);
        foreach ($this->manifest as $hash => $manifest) {
            if ($hash != $hashAlgorithm) {
                $this->clearManifest($hash);
            }
        }
    }

    /**
     * Remove a hash algorithm from the Bag. Including deleting the manifest
     * and tag-manifest files.
     *
     * @param string $hashAlgorithm the hash algorithm.
     *
     * @throws \ScholarsLab\BagIt\BagItException If you try to remove the only hash encoding.
     *
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function removeHashEncoding($hashAlgorithm)
    {
        $hashAlgorithm = strtolower($hashAlgorithm);
        if ($this->hasHashEncoding($hashAlgorithm)) {
            if (count($this->manifest) == 1) {
                throw new BagItException("Cannot remove the last hash encoding, you must add a new one first.");
            }
            $this->clearManifest($hashAlgorithm);
        }
    }

    /**
     * Add the requested hash algorithm to this bag.
     *
     * @param string $hashAlgorithm The hash algorithm name.
     *
     * @throws \InvalidArgumentException If the hash algorithm is not supported.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function addHashEncoding($hashAlgorithm)
    {
        $hashAlgorithm = strtolower($hashAlgorithm);
        $this->checkSupportedHash($hashAlgorithm);
        if (!$this->hasHashEncoding($hashAlgorithm)) {
            $this->manifest[$hashAlgorithm] = new BagItManifest(
                "{$this->bagDirectory}/manifest-{$hashAlgorithm}.txt",
                $this->bagDirectory . "/",
                $this->tagFileEncoding
            );

            if ($this->isExtended()) {
                $this->tagManifest[$hashAlgorithm] = new BagItManifest(
                    "{$this->bagDirectory}/tagmanifest-{$hashAlgorithm}.txt",
                    $this->bagDirectory . "/",
                    $this->tagFileEncoding
                );
            }
        }
    }

    /**
     * Do we have this hash algorithm already?
     *
     * @param string $hashAlgorithm The requested hash algorithms.
     *
     * @return bool Do we already have this manifest.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function hasHashEncoding($hashAlgorithm)
    {
        return (in_array($hashAlgorithm, array_keys($this->manifest)));
    }

    /**
     * Utility to test a hash algorithm.
     *
     * @param string $hashAlgorithm The requested hash algorithm.
     *
     * @throws \InvalidArgumentException If the requested algorithm is not supported.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function checkSupportedHash($hashAlgorithm)
    {
        if (!$this->isSupportedHash($hashAlgorithm)) {
            throw new \InvalidArgumentException("The hash algorithm " .
                "({$hashAlgorithm}) is not supported on this system.");
        }
    }

    /**
     * Return current file encoding.
     *
     * @return string the file encoding charset.
     */
    public function getFileEncoding()
    {
        return $this->tagFileEncoding;
    }


    /**
     * Return an array of all files in the data directory
     *
     * @return array An array of absolute paths for all of the files in the
     * data directory.
     */
    public function getBagContents()
    {
        return BagItUtils::rls($this->getDataDirectory());
    }

    /**
     * Return errors for a bag
     *
     * @param boolean $validate If true, then it will run this->validate() to
     * verify the integrity first. Default is false.
     *
     * @return array An array of all bag errors.
     */
    public function getBagErrors($validate = false)
    {
        if ($validate) {
            $this->validate();
        }
        return $this->bagErrors;
    }

    /**
     * Runs the bag validator on the contents of the bag. This verifies the
     * presence of required files and folders and verifies the checksum for
     * each file.
     *
     * For the results of validation, check isValid() and getBagErrors().
     *
     * @return array The list of bag errors.
     */
    public function validate()
    {
        $errors = array();

        BagItUtils::validateExists($this->bagitFile, $errors);
        BagItUtils::validateExists($this->getDataDirectory(), $errors);
        foreach ($this->manifest as $hash => $manifest) {
            $manifest->validate($errors);
        }
        $this->validateBagInfo($errors);
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
    public function update()
    {
        // Clear the manifests.
        foreach ($this->manifest as &$manifest) {
            $manifest->clear();
        }
        foreach ($this->tagManifest as &$tagManifest) {
            $tagManifest->clear();
        }

        // Clean up the file names in the data directory.
        $dataFiles = BagItUtils::rls($this->getDataDirectory());
        foreach ($dataFiles as $dataFile) {
            $baseName = basename($dataFile);
            if ($baseName == '.' || $baseName == '..') {
                continue;
            }

            $cleanName = BagItUtils::sanitizeFileName($baseName);
            if ($baseName != $cleanName) {
                $dirName = dirname($dataFile);
                rename($dataFile, "$dirName/$cleanName");
            }
        }

        if ($this->extended || count($this->bagInfoData) > 0) {
            $this->writeBagInfo();
        }

        // Update the manifests.
        foreach ($this->manifest as &$manifest) {
            $manifest->update(BagItUtils::rls($this->getDataDirectory()));
        }

        foreach ($this->tagManifest as &$tagManifest) {
            $bagdir = $this->bagDirectory;

            $tagFiles = array(
                "$bagdir/bagit.txt",
                "$bagdir/bag-info.txt",
                $this->fetch->getFileName(),
            );
            $tagFiles = array_merge($tagFiles, $this->getManifestFileNames());
            $tagManifest->update($tagFiles);
        }
    }

    /**
     * Get all manifest filenames.
     *
     * @return array all manifest filenames.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function getManifestFileNames()
    {
        $fileNames = array();
        foreach ($this->manifest as $manifest) {
            $fileNames[] = $manifest->getFileName();
        }
        return $fileNames;
    }

    /**
     * This copies the file specified into the bag at the place given.
     *
     * $dest should begin with "data/", but if it doesn't that will be added.
     *
     * @param string $src  The file name for the source file.
     * @param string $dest The file name for the destination file. This should
     * be relative to the bag directory.
     *
     * @return void
     */
    public function addFile($src, $dest)
    {
        $dataPref = 'data' . DIRECTORY_SEPARATOR;
        $prefLen = strlen($dataPref);
        if ((strncasecmp($dest, $dataPref, $prefLen) != 0)
            && (strncasecmp($dest, $dataPref, $prefLen) != 0)
        ) {
            $dest = $dataPref . $dest;
        }

        $fulldest = "{$this->bagDirectory}/$dest";
        $dirname = dirname($fulldest);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }

        copy($src, $fulldest);
    }

    /**
     * Create a new file in the bag at $dest, with the contents in $content.
     *
     * $dest should begin with "data/", but if it doesn't that will be added.
     *
     * @param mixed $content the content to write to the file. May be binary
     * data.
     * @param string $dest The file name for the destination file. This should
     * be relative to the bag directory.
     *
     * @throws \ScholarsLab\BagIt\BagitException if the file already exists.
     */
    public function createFile($content, $dest)
    {
        $dataPref = 'data' . DIRECTORY_SEPARATOR;
        $prefLen = strlen($dataPref);
        if ((strncasecmp($dest, $dataPref, $prefLen) != 0)) {
            $dest = $dataPref . $dest;
        }

        $fulldest = "{$this->bagDirectory}/$dest";

        if (file_exists($fulldest)) {
            throw new BagItException("File already exists: '$dest'");
        }

        $dirname = dirname($fulldest);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        file_put_contents($fulldest, $content);
    }


    /**
     * Compresses the bag into a file.
     *
     * @param string $destination The file to put the bag into.
     * @param string $method      Either 'tgz' or 'zip'. Default is 'tgz'.
     *
     * @throws \ScholarsLab\BagIt\BagItException Invalid compression method selected.
     */
    public function package($destination, $method = 'tgz')
    {
        $method = strtolower($method);
        if ($method != 'zip' && $method != 'tgz') {
            throw new BagItException("Invalid compression method: '$method'.");
        }

        if (substr_compare($destination, ".$method", -4, 4, true) != 0) {
            $destination = "$destination.$method";
        }

        BagItUtils::compressBag(
            $this->bagDirectory,
            $destination,
            $method
        );
    }

    /**
     * This tests whether bagInfoData has a key.
     *
     * @param string $key The key to test for existence of.
     * @param bool $caseinsensitive Whether to use a case insensitive lookup for the key.
     *
     * @return bool True if we have
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public function hasBagInfoData($key, $caseinsensitive = false)
    {
        $this->ensureBagInfoData();
        return ($caseinsensitive ? self::arrayKeyExistsNoCase($key, $this->bagInfoData) :
            array_key_exists($key, $this->bagInfoData));
    }

    /**
     * This inserts a value into bagInfoData. Checks for only one of non-repeatable ones.
     *
     * @param string $key   This is the key to insert into the data.
     * @param string $value This is the value to associate with the key.
     *
     * @throws \ScholarsLab\BagIt\BagItException if trying to duplicate a non-repeatable field.
     *
     * @author Eric Rochester <erochest@virginia.edu>
     * @author Jared Whiklo <jwhiklo@gmail.com>
     **/
    public function setBagInfoData($key, $value)
    {
        $this->ensureBagInfoData();
        $this->checkForNonRepeatableBagInfoFields($key);
        $this->bagInfoData[$key] = BagItUtils::getAccumulatedValue(
            $this->bagInfoData,
            $key,
            $value
        );
    }

    /**
     * This removes all the values for a key in the `bag-info.txt` file.
     *
     * @param string $key The key to clear.
     *
     * @return void
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public function clearBagInfoData($key)
    {
        if (array_key_exists($key, $this->bagInfoData)) {
            unset($this->bagInfoData[$key]);
        }
    }

    /**
     * Remove all of the keys and values in the `bag-info.txt` file. Use with
     * caution.
     *
     * @return void
     * @author Michael Joyce <ubermichael@gmail.com>
     */
    public function clearAllBagInfo()
    {
        $this->bagInfoData = array();
    }

    /**
     * Return a list of all keys in the `bag-info.txt` file.
     *
     * @return array array of all bagInfo keys.
     */
    public function getBagInfoKeys()
    {
        $this->ensureBagInfoData();
        return array_keys($this->bagInfoData);
    }

    /**
     * This returns the value for a key from bagInfoData.
     *
     * @param string $key This is the key to get the value associated.
     *
     * @return string|null the BagInfo value or null if key not found.
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    public function getBagInfoData($key)
    {
        $this->ensureBagInfoData();
        return array_key_exists($key, $this->bagInfoData) ? $this->bagInfoData[$key] : null;
    }

    /**
     * Return bag compression state.
     *
     * @return bool Whether the original bag was compressed.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function isCompressed()
    {
        if (!isset($this->bagIsCompressed)) {
            $this->checkCompressed();
        }
        return $this->bagIsCompressed;
    }

    /**
     * Return the compression type.
     *
     * @return string|null The compression type or null if not compressed.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function getCompressionType()
    {
        if (!isset($this->bagCompression)) {
            $this->checkCompressed();
        }
        return $this->bagCompression;
    }

    /**
     * Get all manifest objects in associative array keyed on hash algorithm.
     *
     * @return array the manifests.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function getManifests()
    {
        return $this->manifest;
    }

    /**
     * Get all tag manifest objects in associative array keyed on hash algorithm.
     *
     * @return array the tagManifests.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    public function getTagManifests()
    {
        return $this->tagManifest;
    }

    //}}}

    //{{{ Private Methods

    /**
     * Open an existing bag. This expects $bag to be set.
     *
     * @return void
     * @throws \ErrorException If trying to uncompress a non-compressed bag.
     */
    private function openBag()
    {
        if ($this->checkCompressed()) {
            $this->bagDirectory = BagItUtils::uncompressBag($this->bag);
        } else {
            $this->bagDirectory = realpath($this->bag);
        }

        $this->bagitFile = "{$this->bagDirectory}/bagit.txt";
        if (!file_exists($this->bagitFile)) {
            $this->createDefaultBagItTxt();
        }
        list($version, $fileEncoding, $errors) = BagItUtils::readBagItFile(
            $this->bagitFile
        );
        $this->bagVersion = $version;
        $this->tagFileEncoding = $fileEncoding;
        $this->bagErrors = array_merge($this->bagErrors, $errors);

        $files = scandir($this->bagDirectory);
        if (count($files) > 0) {
            $bagdir = $this->bagDirectory;
            $manifestFiles = BagItUtils::findAllByPattern("$bagdir/manifest-*.txt");
            try {
                if (count($manifestFiles) == 0) {
                    // Set a default.
                    $manifestFiles = array("{$bagdir}/manifest-" . self::DEFAULT_HASH_ALGORITHM . '.txt');
                }
                foreach ($manifestFiles as $manifestFile) {
                    $hash = $this->determineHashFromFilename($manifestFile);
                    $this->manifest[$hash] = new BagItManifest(
                        $manifestFile,
                        $this->bagDirectory . '/',
                        $this->tagFileEncoding
                    );
                }
            } catch (\Exception $exc) {
                array_push(
                    $this->bagErrors,
                    array('manifest', "Error reading $manifestFile.")
                );
            }

            if ($this->isExtended()) {
                $manifestFiles = BagItUtils::findAllByPattern("$bagdir/tagmanifest-*.txt");
                if (count($manifestFiles) == 0) {
                    // If there are no tagmanifest then we must have set a manifest.
                    $manifestFiles = array();
                    foreach (array_keys($this->manifest) as $hash) {
                        $manifestFiles[]="{$bagdir}/tagmanifest-{$hash}.txt";
                    }
                }
                foreach ($manifestFiles as $manifestFile) {
                    $hash = $this->determineHashFromFilename($manifestFile);
                    $this->tagManifest[$hash] = new BagItManifest(
                        $manifestFile,
                        $this->bagDirectory . '/',
                        $this->tagFileEncoding
                    );
                }

                try {
                    $this->fetch = new BagItFetch(
                        "{$this->bagDirectory}/fetch.txt",
                        $this->tagFileEncoding
                    );
                } catch (\Exception $exc) {
                    array_push(
                        $this->bagErrors,
                        array('fetch', 'Error reading fetch file.')
                    );
                }

                $this->bagInfoFile = "{$this->bagDirectory}/bag-info.txt";
                $this->readBagInfo();
            }
        }
    }

    /**
     * Parse manifest/tagmanifest file names to determine hash algorithm.
     *
     * @param string $filepath the filename.
     *
     * @return string|null the hash or null.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    private function determineHashFromFilename($filepath)
    {
        $filename = basename($filepath);
        if (preg_match('~\-(\w+)\.txt$~', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Create a new bag. This expects $bag to be set.
     *
     * @return void
     */
    private function createBag()
    {
        if (!is_dir($this->bag)) {
            mkdir($this->bag);
        }
        $this->bagDirectory = realpath($this->bag);

        $dataDir = $this->getDataDirectory();
        if (!is_dir($dataDir)) {
            mkdir($dataDir);
        }

        $this->bagitFile = $this->bagDirectory . '/bagit.txt';
        $this->manifest = array(
          self::DEFAULT_HASH_ALGORITHM => new BagItManifest(
              "{$this->bagDirectory}/manifest-" . self::DEFAULT_HASH_ALGORITHM . ".txt",
              $this->bagDirectory . '/',
              $this->tagFileEncoding
          ));

        $this->createDefaultBagItTxt();

        $this->createExtendedBag();
    }

    /**
     * This creates the files for an extended bag.
     *
     * @return void
     */
    private function createExtendedBag()
    {
        if ($this->extended) {
            $hashEncoding = $this->getHashEncodings();
            $this->tagManifest = array();
            foreach ($hashEncoding as $hash) {
                $this->tagManifest[$hash] = new BagItManifest(
                    "{$this->bagDirectory}/tagmanifest-{$hash}.txt",
                    $this->bagDirectory . '/',
                    $this->tagFileEncoding
                );
            }


            $fetchFile = $this->bagDirectory . '/fetch.txt';
            $this->fetch = new BagItFetch($fetchFile, $this->tagFileEncoding);

            $this->bagInfoFile = $this->bagDirectory . '/bag-info.txt';
            touch($this->bagInfoFile);
            if (is_null($this->bagInfoData)) {
                $this->bagInfoData = array();
            }
        }
    }

    /**
     * This reads the bag-info.txt file into an array dictionary.
     *
     * @return void
     */
    private function readBagInfo()
    {
        try {
            $lines = BagItUtils::readLines($this->bagInfoFile, $this->tagFileEncoding);
            $this->bagInfoData = BagItUtils::parseBagInfo($lines);
        } catch (\Exception $exc) {
            array_push(
                $this->bagErrors,
                array('baginfo', 'Error reading bag info file.')
            );
        }
    }

    /**
     * This writes the bag-info.txt file with the contents of bagInfoData.
     *
     * @return void
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    private function writeBagInfo()
    {
        $lines = array();

        if (is_array($this->bagInfoData) && count($this->bagInfoData)) {
            foreach ($this->bagInfoData as $label => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $lines[] = "$label: $v\n";
                    }
                } else {
                    $lines[] = "$label: $value\n";
                }
            }
        }

        BagItUtils::writeFileText($this->bagInfoFile, $this->tagFileEncoding, join('', $lines));
    }

    private function createDefaultBagItTxt()
    {
        $major = $this->bagVersion['major'];
        $minor = $this->bagVersion['minor'];
        $bagItData
            = "BagIt-Version: $major.$minor\n" .
            "Tag-File-Character-Encoding: {$this->tagFileEncoding}\n";
        BagItUtils::writeFileText($this->bagitFile, $this->tagFileEncoding, $bagItData);
    }

    /**
     * Tests if a bag is compressed
     *
     * @return bool True if this is a compressed bag.
     */
    private function checkCompressed()
    {
        $compressed = false;
        if (!is_dir($this->bag)) {
            $bag = strtolower($this->bag);
            if (BagItUtils::endsWith($bag, '.zip')) {
                $this->bagCompression = 'zip';
                $compressed = true;
            } elseif (BagItUtils::endsWith($bag, '.tar.gz') || BagItUtils::endsWith($bag, '.tgz')) {
                $this->bagCompression = 'tgz';
                $compressed = true;
            }
        }
        $this->bagIsCompressed = $compressed;
        return $compressed;
    }

    /**
     * This makes sure that bagInfoData is not null.
     *
     * @return void
     * @author Eric Rochester <erochest@virginia.edu>
     **/
    private function ensureBagInfoData()
    {
        if (is_null($this->bagInfoData)) {
            $this->bagInfoData = array();
        }
    }

    /**
     * Normalize a PHP hash algorithm to a BagIt specification name. Used to alter the incoming $item.
     *
     * @param string $item The hash algorithm name.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    private function normalizeHashAlgorithmName(&$item)
    {
        $item = array_flip(BagItManifest::HASH_ALGORITHMS)[$item];
    }

    /**
     * Check if the algorithm PHP has is allowed by the specification.
     *
     * @param string $item A hash algorithm name.
     *
     * @return bool True if allowed by the specification.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    private function filterPhpHashAlgorithms($item)
    {
        return in_array($item, array_values(BagItManifest::HASH_ALGORITHMS));
    }

    /**
     * Is this a supported algorithm?
     *
     * @param string $hashAlgorithm A string representing a hash algorithm.
     *
     * @return bool Whether the algorithm can be used.
     * @author Jared Whiklo <jwhiklo@gmail.com>
     */
    private function isSupportedHash($hashAlgorithm)
    {
        $hashAlgorithm = strtolower($hashAlgorithm);
        return in_array($hashAlgorithm, $this->validHashAlgorithms);
    }

    /**
     * Check that the key is not non-repeatable and already in the bagInfo.
     *
     * @param string $key The key being added.
     *
     * @return void
     * @throws \ScholarsLab\BagIt\BagItException If the key is non-repeatable and already in the bagInfo.
     */
    private function checkForNonRepeatableBagInfoFields($key)
    {
        if (in_array(strtolower($key), self::BAG_INFO_MUST_NOT_REPEAT) &&
            self::arrayKeyExistsNoCase($key, $this->bagInfoData)) {
            throw new BagItException("You cannot add more than one instance of {$key} to the bag-info.txt");
        }
    }

    /**
     * Check for validity of bag-info fields. Adds errors to the $errors array.
     *
     * @param array $errors Array of errors in validation.
     *
     * @return void
     */
    private function validateBagInfo(array &$errors)
    {
        $this->ensureBagInfoData();
        $bagInfoKeys = array_keys($this->bagInfoData);
        array_walk($bagInfoKeys, function (&$item) {
            $item = strtolower($item);
        });
        $countBagInfoKeys = array_count_values($bagInfoKeys);
        foreach (self::BAG_INFO_MUST_NOT_REPEAT as $key) {
            if (array_key_exists(strtolower($key), $countBagInfoKeys) && $countBagInfoKeys[$key] > 1) {
                $errors[] = array(
                    "{$this->getBagDirectory()}/bag-info.txt",
                    "cannot contain more than one of tag {$key}, {$countBagInfoKeys[$key]} found",
                );
            }
        }
    }

    /**
     * Utility to properly remove a manifest/tagmanifest file from the bag.
     *
     * @param string $hashAlgorithm The hash algorithm to remove.
     *
     * @return void
     */
    private function clearManifest($hashAlgorithm)
    {
        if (array_key_exists($hashAlgorithm, $this->manifest)) {
            if (file_exists($this->manifest[$hashAlgorithm]->getFileName())) {
                unlink($this->manifest[$hashAlgorithm]->getFileName());
            }
            unset($this->manifest[$hashAlgorithm]);
        }
        if (array_key_exists($hashAlgorithm, $this->tagManifest)) {
            if (file_exists($this->tagManifest[$hashAlgorithm]->getFileName())) {
                unlink($this->tagManifest[$hashAlgorithm]->getFileName());
            }
            unset($this->tagManifest[$hashAlgorithm]);
        }
    }

    /**
     * Case-insensitive version of array_key_exists
     *
     * @param string $search The key to look for.
     * @param array $map The associative array to search.
     * @return bool True if the key exists regardless of case.
     */
    private static function arrayKeyExistsNoCase($search, array $map)
    {
        $keys = array_keys($map);
        array_walk($keys, function (&$item) {
            $item = strtolower($item);
        });
        return in_array(strtolower($search), $keys);
    }

    //}}}
}
