<?php
namespace Syncro\Exception;

/** Exception to be thrown when settings file doesn't exist or is not valid.
 *
 */

class SettingsException extends SyncroException {

	/** The code for this exception.
	 *
	 *  @var int
	 *
	 */

	protected $code = 1;
}

?>
