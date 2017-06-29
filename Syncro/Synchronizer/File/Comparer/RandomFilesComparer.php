<?php
namespace Syncro\Synchronizer\File\Comparer;

class RandomFilesComparer extends OneWayFilesComparer {
	
	protected $count;
	protected $maxsize;
	protected $totalitems = 0;
	protected $totalsize = 0;
	protected $retries = 0;

	public function __construct($sourcePath, $targetPath, $options, $count, $maxsize) {
		$this->count = $count;
		$this->maxsize = $maxsize;
		parent::__construct($sourcePath, $targetPath, $options);
	}

	protected function nextFile($type, $path) {
		if($type == 'source'
			&& (($this->count > 0 && $this->totalitems >= $this->count)
				|| $this->retries >= 10)) {
			return null;
		}
		
		return $path->nextFile();
	}
	
	protected function addFile($type, $file, $filename) {
		if($this->maxsize > 0 && $type == 'source') {
			$filesize = $file->size();
			
			if($this->totalsize + $filesize > $this->maxsize) {
				$this->retries++;
				return false;
			}
		}
		
		$added = parent::addFile($type, $file, $filename);
		
		if($added && $type == 'source') {
			if($this->count > 0)
				$this->totalitems++;
			else
				$this->totalsize += $filesize;
		}
		
		return $added;
	}		

}
