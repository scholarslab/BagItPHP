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
 *
 */

namespace ScholarsLab\BagIt;

/**
 * This is a class for all bag exceptions.
 *
 * @category   FileUtils
 * @package    ScholarsLab\BagIt
 * @author     Eric Rochester <erochest@gmail.com>
 * @author     Wayne Graham <wayne.graham@gmail.com>
 * @author     Jared Whiklo <jwhiklo@gmail.com>
 * @copyright  2011 The Board and Visitors of the University of Virginia
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version    Release: 1.0.0
 * @link       https://github.com/ScholarsLab/BagItPHP
 */
class BagItException extends \Exception
{

}
