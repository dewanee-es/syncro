<?php
namespace Syncro\Synchronizer\File;

use Syncro\Exception\InvalidPathException;
use Syncro\Synchronizer\SynchronizerSourceInterface;
use Syncro\Synchronizer\SynchronizerTargetInterface;

class FileSynchronizerPath implements SynchronizerSourceInterface, SynchronizerTargetInterface {

	protected $name;
	protected $path;
	protected $relative;
	protected $filename;
	protected $status;
	protected $modifyTime = -1;
	protected $dir;
	protected $exists;
	protected $checksum;
	protected $files;
	
	public function __construct($settings) {
		if(is_object($settings)) {
			$this->name = isset($settings->name) ? $settings->name : null;
			$this->path = isset($settings->path) ? $settings->path : null;
		} else {
			$this->path = (string) $settings;
		}
		
		$this->name = $this->name ? $this->name : basename($this->path);
		$this->path = $this->realpath($this->path);
	}
	
	public function check() {
		$paths = glob($this->path, GLOB_ONLYDIR);
		
		if($paths === false || count($paths) == 0) {
			throw new InvalidPathException("The path '" . $this->path . "' is not accessible or is not a directory.");
		} else if(count($paths) > 1) {
			throw new InvalidPathException("There are more than one directory matching the path '" . $this->path . "'.");
		} else {
			$this->path = $paths[0];
		}
	}
	
	public function checksum($calculate = true) {
		if(!$this->checksum && $calculate && $this->exists()) {
			$this->checksum = @sha1_file($this->path);
		}
		
		return $this->checksum;
	}

	public function copy($target) {
		// Check integrity on copy
		if($this->getRelativePath() != $target->getRelativePath()) {
			return false;
		}

		$result = copy($this->getPath(), $target->getPath());
		$modifyTime = $this->modifyTime();
		
		if($result && $modifyTime > 0) {
			@touch($target->getPath(), $modifyTime);
		}
		
		return $result;
	}

	public function exists() {
		if(is_null($this->exists)) {
			$this->exists = file_exists($this->path);
		}
		
		return $this->exists;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getPath() {
		return $this->path;
	}

	public function getRelativeFilename() {
		return $this->filename;
	}
	
	public function getRelativeName() {
		return $this->name . ($this->relative ? '/' . $this->relative : '');
	}
	
	public function getRelativePath() {
		return $this->relative;
	}
	
	public function getStatus() {
		return $this->status;
	}
	
	public function getType() {
		return ($this->isDir() ? 'dir' : 'file');
	}
	
	public function isDir() {
		if(is_null($this->dir) && $this->exists()) {
			$this->dir = is_dir($this->path);
		}
		
		return $this->dir;
	}
	
	public function makeDir($modifyTime = 0) {
		$result = @mkdir($this->getPath());
		
		if($result && $modifyTime > 0) {
			@touch($this->getPath(), $modifyTime);
		}
		
		return $result;
	}
	
	public function modifyTime($calculate = false) {
		if($calculate && $this->modifyTime >= 0) {
			clearstatcache(true, $this->getPath());
			$this->modifyTime = -1;
		}
		
		if($this->modifyTime < 0) {
			$time = 0;
			
			if($this->exists()) {
				$stat = stat($this->path);

				if($stat) {
					$mtime = $stat["mtime"];
					$ctime = $stat["ctime"];
					$time = max($mtime, $ctime);
				}
			}
			
			$this->modifyTime = $time;
		}
		
		return $this->modifyTime;
	}
	
	public function nextFile() {
		$file = current($this->files);
		
		if($file) {
			next($this->files);
			return $file;
		}
		
		return null;
	}
	
	public function newPath($path) {
		$newPath = new static((object) ['name' => $this->name, 'path' => $this->path . '/' . $path]);
		$newPath->relative = ltrim($this->relative . '/' . $path, '/');
		$newPath->filename = ltrim($this->filename . '/' . $path, '/');
		return $newPath;
	}
	
	private function realpath($path) {
		if(strpos($path, '~') === 0) {
			$home = isset($_SERVER['HOME']) ? $_SERVER['HOME'] : $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
			$path = $home . substr($path, 1);
		}
		
		return $path;
	}
	
	public function remove() {
		return @unlink($this->getPath());
	}
	
	public function removeDir() {
		return @rmdir($this->getPath());
	}
	
	public function scan() {
		$this->files = scandir($this->path);
		return $this->files;
	}
	
	public function size() {
		if($this->isDir()) {
			return self::dirSize($this->path);
		} else {
			return filesize($this->path);
		}
	}
	
	public function setStatus($status) {
		$this->status = $status;
	}

	public function current() {
		$file = current($this->files);
		
		if($file) {
			return $this->newPath(file);
		}
		
		return $this->false;
	}
	
	protected static function dirSize($path) {
		/*
		$size = 0;
		
    	foreach (glob(rtrim($path, '/').'/*', GLOB_NOSORT) as $each) {
	        $size += is_file($each) ? filesize($each) : self::dirSize($each);
    	}
    	
	    return $size;
	    */

		$total_size = 0;
		$files = scandir($path);

  		foreach($files as $t) {
  		    if (is_dir(rtrim($path, '/') . '/' . $t)) {
  		    	if ($t <> "." && $t <> "..") {
					$size = self::dirSize(rtrim($path, '/') . '/' . $t);

					$total_size += $size;
				}
			} else {
				$size = filesize(rtrim($path, '/') . '/' . $t);
				$total_size += $size;
			}
		}
		
		return $total_size;
	}
	
}
