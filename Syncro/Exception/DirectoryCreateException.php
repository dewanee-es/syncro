<?php

namespace Syncro\Exception;

/** Exception to be thrown when the application fails to create a directory in
 *  one of the paths.
 *
 */

class DirectoryCreateException extends SyncroException {

	/** The code for this exception.
	 *
	 *  @var int
	 *
	 */

	protected $code = 7;
}

?>
