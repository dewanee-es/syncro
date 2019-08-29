<?php
namespace Syncro\Synchronizer\File;

use Syncro\Exception\SettingsException;
use Syncro\Output\Output;
use Syncro\Synchronizer\File\Comparer\FilesComparerIterator;
use Syncro\Synchronizer\File\Comparer\RandomFilesComparer;
use Syncro\Synchronizer\SynchronizerSourceInterface;

class RandomFileSynchronizer extends FileSynchronizer {

	protected $count;
	protected $maxsize;
	protected $items;

	public function __construct($settings) {
		parent::__construct($settings);
		list($this->count, $this->maxsize, $this->items) = self::readSize($settings->getSize());
	}
		
	public function getFilesComparerIterator($source, $target) {
		return new FilesComparerIterator(new RandomFilesComparer($source, $target, $this->settings, $this->count, $this->maxsize));
	}

	public function getMode() {
		return "random";
	}
	
	public function setSource(SynchronizerSourceInterface $source) {
		if($this->items == 'FOLDERS') {
			$randomSource = RandomFolderSynchronizerPath::fromPathAndConfig($source, $this->settings->getFolders());			
		} else {
			$randomSource = RandomFileSynchronizerPath::fromPath($source);
		}
		parent::setSource($randomSource);
	}
		
	protected function syncPaths($source, $target, $root = false) {
		if($root) {
			if($this->output->getVerbosity() < Output::NOTICE) {
				$this->output->message("Copying random " . $source->getName() . " '" . $source->getPath() . "' ~~(" . $this->settings->getSize() .")~~> " . $target->getName() . " '" . $target->getPath() . "'");
			} else {
				$this->output->notice("Copying random " . $source->getName() . " ~~(" . $this->settings->getSize() .")~~> " . $target->getName());
			}
		} else {
			$this->output->debug("Copying random " . $source->getRelativeName() . " ~~~~> " . $target->getRelativeName());
		}
		
		// Calls the default path synchronization
		parent::syncPaths($source, $target);
	}
		
	protected static function readSize($sizeOption) {
		$count = 0;
		$maxsize = 0;
		$items = 'FILES';
		
		if(!preg_match('/^([0-9]+) *([KMGT]?B)?(?: (FILE|FOLDER)S?)?$/', strtoupper($sizeOption), $matches)) {
			throw new SettingsException('Size is not valid: ' + $sizeOption + '. Size can be the number of files or the max size (with B, KB, MB, GB, TB suffix)');
		}
		
		if(empty($matches[2])) {
			$count = (int) $matches[1];
		} else {
			$maxsize = (int) $matches[1];
			
			switch($matches[2]) {
				case 'KB':
					$maxsize *= 1000;
					break;
				case 'MB':
					$maxsize *= 1000000;
					break;
				case 'GB':
					$maxsize *= 1000000000;
					break;
				case 'TB':
					$maxsize *= 1000000000000;
					break;
			}
		}
		
		if(!empty($matches[3])) {
			$items = $matches[3] . 'S';
		}
		
		if($count == 0 && $maxsize == 0) {
			throw new SettingsException('Size can not be zero');
		}
		
		return [$count, $maxsize, $items];
	}

}
