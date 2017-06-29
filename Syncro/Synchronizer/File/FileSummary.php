<?php
namespace Syncro\Synchronizer\File;

class FileSummary {

	private $files = 0;
	private $dirs = 0;
	private $unchanged = 0;
	private $outdated = 0;
	private $missing = 0;
	private $obsolete = 0;
	private $conflicts = 0;
	private $unknown = 0;
	private $updated = array(0, 0);
	private $added = array(0, 0);
	private $preserved = array(0, 0);
	private $removed = array(0, 0);
	private $skipped = array();
	
	public function getAdded() {
		return $this->added;
	}
	
	public function decrementAdded($i) {
		$this->added[$i - 1]--;
	}
	
	public function incrementAdded($i) {
		$this->added[$i -1 ]++;
	}

	public function getConflicts() {
		return $this->conflicts;
	}
	
	public function decrementConflicts() {
		$this->conflicts--;
	}
	
	public function incrementConflicts() {
		$this->conflicts++;
	}
	
	public function getCopied() {
		return $this->copied;
	}
	
	public function decrementCopied() {
		$this->copies--;
	}
	
	public function incrementCopied() {
		$this->copies++;
	}

	public function getDirs() {
		return $this->dirs;
	}
	
	public function decrementDirs() {
		$this->dirs--;
	}
	
	public function incrementDirs() {
		$this->dirs++;
	}

	public function getFiles() {
		return $this->files;
	}
	
	public function decrementFiles() {
		$this->files--;
	}
	
	public function incrementFiles() {
		$this->files++;
	}
	
	public function getMissing() {
		return $this->missing;
	}
	
	public function decrementMissing() {
		$this->missing--;
	}
	
	public function incrementMissing() {
		$this->missing++;
	}
	
	public function getObsolete() {
		return $this->obsolete;
	}
	
	public function decrementObsolete() {
		$this->obsolete--;
	}
	
	public function incrementObsolete() {
		$this->obsolete++;
	}
	
	public function getOutdated() {
		return $this->outdated;
	}
	
	public function decrementOutdated() {
		$this->outdated--;
	}
	
	public function incrementOutdated() {
		$this->outdated++;
	}
	
	public function getPreserved() {
		return $this->preserved;
	}
	
	public function decrementPreserved($i) {
		$this->preserved[$i - 1]--;
	}
	
	public function incrementPreserved($i) {
		$this->preserved[$i -1 ]++;
	}

	public function getRemoved() {
		return $this->removed;
	}
	
	public function decrementRemoved($i) {
		$this->removed[$i - 1]--;
	}
	
	public function incrementRemoved($i) {
		$this->removed[$i -1 ]++;
	}

	public function getSkipped() {
		return $this->skipped;
	}
	
	public function decrementSkipped($type, $i) {
		if(isset($this->skipped[$type])) {
			$this->skipped[$type][$i - 1]--;
		}
	}
	
	public function incrementSkipped($type, $i) {
		if(!isset($this->skipped[$type])) {
			$this->skipped[$type] = array(0, 0);
		}
		
		$this->skipped[$type][$i - 1]++;
	}

	public function getUnchanged() {
		return $this->unchanged;
	}
	
	public function decrementUnchanged() {
		$this->unchanged--;
	}
	
	public function incrementUnchanged() {
		$this->unchanged++;
	}

	public function getUnknown() {
		return $this->unknown;
	}
	
	public function decrementUnknown() {
		$this->unknown--;
	}
	
	public function incrementUnknown() {
		$this->unknown++;
	}

	public function getUpdated() {
		return $this->updated;
	}
	
	public function decrementUpdated($i) {
		$this->updated[$i - 1]--;
	}
	
	public function incrementUpdated($i) {
		$this->updated[$i -1 ]++;
	}

}
