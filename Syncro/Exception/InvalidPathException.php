<?php

namespace Syncro\Exception;

/** Exception to be thrown when something gets wrong with the syncrhoniation
 *  paths
 *
 */

class InvalidPathException extends SyncroException
{
	/** The code for this exception.
	 *
	 *  @var int
	 *
	 */

	protected $code = 2;
}

?>
