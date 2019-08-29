<?php
namespace Syncro\Synchronizer\File;

class AbstractRandomSynchronizerPath extends FileSynchronizerPath {

	protected $clazz;
	
	public static function fromPath($path) {
		$instance = new static((object) array(
			'name' => $path->name,
			'path' => $path->path
		));
		$instance->relative = $path->relative;
		$instance->clazz = get_class($path);
		$instance->filename = $path->getRelativeFilename();
		return $instance;
	}
	
	public function getRelativeFilename() {
		return $this->filename;
	}
	
	public function nextFile() {
		$file = key($this->files);
		
		if($file) {
			next($this->files);
			return $file;
		}
		
		return null;
	}
	
	public function newPath($path) {
		if(!isset($this->files[$path])) {
			return parent::newPath($path);
		} else {
			$clazz = $this->clazz;
			$newPath = new $clazz((object) ['name' => $this->name, 'path' => $this->files[$path]]);
			$newPath->relative = ltrim($this->relative . '/' . $path, '/');
			$newPath->filename = substr($newPath->path, strlen($this->path) + 1);
			return $newPath;
		}
	}
	
}
