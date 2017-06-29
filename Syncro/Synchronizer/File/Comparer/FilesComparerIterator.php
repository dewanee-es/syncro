<?php
namespace Syncro\Synchronizer\File\Comparer;

use Syncro\Exception\ResourceMissmatchException;

class FilesComparerIterator implements \Iterator {

	private $comparer;
	private $comparison;
	private $files;

	public function __construct($comparer) {
		$this->comparer = $comparer;
		$this->files = $comparer->getFiles();
	}

	public function current() {
		if(!$this->comparison) {
			$this->comparison = $this->comparer->compare($this->key());
		}
		
		return $this->comparison;
	}
	
	public function key() {
		return current($this->files);
	}
	
	public function next() {
		next($this->files);
		$this->comparison = null;
	}
	
	public function rewind() {
		reset($this->files);
		$this->comparison = null;
	}
	
	public function valid() {
		return key($this->files) !== null;
	}

}
