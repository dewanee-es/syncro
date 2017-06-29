<?php

namespace Syncro\Exception;

/** Exception to be thrown when the application fails to delete a file from one
 *  of the paths.
 *
 */

class DirectoryRemoveException extends SyncroException {
	
	/** The code for this exception.
	 *
	 *  @var int
	 *
	 */

	protected $code = 6;
}

?>
