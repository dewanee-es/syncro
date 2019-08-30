<?php
namespace Syncro\Synchronizer\File;

use Syncro\Exception\DirectoryCreateException;
use Syncro\Exception\DirectoryRemoveException;
use Syncro\Exception\FileCopyException;
use Syncro\Exception\FileDeleteException;
use Syncro\Exception\InvalidPathException;
use Syncro\Interaction\SynchronizerInteractionAdapter;
use Syncro\Output\SynchronizerOutputAdapter;
use Syncro\Synchronizer\AbstractSynchronizer;
use Syncro\Synchronizer\SynchronizerSourceInterface;
use Syncro\Synchronizer\SynchronizerTargetInterface;
use Syncro\Synchronizer\File\Comparer\FilesComparison;

abstract class FileSynchronizer extends AbstractSynchronizer {

	const ACTION_ASK = '?';
	const ACTION_COPY = 'c';
	const ACTION_DIFF = 'd';
	const ACTION_INFO = 'i';
	const ACTION_REMOVE = 'r';
	const ACTION_SKIP = 's';
	const ACTION_TO_SOURCE = '<';
	const ACTION_TO_TARGET = '>';
	
	const TEXT_COPY = 'copy to ';
	const TEXT_DIFF = 'view differences';
	const TEXT_INFO = 'show files info';
	const TEXT_REMOVE = 'remove from ';
	const TEXT_SKIP = 'skip';
	const TEXT_TO_SOURCE = 'copy to ';
	const TEXT_TO_TARGET = 'copy to ';
	
	const DATE = 'Y-m-d H:i:s';
	
	protected $output;
	protected $interaction;
	protected $summary;
	
	public function __construct($settings) {
		parent::__construct($settings);
		
		$this->output = new SynchronizerOutputAdapter($settings->getOutput(), self::DATE, $settings->getPreserve());
		$this->interaction = new SynchronizerInteractionAdapter($settings->getInteraction());
	}
	
	/** Returns the iterator for the files comparer used by this synchronizar
	 */
	 
	abstract public function getFilesComparerIterator($source, $target);
	
	/** Returns the text representation of the synchronization mode for this synchronizer
	 */
	 
	abstract public function getMode();

	/** Start the synchronization process. Checks both paths and then sync them.
	 *
	 *  @throws SynchronizationException If the synchronization process fails.
	 *  	(The function can throw any of the classes that inherit from
	 *  	Synchronization_Exception depending on where the error ocurred)
	 *
	 */

	public function synchronize() {
		// if the synchronization is being run in simulation mode let the user
		// know so.

		if($this->settings->getSimulate()) {
			$this->output->banner("*** RUNNING IN SIMULATION MODE ***");
		}

		if($this->interaction->auto()) {
			$this->output->banner("!!! RUNNING IN AUTO MODE !!!");
		}

		/***********************************************************************
		 * Path check
		 *
		 * Checks to make sure both paths were given and that the both paths are
		 * valid, accesible and are both directories.
		 *
		 */

		if($this->source == null || $this->target == null)
			throw new InvalidPathException("One or both paths are missing. Both paths must be supplied");
			
		$this->source->check();
		$this->target->check();
		
		// We save the time when the synchronization began so that we do not
		// copy files unnecesarily.

		$this->settings->setEndTime(time());
		$excluded = implode("\n                            ", $this->settings->getExcludes());
		
		$this->output->notice('--------------------------------------------------------------------------------');
		$this->output->notice('Source path:                (' . $this->source->getName() . ') ' . $this->source->getPath());
		$this->output->notice('Target path:                (' . $this->target->getName() . ') ' . $this->target->getPath());
		$this->output->info('Synchronization mode:       ' . $this->getMode());
		$this->output->info('Last synchronization time:  ' . ($this->settings->getTime() == 0 ? 'unknown' : date(self::DATE, $this->settings->getTime())));
		$this->output->info('Start synchronization time: ' . date(self::DATE, $this->settings->getEndTime()));
		$this->output->info('Preserve files:             ' . ($this->settings->getPreserve() ? 'yes' : 'no'));
		$this->output->info('Use checksum:               ' . ($this->settings->getChecksum() ? 'yes' : 'no'));
		$this->output->info('FAT filesystem:             ' . ($this->settings->getFat() ? 'yes' : 'no'));
		$this->output->info('Include hidden files:       ' . ($this->settings->getHidden() ? 'yes' : 'no'));
		$this->output->info('Excluded files:             ' . (empty($excluded) ? 'none' : $excluded));
		$this->output->notice('--------------------------------------------------------------------------------');

		/*******************************************************************************
		 * Start the synchronization
		 *
		 */
		 
		$this->summary = new FileSummary;

		// We synchronize the paths. We do not need to put a
		// try/catch block the calling function should catch it.

		$this->syncPaths($this->source, $this->target, true);
		
		$this->output->summary($this->summary, $this->source->getName(), $this->target->getName());
		
		return $this->settings->getEndTime();
	}
	
	/** Synchronizes two paths. After the syncrhonization is guaranteed that the
	 *  $target has at least the same files that $source.
	 *
	 *  @param string $source One of the paths.
	 *
	 *  @param string $target The other path.
	 *
	 *  @param boolean $root True when the paths are the root of the synchronization process 
	 *
	 *  @throws SynchronizationException If the synchronization process fails.
	 *  	(The function can throw any of the classes that inherit from
	 *  	SynchronizationException depending on where the error happens.)
	 *
	 */

	protected function syncPaths($source, $target, $root = false) {
		
		// Compare the paths files modified between start and end time.
		// Use checksums and include hidden files if specified
		$filescomparer = $this->getFilesComparerIterator($source, $target);
		
		// First delete obsolete files to free space
		foreach($filescomparer as $comparison) {
		  if($comparison->getStatus() == FilesComparison::OBSOLETE) {
		    $this->syncComparison($comparison);
			}
		}
		
		// Execute actions for the other files
    foreach($filescomparer as $comparison) {
      if($comparison->getStatus() != FilesComparison::OBSOLETE) {
        $this->syncComparison($comparison);
      }
    }
		
	}
	
	protected function syncComparison($comparison) {
    if($comparison->isDir()) {
      $this->summary->incrementDirs();
    } else {
      $this->summary->incrementFiles();
    }
    
    $status = $comparison->getStatus();
    
    switch($status) {
    case FilesComparison::DIR:    // Synchronize subdirectory
      $this->summary->incrementUnchanged();
      $this->doDir($comparison);
      break;
    case FilesComparison::UPDATED:  // Files updated
      $this->summary->incrementUnchanged();
      $this->doUpdated($comparison);
      break;
    case FilesComparison::OUTDATED: // Source or target outdated
      $this->summary->incrementOutdated();
      $this->updateOutdated($comparison);
      break;
    case FilesComparison::MISSING:
      $this->summary->incrementMissing();
      $this->addMissing($comparison);
      break;
    case FilesComparison::OBSOLETE:
      $this->summary->incrementObsolete();
      $this->removeObsolete($comparison);
      break;
    case FilesComparison::CONFLICT:
      $this->summary->incrementConflicts();
      $this->doConflict($comparison);
      break;
    case FilesComparison::UNKNOWN:
    default:
      $this->summary->incrementUnknown();
      $this->doUnknown($comparison);
      break;
    }
	}

	protected function updateOutdated($comparison) {
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$from = $comparison->getFrom();
		$to = $comparison->getTo();
		$direction = $comparison->getDirection();

		$this->output->comparison($comparison);
		$this->output->files($source, $target);
		$this->output->action("update", "Copying '" . $from->getPath() . "' -> '" . $to->getPath() . "'");
		$this->summary->incrementUpdated(3 - $direction);

		if(!$this->settings->getSimulate()) {
			if(!$from->copy($to)) {
				// If we were unable to copy the file to the other
				// path. We abort the synchronization process
				// and throw an exception.
					
				throw new FileCopyException("Unable to copy the file '" . $from->getPath() . "' to '" . $to->getPath() . "'");
			} else {
				// Displays file status after copy
				$from->setStatus(FileStatus::UNCHANGED);
				$to->setStatus(FileStatus::UPDATED);
				$this->output->files($source, $target, 'debug', true);
			}
		}
	}
	
	protected function addMissing($comparison) {
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$from = $comparison->getFrom();
		$to = $comparison->getTo();
		$direction = $comparison->getDirection();
		
		$this->output->comparison($comparison);
		$this->output->files($source, $target);
		$this->summary->incrementAdded(3 - $direction);

		if($from->isDir()) {
			$this->copyDirectory($from, $to);
		} else {
			$this->output->action("add", "Copying '" . $from->getPath() . "' -> '" . $to->getPath() . "'");

			if(!$this->settings->getSimulate()) {
				if(!$from->copy($to)) {
					// If we were unable to copy the file to the other path
					// we throw an exception.

					throw new FileCopyException("Unable to copy the file '" . $from->getPath() . "' to '" . $to->getPath() . "'");
				} else {
					// Displays file status after copy
					$from->setStatus(FileStatus::UNCHANGED);
					$to->setStatus(FileStatus::UPDATED);
					$this->output->files($source, $target, 'debug', true);
				}
			}
		}
	}
	
	protected function removeObsolete($comparison) {
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$file = $comparison->getExisting();
		$direction = $comparison->getDirection();
		
		$this->output->comparison($comparison);
		$this->output->files($source, $target);

		if($this->settings->getPreserve()) {
			$this->output->action("remove", "Skip removing '" . $file->getPath() . "' (preserve = true)");
			$this->summary->incrementPreserved(3 - $direction);
		} else {
			$this->summary->incrementRemoved(3 - $direction);
			
			if($file->isDir()) {			
				// The file is a directory, we have to recursively
				// delete it and all its files and sub-folders.
			
				$this->removeDirectory($file);
			} else {
				$this->output->action("remove", "Removing '" . $file->getPath() . "'");

				if(!$this->settings->getSimulate()) {
					if(!$file->remove()) {
						// If we fail to delete the file we throw an exception.

						throw new FileDeleteException("Couldn't delete file '" . $file->getPath() . "'");
					}
				}
			}
		}
	}
	
	protected function doConflict($comparison) {
		if($comparison->getNumFiles() == 1) {
			$this->doConflictOneFile($comparison);
		} else {
			$this->doConflictTwoFiles($comparison);
		}
	}
	
	protected function doConflictOneFile($comparison) {	
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$file = $comparison->getExisting();
		$noFile = $comparison->getNonExisting();
		$path = $file->getRelativePath();
		$direction = $comparison->getDirection();
		
		if($this->interaction->auto()) {
			$defaultAction = self::ACTION_COPY;
		} else {
			$defaultAction = self::ACTION_SKIP;
		}
		
		$this->output->comparison($comparison);
		$this->output->files($source, $target);

		$action = $this->interaction->choice(($file->isDir() ? 'Directory' : 'File') . " '$path' doesn't exist in " . $noFile->getName() . ". Action?", [
			self::ACTION_SKIP => self::TEXT_SKIP,
			self::ACTION_COPY => self::TEXT_COPY . $noFile->getName(),
			self::ACTION_REMOVE => self::TEXT_REMOVE . $file->getName()
		], $defaultAction);

		switch($action) {
		case self::ACTION_REMOVE:
			$comparison->resolveRemove();	// Resolve comparison conflict
			$this->removeObsolete($comparison);
			break;
		case self::ACTION_COPY:
			$comparison->resolveCopy();		// Resolve comparison conflict
			$this->addMissing($comparison);
			break;
		case self::ACTION_SKIP:
		default:
			$this->output->action("skip", "Skipping conflict '$path' (" . $file->getName() . ")" . ($this->interaction->interactive() ? '' : ' (default action)'));
			$this->summary->incrementSkipped('conflict', $direction);
		}
	}
	
	protected function doConflictTwoFiles($comparison) {
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$path = $source->getRelativePath();
		$direction = $comparison->getDirection();
		
		if(!$this->interaction->interactive() && !$this->interaction->auto()) {
			$defaultAction = self::ACTION_SKIP;
		} else if($direction == 1) {
			$defaultAction = self::ACTION_TO_TARGET;
		} else {
			$defaultAction = self::ACTION_TO_SOURCE;
		}
		
		$this->output->comparison($comparison);
		$this->output->files($source, $target);

		$continue = true;
		while($continue) {
			$continue = false;
		
			$action = $this->interaction->choice("File '$path' has changed in both paths. Action?", [
				self::ACTION_SKIP => self::TEXT_SKIP,
				self::ACTION_TO_TARGET => self::TEXT_TO_TARGET . $target->getName(),
				self::ACTION_TO_SOURCE => self::TEXT_TO_SOURCE . $source->getName(),
				self::ACTION_INFO => self::TEXT_INFO,
				self::ACTION_DIFF => self::TEXT_DIFF
			], $defaultAction);

			switch($action) {
			case self::ACTION_TO_SOURCE:
				$comparison->resolveToSource();	// Resolve comparison conflict
				$this->updateOutdated($comparison);
				break;
			case self::ACTION_TO_TARGET:
				$comparison->resolveToTarget();		// Resolve comparison conflict
				$this->updateOutdated($comparison);
				break;
			case self::ACTION_INFO:
				$this->output->files($source, $target, 'message', true);
				$continue = true;
				break;
			case self::ACTION_DIFF:
				Diff::showDiff($this->output, $source->getPath(), $target->getPath(), $source->getRelativeName(), $target->getRelativeName());
				$continue = true;
				break;
			case self::ACTION_SKIP:
			default:
				$this->output->action("skip", "Skipping conflict '$path' (" . $source->getName() . " <=?=> " . $target->getName() . ")" . ($this->interaction->interactive() ? '' : ' (default action)'));
				$this->summary->incrementSkipped('conflict', $direction);
			}
		}
	}
	
	protected function doDir($comparison) {
		// We recursively synchornize the directory with it's
		// counterpart in the other path. Note that if an error
		// occurr while doing that synchronization an exception will
		// be thrown.

		$this->syncPaths($comparison->getSource(), $comparison->getTarget());
		$this->summary->incrementSkipped('unchanged', 1);
	}
	
	protected function doUpdated($comparison) {
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$path = $source->getRelativePath();
		
		$this->output->comparison($comparison, 'debug');
		$this->output->files($source, $target, 'debug');
		$this->output->action("skip", "Skipping updated file '" . $path . "' (" . $source->getName() . " = " . $target->getName() . ")", 'debug');
		$this->summary->incrementSkipped('unchanged', 1);
	}
	
	protected function doUnknown($comparison) {
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$path = $source->getRelativePath();
		
		$this->output->comparison($comparison);
		$this->output->files($source, $target);
		$this->output->action("skip", "Skipping unknown file '" . $path . "' (" . $source->getName() . " <=?=> " . $target->getName() . ")", 'debug');
		$this->summary->incrementSkipped('unknown', 1);
	}

	/** Removes an entire directory (including it's sub-folders).
	 *
	 *  @param string $path The path of the directory to be removed.
	 *
	 *  @throws FileDeleteException If the function is unable to delete one of
	 *  	the files in the directory.
	 *
	 *  @throws DirectoryRemoveException If the function is unable to remove
	 *  	the directory.
	 *
	 */

	private function removeDirectory($path) {
		
		$this->output->action("remove", "Removing '" . $path->getPath() . "'");

		// We get a list of all files and folders in the folder we are about to
		// remove.

		$files = $path->scan();

		foreach($files as $file) {
			// We skip the current directory and the parent directory.

			if($file == "." || $file == "..")
				continue;

			// We get the full path of the current file.

			$pathDelete = $path->newPath($file);

			// If the current file is a Directory we recusrsively delete it, if
			// it is a regular file we just have to remove it.
			
			if($pathDelete->isDir()) {
				$this->removeDirectory($pathDelete);
			} else {
				$this->output->action("remove", "Removing '" . $pathDelete->getPath() . "'");

				if(!$this->settings->getSimulate()) {
					if(!$pathDelete->remove()) {
						// If we fail to remove thw file, we throw an exception.

						throw new FileDeleteException("Couldn't remove file '" . $pathDelete->getPath() . "'");
					}
				}
			}
		}

		// Finally we remove the directory itself.

		if(!$this->settings->getSimulate()) {
			if(!$path->removeDir()) {		
				// If we fail to remove the directory we throw an exception

				throw new DirectoryRemoveException("Couldn't remove directory '" . $path->getPath() . "'");
			}
		}
	}

	/** Recursively copy a directory, all its sub-directories and files.
	 *
	 *  @param string $path The path of the directory to copy.
	 *
	 *  @throws DirectoryCreateException If the function fails to create the
	 *  	destination directory.
	 *
	 *  @throws FileCopyException If the function fails to copy one of the
	 *  	files to the destination directory.
	 *
	 */

	private function copyDirectory($from, $to) {
		// First we try to create the destination directory.

		$this->output->action("add", "Copying '" . $from->getPath() . "' -> '" . $to->getPath() . "'");
		$this->output->action("add", "Creating '" . $to->getPath() . "'");

		if(!$this->settings->getSimulate()) {
			if(!$to->makeDir($from->modifyTime())) {
				throw new DirectoryCreateException("Unable to create destination directory '" . $to->getPath() . "'");
			} else {
				// Displays file status after copy
				$from->setStatus(FileStatus::UNCHANGED);
				$to->setStatus(FileStatus::UPDATED);
				$this->output->files($from, $to, 'debug', true);
			}
		}

		// Now we scan the source directory. We try to copy each file and sub
		// folder to the destination directory.

		$files = $from->scan();

		foreach($files as $file) {
			// We skip the current and parent directories

			if($file == "." || $file == "..")
				continue;

			// If we have to, we skip hidden files and directories.

			if(!$this->settings->getHidden() == true && substr($file, 0, 1) == ".")
				continue;

			$fromCopy = $from->newPath($file);
			$toCopy = $to->newPath($file);

			// If the current file is a directory we recursively copy it to the
			// destination path. If it is a regulr file we just copy it.

			if($fromCopy->isDir()) {
				$this->copyDirectory($fromCopy, $toCopy);
			} else {
				$this->output->action("add", "Copying '" . $fromCopy->getPath() . "' -> '" . $toCopy->getPath() . "'");

				if(!$this->settings->getSimulate()) {
					if(!$fromCopy->copy($toCopy)) {
						throw new FileCopyException("Unable to copy the file '" . $fromCopy->getPath() . "' to '" . $toCopy->getPath() . "'");
					} else {
						// Displays file status after copy
						$fromCopy->setStatus(FileStatus::UNCHANGED);
						$toCopy->setStatus(FileStatus::UPDATED);
						$this->output->files($fromCopy, $toCopy, 'debug', true);
					}
				}
			}
		}
	}
	
}
