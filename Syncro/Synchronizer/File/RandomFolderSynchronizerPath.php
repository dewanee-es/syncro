<?php
namespace Syncro\Synchronizer\File;

use Syncro\Synchronizer\File\Comparer\FilenameMatcher;

class RandomFolderSynchronizerPath extends AbstractRandomSynchronizerPath {
	
	protected $select;	// Folders to select: top, leaf, any
	protected $fnmatcher;	// Select folders whose contents match these filenames

	public static function fromPathAndConfig($path, $config) {
		$instance = parent::fromPath($path);
		$instance->select = $config->select;
		if(!empty($config->with)) {
			$instance->fnmatcher = new FilenameMatcher($config->with);
		}		
		return $instance;
	}

	public function scan() {
		$base = $this->path;
		$pathStack = array($this->path);
		$contents = array();
		$isTop = true;
		
		while ($path = array_pop($pathStack)) {
			$isLeaf = !$isTop;
		    $isSelected = empty($this->fnmatcher);
			
			foreach (scandir($path) as $filename) {
				if($filename == '.' || $filename == '..')
					continue;
					
				$newPath = $path . '/' . $filename;
				
				if(!$isSelected && !$isTop) {
					$isSelected = $this->fnmatcher->match(substr($newPath, strlen($base) + 1));
				}
				
				if (is_dir($newPath)) {
					$isLeaf = false;
					
					if($this->select != 'top' || $isTop) {
						array_push($pathStack, $newPath);
					}
				}
			}
			
			if($isSelected && !$isTop && ($this->select != 'leaf' || $isLeaf)) {
				array_push($contents, $path);
			}
			
			$isTop = false;
		}
		
		shuffle($contents);
		
		$this->files = array();
		
		foreach($contents as $path) {
			$this->files[basename($path)] = $path;
		}
		
		return array_keys($this->files);
	}
	
}
