<?php
namespace Syncro\Synchronizer\File\Comparer;

use Syncro\Synchronizer\File\FileStatus;

class TwoWayFilesComparer extends FilesComparer {

	protected function compareOnlySource($source, $target) {
		return $this->compareOneFile($source, $target, true);
	}

	protected function compareOnlyTarget($source, $target) {
		return $this->compareOneFile($source, $target, false);
	}
	
	private function compareOneFile($source, $target, $isSource) {
		if($isSource) {
			$file = &$source;
			$noFile = &$target;
		} else {
			$file = &$target;
			$noFile = &$source;
		}
		
		// We get the last modification time of the file and
		// check if file was last modified before the last
		// synchronization

		$time = $file->modifyTime();

		if($time == 0 || $this->options->getStartTime() == 0) {
			// Modification time unknown
			// or first synchronization
			
			$file->setStatus(FileStatus::UNKNOWN_ADDED);
			$noFile->setStatus(FileStatus::UNKNOWN_DELETED);
			
			if($isSource) {
				$comparison = FilesComparison::conflictSource($source, $target);
			} else {
				$comparison = FilesComparison::conflictTarget($source, $target);
			}
		} else if($time <= $this->options->getStartTime()) {
			// Since the file was last modified before the last
			// synchronization we can asume that the file was deleted on
			// a path after the synchronization so it should be
			// removed from the other path as well.
			
			$file->setStatus(FileStatus::UNCHANGED);
			$noFile->setStatus(FileStatus::DELETED);
			
			if($isSource) {
				$comparison = FilesComparison::obsoleteSource($source, $target);
			} else {
				$comparison = FilesComparison::obsoleteTarget($source, $target);
			}
		} else {
			// Since the file was last modified after the last
			// synchronization we asume that the file is new on one
			// path and thus we should copy it to the other path.
				
			$file->setStatus(FileStatus::ADDED);
			$noFile->setStatus(FileStatus::NONE);
			
			if($isSource) {
				$comparison = FilesComparison::missingTarget($source, $target);
			} else {
				$comparison = FilesComparison::missingSource($source, $target);
			}
		}
		
		return $comparison;
	}

	protected function compareTwoFiles($source, $target) {
		// We get the modification time of both files.

		$timeSource = $source->modifyTime();					
		$timeTarget = $target->modifyTime();

		// File status
		
		if($timeSource <= $this->options->getStartTime() && $this->options->getStartTime() > 0) {		// 2. (see below)
			$statusSource = FileStatus::UNCHANGED;
		} else if($timeSource > $this->options->getEndTime()) {	// 3. (see below)
			$statusSource = FileStatus::UPDATED;
		} else if($timeSource < $timeTarget) {		// 1. (see below)
			$statusSource = FileStatus::OLDER;
		} else if($timeSource == $timeTarget) {
			$statusSource = FileStatus::UNCHANGED;
		} else if($timeTarget > $this->options->getStartTime()) {
			$statusSource = FileStatus::NEWER;
		} else {
			$statusSource = FileStatus::CHANGED;
		}
		
		if($timeTarget <= $this->options->getStartTime() && $this->options->getStartTime() > 0) {		// 2. (see below)
			$statusTarget = FileStatus::UNCHANGED;
		} else if($timeTarget > $this->options->getEndTime()) {	// 3. (see below)
			$statusTarget = FileStatus::UPDATED;
		} else if($timeTarget < $timeSource) {		// 1. (see below)
			$statusTarget = FileStatus::OLDER;
		} else if($timeTarget == $timeSource) {
			$statusTarget = FileStatus::UNCHANGED;
		} else if($timeSource > $this->options->getStartTime()) {
			$statusTarget = FileStatus::NEWER;
		} else {
			$statusTarget = FileStatus::CHANGED;
		}
		
		$source->setStatus($statusSource);
		$target->setStatus($statusTarget);
		
		// For a file to be copied to the other path (update) it must meet
		// three requirements.
		//
		// 1. It was modified after the file in the other path (!OLDER)
		// 2. It was modified after the last syncrhonization (startTime) (!UNCHANGED)
		// 3. It wasn't modified after the synchronization started (endTime) (!UPDATED)
		//
		// So, the status must be CHANGED or NEWER
		
		$outdated = true;
		$direction = 0;	// 1: source -> target, 2: target -> source
		
		if($statusSource == FileStatus::CHANGED || $statusSource == FileStatus::NEWER) {
			$direction = 1;
		} else if($statusTarget == FileStatus::CHANGED || $statusTarget == FileStatus::NEWER) {
			$direction = 2;
		} else {
			$outdated = false;
		}
		
		// Test conflict: both files changed
		$conflict = ($statusSource == FileStatus::NEWER || $statusTarget == FileStatus::NEWER || ($statusSource == FileStatus::CHANGED && $statusTarget == FileStatus::CHANGED));

		// 4. If use checksum is true, the checksums of the two
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
			if($conflict) {
				if($direction == 1) {
					$comparison = FilesComparison::conflictSourceToTarget($source, $target);
				} else {
					$comparison = FilesComparison::conflictTargetToSource($source, $target);
				}
			} else if($direction == 1) {
				$comparison = FilesComparison::outdatedTarget($source, $target);
			} else {
				$comparison = FilesComparison::outdatedSource($source, $target);
			}
		} else {
			$comparison = FilesComparison::updated($source, $target);
		}
		
		return $comparison;
	}

}
