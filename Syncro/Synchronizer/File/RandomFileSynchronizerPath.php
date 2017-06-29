<?php
namespace Syncro\Synchronizer\File;

class RandomFileSynchronizerPath extends AbstractRandomSynchronizerPath {

	public function scan() {
		$pathStack = array($this->path);
		$contents = array();
		while ($path = array_pop($pathStack)) {
			foreach (scandir($path) as $filename) {
				if($filename == '.' || $filename == '..')
					continue;
					
				$newPath = $path . '/' . $filename;
				if (is_dir($newPath)) {
					array_push($pathStack, $newPath);
				} else {
					array_push($contents, $newPath);
				}
			}
		}
		
		shuffle($contents);
		
		$this->files = array();
		
		foreach($contents as $path) {
			$this->files[basename($path)] = $path;
		}
		
		return array_keys($this->files);
	}
	
}
