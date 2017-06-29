<?php
namespace Syncro\Synchronizer\File\Comparer;

use Syncro\Synchronizer\File\FileStatus;

class OneWayFilesComparer extends FilesComparer {

	protected function compareOnlySource($source, $target) {
		$source->setStatus(FileStatus::ADDED);
		$target->setStatus(FileStatus::NONE);
		
		return FilesComparison::missingTarget($source, $target);
	}

	protected function compareOnlyTarget($source, $target) {
		$source->setStatus(FileStatus::DELETED);
		
		// We get the last modification time of the file and
		// check if file was last modified before the last
		// synchronization
		
		$time = $target->modifyTime();
		
		if($time == 0) {
			$status = FileStatus::UNKNOWN_UNCHANGED;
		} else if($time <= $this->options->getStartTime()) {
			$status = FileStatus::UNCHANGED;
		} else {
			$status = FileStatus::CHANGED;
		}
		
		$target->setStatus($status);
		
		return FilesComparison::obsoleteTarget($source, $target);
	}
	
	protected function compareTwoFiles($source, $target) {
		// We get the modification time of both files.

		$timeSource = $source->modifyTime();					
		$timeTarget = $target->modifyTime();

		// File status
		
		if($timeSource <= $this->options->getStartTime() || $timeSource <= $timeTarget) {	// 2. and 1. (see below)
			$statusSource = FileStatus::UNCHANGED;
		} else {
			$statusSource = FileStatus::CHANGED;
		}
		
		$statusTarget = FileStatus::UNCHANGED;
		
		$source->setStatus($statusSource);
		$target->setStatus($statusTarget);
		
		// For a file to be copied to the other path (update) it must meet
		// two requirements.
		//
		// 1. It was modified after the file in the other path
		// 2. It was modified after the last syncrhonization (startTime)
		
		$outdated = ($statusSource == FileStatus::CHANGED);
		
		// 3. If use checksum is true, the checksums of the two
		//    versions of the file must differ.

		if($outdated && $this->options->getChecksum()) {
			// Calculate the checksums of both files and see if they
			// are different. If they do not differ then the file is
			// not copied.

			$checksumSource = $source->checksum();
			$checksumTarget = $target->checksum();
			
			if($checksumSource && $checksumTarget) {	// Check both checksums are available
				$outdated = ($checksumSource != $checksumTarget);
			}
		}
		
		// Set comparison result
		
		if($outdated) {
			$comparison = FilesComparison::outdatedTarget($source, $target);
		} else {
			$comparison = FilesComparison::updated($source, $target);
		}
		
		return $comparison;
	}

}
