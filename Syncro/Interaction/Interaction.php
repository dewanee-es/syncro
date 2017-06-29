<?php
namespace Syncro\Interaction;

abstract class Interaction {

	// public abstract function autocompletion($message, $values);
	public abstract function choice($message, $options, $default = null);
	// public abstract function confirm($message);
	// public abstract function hidden($message, $validator = null);
	public abstract function interactive();
	// public abstract function multiple($message, $options, $default = null);
	// public abstract function question($message, $default = null, $validator = null);

}