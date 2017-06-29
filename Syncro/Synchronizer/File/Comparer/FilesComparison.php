<?php
namespace Syncro\Synchronizer\File\Comparer;

class FilesComparison {

	const UNKNOWN = 0;
	const DIR = 1;
	const UPDATED = 2;
	const OUTDATED = 3;
	const MISSING = 4;
	const OBSOLETE = 5;
	const CONFLICT = 6;
	
	private $source;
	private $target;
	private $status;
	private $numFiles;
	private $from;
	private $to;
	private $direction = 0;	// 0: none, 1: source -> target, 2: target -> source
	
	private function __construct(&$source, &$target, $status) {
		$this->source = &$source;
		$this->target = &$target;
		$this->status = $status;
		$this->numFiles = 2;
	}
	
	/** One file conflict: source exists, target doesn't
	 */
	public static function conflictSource(&$source, &$target) {
		$comparison = new self($source, $target, self::CONFLICT);
		$comparison->from = &$source;
		$comparison->to = &$target;
		$comparison->direction = 1;
		$comparison->numFiles = 1;
		return $comparison;
	}
	
	/** Two files conflict: source is newer than target
	 */
	public static function conflictSourceToTarget(&$source, &$target) {
		$comparison = new self($source, $target, self::CONFLICT);
		$comparison->from = &$source;
		$comparison->to = &$target;
		$comparison->direction = 1;
		return $comparison;
	}
	
	/** One file conflict: target exists, source doesn't
	 */
	public static function conflictTarget(&$source, &$target) {
		$comparison = new self($source, $target, self::CONFLICT);
		$comparison->from = &$target;
		$comparison->to = &$source;
		$comparison->direction = 2;
		$comparison->numFiles = 1;
		return $comparison;
	}
	
	/** Two files conflict: target is newer than source
	 */
	public static function conflictTargetToSource(&$source, &$target) {
		$comparison = new self($source, $target, self::CONFLICT);
		$comparison->from = &$target;
		$comparison->to = &$source;
		$comparison->direction = 2;
		return $comparison;
	}
	
	public static function dir(&$source, &$target) {
		return new self($source, $target, self::DIR);
	}
	
	public static function missingSource(&$source, &$target) {
		$comparison = new self($source, $target, self::MISSING);
		$comparison->from = &$target;
		$comparison->to = &$source;
		$comparison->direction = 2;
		$comparison->numFiles = 1;
		return $comparison;
	}
	
	public static function missingTarget(&$source, &$target) {
		$comparison = new self($source, $target, self::MISSING);
		$comparison->from = &$source;
		$comparison->to = &$target;
		$comparison->direction = 1;
		$comparison->numFiles = 1;
		return $comparison;
	}
	
	public static function obsoleteSource(&$source, &$target) {
		$comparison = new self($source, $target, self::OBSOLETE);
		$comparison->from = &$target;
		$comparison->to = &$source;
		$comparison->direction = 2;
		$comparison->numFiles = 1;
		return $comparison;
	}
	
	public static function obsoleteTarget(&$source, &$target) {
		$comparison = new self($source, $target, self::OBSOLETE);
		$comparison->from = &$source;
		$comparison->to = &$target;
		$comparison->direction = 1;
		$comparison->numFiles = 1;
		return $comparison;
	}
	
	public static function outdatedSource(&$source, &$target) {
		$comparison = new self($source, $target, self::OUTDATED);
		$comparison->from = &$target;
		$comparison->to = &$source;
		$comparison->direction = 2;
		return $comparison;
	}
	
	public static function outdatedTarget(&$source, &$target) {
		$comparison = new self($source, $target, self::OUTDATED);
		$comparison->from = &$source;
		$comparison->to = &$target;
		$comparison->direction = 1;
		return $comparison;
	}
	
	public static function updated(&$source, &$target) {
		return new self($source, $target, self::UPDATED);
	}
	
	public function isDir() {
		return ($this->source->isDir() || $this->target->isDir());
	}
	
	public function getDirection() {
		return $this->direction;
	}
	
	public function getExisting() {
		switch($this->status) {
		case self::CONFLICT:
		case self::MISSING:
			return $this->from;
		case self::OBSOLETE:
			return $this->to;
		default:
			return null;
		}
	}
	
	public function getFrom() {
		return $this->from;
	}
	
	public function getNumFiles() {
		return $this->numFiles;
	}
	
	public function getNonExisting() {
		switch($this->status) {
		case self::CONFLICT:
		case self::MISSING:
			return $this->to;
		case self::OBSOLETE:
			return $this->from;
		default:
			return null;
		}
	}
	
	public function getSource() {
		return $this->source;
	}
	
	public function getStatus() {
		return $this->status;
	}
	
	public function getTarget() {
		return $this->target;
	}
	
	public function getTo() {
		return $this->to;
	}
	
	/** Resolves one file conflict as copying missing file
	 */	 
	public function resolveCopy() {
		if($this->status == self::CONFLICT && $this->numFiles == 1) {
			$this->status = self::MISSING;			
		}
	}
	
	/** Resolves one file conflict as removing obsolete file
	 */
	public function resolveRemove() {
		if($this->status == self::CONFLICT && $this->numFiles == 1) {
			$this->status = self::OBSOLETE;
			list($this->from, $this->to) = array($this->to, $this->from);	// Swaps from and to
			$this->direction = 3 - $this->direction;	// Changes direction: 1 -> 2, 2 -> 1
		}
	}
	
	/** Resolves two files conflict as copying target to source
	 */
	public function resolveToSource() {
		if($this->status == self::CONFLICT && $this->numFiles == 2) {
			$this->status = self::OUTDATED;
			$this->from = &$this->target;
			$this->to = &$this->source;
			$this->direction = 2;
		}
	}
	
	/** Resolves two files conflict as copying source to target
	 */
	public function resolveToTarget() {
		if($this->status == self::CONFLICT && $this->numFiles == 2) {
			$this->status = self::OUTDATED;
			$this->from = &$this->source;
			$this->to = &$this->target;
			$this->direction = 1;
		}
	}
	
}
