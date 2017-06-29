<?php
namespace Syncro\Output;

/** This is the base clase for all output writers.
 *
 */
 
abstract class Output {
	
	const NONE = 0;
	const NORMAL = 1;
	const NOTICE = 2;
	const INFO = 3;
	const DEBUG = 4;

	public abstract function message($message, $newline = true);	// NORMAL
	public abstract function notice($message, $newline = true);	// NOTICE
	public abstract function info($message, $newline = true);	// INFO
	public abstract function debug($message, $newline = true);	// DEBUG
	
	public abstract function success($message, $newline = true);	// NORMAL
	public abstract function warn($message, $newline = true);	// NOTICE
	public abstract function error($message, $newline = true);	// NORMAL

	// Highlighted text
	public abstract function highlight($message, $type = null, $level = 'message', $newline = true);	// NORMAL
	
	public abstract function getVerbosity();
	public abstract function setVerbosity($level);
	
}
