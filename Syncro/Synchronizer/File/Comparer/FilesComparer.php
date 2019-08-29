<?php
namespace Syncro\Synchronizer\File\Comparer;

abstract class FilesComparer {
		
	protected $sourcePath;
	protected $targetPath;
	protected $options;
	protected $fnmatcher;
	protected $files = array();

	public function __construct($sourcePath, $targetPath, $options) {
		$this->sourcePath = $sourcePath;
		$this->targetPath = $targetPath;
		$this->options = $options;
		$this->fnmatcher = new FilenameMatcher($options->getExcludes());
		$this->scanPath('source', $sourcePath);
		$this->scanPath('target', $targetPath);
		ksort($this->files);
	}
	
	protected function scanPath($type, $path) {
		$path->scan();
		$filename = $this->nextFile($type, $path);
		
		while($filename) {
			$file = $path->newPath($filename);
			$this->addFile($type, $file, $filename);
			$filename = $this->nextFile($type, $path);
		}
	}
	
	protected function nextFile($type, $path) {
		return $path->nextFile();
	}
	
	protected function addFile($type, $file, $filename) {
	
		// We check if the file name starts with .

		if(substr($filename, 0, 1) == ".") {
			// If we are to skip hidden files then we don't add the file

			if(!$this->options->getHidden())
				return false; 

			// If the file is the current directory (.) or the parent directory
			// (..) we don't add the file

			if($filename == "." || $filename == "..")
				return false;
		}
			
		// If the file matchs an excluded name we don't add the file
		if($this->fnmatcher->match($file->getRelativeFilename()))
			return false;

		if(!isset($this->files[$filename])) {
			$this->files[$filename] = (object) array('source' => false, 'target' => false);
		} else if($this->files[$filename]->$type) {
			// If there is a file with the same name we don't add the file
			return false;
		}
			
		$this->files[$filename]->$type = $file;
		return true;
	}
	
	public function compare($file) {
		if(isset($this->files[$file])) {
			$files = $this->files[$file];
		} else {
			return null;
		}
		
		$newSource = $files->source;
		$newTarget = $files->target;
		$comparison = null;

		// We check if the same file exists in both paths

		if($newSource && $newTarget) {
		
			// The file exists in both paths and therefore their type
			// should match, in other words, if the file in one path is a
			// directory the file in the other path should be a directory too.
			$isDirectory = $newSource->isDir();

			if($isDirectory != $newTarget->isDir()) {
				// The type of the two files doesn't match. We abort the
				// comparation and throw an exception.

				throw new ResourceMissmatchException("Resource type mismatch: '" . $newSource->getPath() . "' (" . $newSource->getType() . ") doesn't match '" . $newTarget->getPath() . "' (" . $newTarget->getType() . ")");
			}
			
			if($isDirectory) {
				$comparison = $this->compareDirectory($newSource, $newTarget);
			} else {
				$comparison = $this->compareTwoFiles($newSource, $newTarget);
			}
			
		} else if($newSource) {	// File only exists in source
		
			$comparison = $this->compareOnlySource($newSource, $this->targetPath->newPath($file));
			
		} else if($newTarget) {	// File only exists in target
		
			$comparison = $this->compareOnlyTarget($this->sourcePath->newPath($file), $newTarget);
			
		} else {	// This must not happen!
		
			$comparison = FilesComparison::updated($this->sourcePath->newPath($file), $this->targetPath->newPath($file));			
			
		}
		
		return $comparison;
	}
	
	protected function compareDirectory($source, $target) {
		return FilesComparison::dir($source, $target);
	}
	
	/** Compare existing source with no file
	 */
	abstract protected function compareOnlySource($source, $target);
	
	/** Compare existing target with no file
	 */
	abstract protected function compareOnlyTarget($source, $target);
	
	/** Compare two files
	 */
	abstract protected function compareTwoFiles($source, $target);
	
	public function getFiles() {
		return array_keys($this->files);
	}
	
}
