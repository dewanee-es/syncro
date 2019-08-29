<?php
namespace Syncro\Options;

/**
 * The Options interface
 *
 */
interface OptionsInterface {

	public function getChecksum();
	
	public function getDebug();
	
	public function getEndTime();
	
	public function getExcludes();
	
	public function getFat();
	
	public function getFolders();
	
	public function getHidden();
	
	public function getMode();
	
	public function getName();
	
	public function getPreserve();
	
	public function getSimulate();
	
	public function getSize();
	
	public function getSource();
	
	public function getStartTime();
	
	public function getTarget();
	
	public function getTime();
	
	public function setChecksum($checksum);
	
	public function setDebug($debug);
	
	public function setEndTime($endTime);
	
	public function setExcludes($excludes);
	
	public function setFat($fat);
	
	public function setFolders($folders);
	
	public function setHidden($hidden);
	
	public function setMode($mode);
	
	public function setName($name);
	
	public function setPreserve($preserve);
	
	public function setSimulate($simulate);
	
	public function setSize($size);
	
	public function setSource($source);
	
	public function setStartTime($startTime);
	
	public function setTarget($target);
	
}
