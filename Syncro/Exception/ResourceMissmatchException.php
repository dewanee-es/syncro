<?php

namespace Syncro\Exception;

/** Exception to be thrown when a file with the same name is a directory in one
 *  of the paths and a file in the other.
 *
 */

class ResourceMissmatchException extends SyncroException {
	
	/** The code for this exception.
	 *
	 *  @var int
	 *
	 */

	protected $code = 3;
}

?>
