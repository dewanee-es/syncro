<?php

namespace Syncro\Exception;

/** Exception to be thrown when the copy of a file from one path to the other
 *  fails.
 *
 */

class FileCopyException extends SyncroException {
	
	/** The code for this exception.
	 *
	 *  @var int
	 *
	 */

	protected $code = 4;
}

?>
