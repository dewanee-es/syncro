<?php
namespace Syncro\Exception;

/** Base class por all syncrhonization exceptions.
 *
 */

abstract class SyncroException extends \Exception {
	/** The exception's code. It will be returned to the system when the
	 *  script is run from CLI.
	 *
	 *  @var int
	 *
	 */

	protected $code;

	public function __construct($message, $previous = null) {
		parent::__construct($message, $this->code, $previous);
	}
}
