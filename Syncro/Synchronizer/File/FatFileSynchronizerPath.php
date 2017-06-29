<?php
namespace Syncro\Synchronizer\File;

class FatFileSynchronizerPath extends FileSynchronizerPath {

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
					$time = $stat["mtime"];
				}
			}
			
			$this->modifyTime = $time;
		}
		
		return $this->modifyTime;
	}
	
}