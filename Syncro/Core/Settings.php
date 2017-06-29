<?php
namespace Syncro\Core;

use Syncro\Exception\SettingsException;
use Syncro\Options\OptionsInterface;
use Syncro\Output\Output;

class Settings implements OptionsInterface {
	
	private $settings;
	private $output;
	private $interaction;
	private $endTime;
	
	public function __construct($settings = null, $output = null, $interaction = null) {
		if(is_null($settings)) {
			$this->settings = (object) array();
			return;
		}
	
		$this->settings = json_decode($settings);
		
		if(empty($this->settings)) {
			switch(json_last_error()) {
	        case JSON_ERROR_NONE:
	            $error = 'No errors?';
			    break;
		    case JSON_ERROR_DEPTH:
		        $error = 'Maximum stack depth exceeded';
			    break;
		    case JSON_ERROR_STATE_MISMATCH:
		        $error = 'Underflow or the modes mismatch';
			    break;
		    case JSON_ERROR_CTRL_CHAR:
		        $error = 'Unexpected control character found';
			    break;
		    case JSON_ERROR_SYNTAX:
		        $error = 'Syntax error, malformed JSON';
			    break;
		    case JSON_ERROR_UTF8:
		    	$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
			    break;
		    case JSON_ERROR_RECURSION:
		    	$error = 'One or more recursive references in the value to be encoded';
			    break;
		    case JSON_ERROR_INF_OR_NAN:
		    	$error = 'One or more NAN or INF values in the value to be encoded';
			    break;
		    case JSON_ERROR_UNSUPPORTED_TYPE:
		    	$error = 'A value of a type that cannot be encoded was given';
			    break;
		    default:
		        $error = 'Unknown error';
			    break;
			}
			throw new SettingsException("Settings are empty or invalid JSON file ($error)");
		}
		
		if($this->getDebug()) {
			$msg = ($output->getVerbosity() < Output::DEBUG);
			$output->setVerbosity(Output::DEBUG);
			if($msg) {
				$output->debug("Loading settings...");
			}
		}
		
		$this->output = $output;
		$this->interaction = $interaction;

		$output->debug("Trying to retrieve data from the last synchronization...");
		
		if($this->getTime()) {
			$time = $this->getTime();
			$regex = "/^[0-9]+$/";
			
			if(!preg_match($regex, $time)) {
				$output->warn("WARNING: Last synchronization time does not have the right format.");
				$output->warn("Asuming the paths have never been synchronized before.");
				$this->setTime(0);
			}
		} else {
			$output->warn("WARNING: Could not find data from last synchronization, asuming the paths have never been synchronized");
		}
	}
	
	/** Merge other setting objetct with existing values in this object.
	 *  Values in $settings object overrides values in this object
	 */
	public function merge(Settings $settings) {
		foreach((array) $settings->settings as $setting => $value) {
			if(!empty($setting)) {
				$this->settings->$setting = $value;
			}
		}
	}
	
	public function getChecksum() {
		return isset($this->settings->checksum) ? $this->settings->checksum : false;
	}
	
	public function getDebug() {
		return isset($this->settings->debug) ? $this->settings->debug : false;
	}
	
	public function getEndTime() {
		return $this->endTime;
	}
	
	public function getExcludes() {
		return isset($this->settings->excludes) ? (is_array($this->settings->excludes) ? $this->settings->excludes : [$this->settings->excludes]) : [];
	}
	
	public function getFat() {
		return isset($this->settings->fat) ? $this->settings->fat : false;
	}
	
	public function getHidden() {
		return isset($this->settings->hidden) ? $this->settings->hidden : true;
	}
	
	public function getInteraction() {
		return $this->interaction;
	}
	
	public function getOutput() {
		return $this->output;
	}
	
	public function getMode() {
		return isset($this->settings->mode) ? $this->settings->mode : 'sync';
	}
	
	public function getName() {
		return isset($this->settings->name) ? $this->settings->name : null;
	}
	
	public function getPreserve() {
		return isset($this->settings->preserve) ? $this->settings->preserve : false;
	}
	
	public function getSettings() {
		return json_encode($this->settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	
	public function getSimulate() {
		return isset($this->settings->simulate) ? $this->settings->simulate : false;
	}
	
	public function getSize() {
		return isset($this->settings->size) ? $this->settings->size : 0;
	}
	
	public function getSource() {
		return isset($this->settings->source) ? $this->settings->source : null;
	}
	
	public function getStartTime() {
		return $this->getTime();
	}
	
	public function getTarget() {
		return isset($this->settings->target) ? $this->settings->target : null;
	}
	
	public function getTime() {
		return isset($this->settings->time) ? $this->settings->time : 0;
	}
	
	public function setChecksum($checksum) {
		$this->settings->checksum = $checksum;
	}
	
	public function setDebug($debug) {
		$this->settings->debug = $debug;
	}
	
	public function setEndTime($endTime) {
		$this->endTime = $endTime;
	}
	
	public function setExcludes($excludes) {
		$this->settings->excludes = $excludes;
	}
	
	public function setFat($fat) {
		$this->settings->fat = $fat;
	}
	
	public function setHidden($hidden) {
		$this->settings->hidden = $hidden;
	}
	
	public function setMode($mode) {
		$this->settings->mode = $mode;
	}
	
	public function setName($name) {
		$this->settings->name = $name;
	}
	
	public function setPreserve($preserve) {
		$this->settings->preserve = $preserve;
	}
	
	public function setSimulate($simulate) {
		$this->settings->simulate = $simulate;
	}
	
	public function setSize($size) {
		$this->settings->size = $size;
	}
	
	public function setSource($source) {
		$this->settings->source = $source;
	}
	
	public function setStartTime($startTime) {
		$this->setTime($startTime);
	}
	
	public function setTarget($target) {
		$this->settings->target = $target;
	}
	
	public function setTime($time) {
		$this->settings->time = $time;
	}
	
}
