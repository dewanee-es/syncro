<?php
namespace Syncro\Output;

use Syncro\Synchronizer\File\Comparer\FilesComparison;
use Syncro\Synchronizer\File\FileStatus;

class SynchronizerOutputAdapter extends Output {

	private $output;
	private $dateformat;
	private $preserve;
	
	public function __construct($output, $dateformat, $preserve) {
		$this->output = $output;
		$this->dateformat = $dateformat;
		$this->preserve = $preserve;
	}
	
	public function message($message, $newline = true) {
		$this->output->message($message, $newline);
	}
	
	public function notice($message, $newline = true) {
		$this->output->notice($message, $newline);
	}
	
	public function info($message, $newline = true) {
		$this->output->info($message, $newline);
	}
	
	public function debug($message, $newline = true) {
		$this->output->debug($message, $newline);
	}
	
	public function success($message, $newline = true) {
		$this->output->success($message, $newline);
	}
	
	public function warn($message, $newline = true) {
		$this->output->warn($message, $newline);
	}
	
	public function error($message, $newline = true) {
		$this->output->error($message, $newline);
	}
	
	public function highlight($message, $type = null, $level = 'message', $newline = true) {
		$this->output->highlight($message, $type, $level, $newline);
	}
	
	public function getVerbosity() {
		return $this->output->getVerbosity();
	}
	
	public function setVerbosity($level) {
		$this->output->setVerbosity($level);
	}
	
	/** Display executed action information
	 */
	public function action($action, $message, $level = 'notice') {
		$this->output->$level(sprintf("[%-6s] %s", $action, $message));
	}
	
	/** Displays a banner text for important messages
	 */
	public function banner($text) {
		$length = strlen($text) + 4;
		$this->output->message(" ");
		$this->output->highlight(sprintf("%${length}s\n  %s  \n%${length}s", ' ', $text, ' '));
		$this->output->message(" ");
	}
	
	/** Displays files comparison
	 */
	public function comparison($comparison, $level = 'message') {
		$source = $comparison->getSource();
		$target = $comparison->getTarget();
		$path = $source->getRelativePath();
		$direction = $comparison->getDirection();
		
		switch($comparison->getStatus()) {
		case FilesComparison::UPDATED:
			$sourceText = $targetText = 'file';
			$changeText = null;
			$sourceColor = $changeColor = $targetColor = 'cyan';
			break;
		case FilesComparison::OUTDATED:
			if($direction == 1) {
				$sourceText = 'changed';
				$targetText = 'file';
				$changeText = '---->';
			} else {
				$sourceText = 'file';
				$targetText = 'changed';
				$changeText = '<----';
			}
			$sourceColor = $changeColor = $targetColor = 'blue';
			break;
		case FilesComparison::MISSING:
			if($direction == 1) {
				$sourceText = 'new';
				$targetText = null;
				$changeText = '---->';
			} else {
				$sourceText = null;
				$targetText = 'new';
				$changeText = '<----';
			}
			$sourceColor = $changeColor = $targetColor = 'green';
			break;
		case FilesComparison::OBSOLETE:
			if($direction == 1) {
				$sourceText = 'deleted';
				$sourceColor = 'red';
				$targetText = $target->getType();

				if($this->preserve) {
					$changeText = null;
					$changeColor = $targetColor = 'yellow';
				} else {		
					$changeText = '---->';
					$changeColor = $targetColor = 'red';
				}
			} else {
				$sourceText = $source->getType();
				$targetText = 'deleted';
				$targetColor = 'red';

				if($this->preserve) {
					$changeText = null;
					$changeColor = $sourceColor = 'yellow';
				} else {		
					$changeText = '<----';
					$changeColor = $sourceColor = 'red';
				}
			}
			break;
		case FilesComparison::CONFLICT:
			$changeText = '<-?->';
			$sourceColor = $changeColor = $targetColor = 'magenta';
			
			if($comparison->getNumFiles() == 1) {
				if($direction == 1) {
					$sourceText = $source->getType();
					$targetText = null;
				} else {
					$sourceText = null;
					$targetText = $target->getType();
				}
			} else {
				$sourceText = FileStatus::text($source->getStatus());
				$targetText = FileStatus::text($target->getStatus());
			}
			break;
		case FilesComparison::UNKNOWN:
		default:
			$sourceText = $targetText = 'unknown';
			$changeText = '<-?->';
			$sourceColor = $changeColor = $targetColor = 'magenta';
		}
			
		$text1 = sprintf("%-7s ", sprintf("%" . round(3.5 + strlen($sourceText) / 2) . "s", $sourceText));
		$text2 = sprintf("%-5s ", sprintf("%" . round(2.5 + strlen($changeText) / 2) . "s", $changeText));
		$text3 = sprintf("%-7s ", sprintf("%" . round(3.5 + strlen($targetText) / 2) . "s", $targetText));
		$this->output->highlight($text1, $sourceColor, $level, false);
		$this->output->highlight($text2, $changeColor, $level, false);
		$this->output->highlight($text3, $targetColor, $level, false);
		$this->output->$level($path);
	}
	
	/** Displays files information
	 */
	public function files($source, $target, $level = 'info', $calculate = false) {
		$this->output->$level(sprintf("%-10s: %-10s %-19s %s", $source->getName(), FileStatus::text($source->getStatus()), ($source->modifyTime($calculate) == 0 ? 'unknown date' : date($this->dateformat, $source->modifyTime())), $source->checksum($calculate)));
		$this->output->$level(sprintf("%-10s: %-10s %-19s %s", $target->getName(), FileStatus::text($target->getStatus()), ($target->modifyTime($calculate) == 0 ? 'unknown date' : date($this->dateformat, $target->modifyTime())), $target->checksum($calculate)));
	}
	
	/** Displays summary info
	 */
	public function summary($summary, $source, $target, $level = 'message') {
		$this->output->$level('--------------------------------------------------------------------------------');
		$this->output->$level('Total files:             ' . $summary->getFiles());
		$this->output->$level('Total directories:       ' . $summary->getDirs());
		$this->output->$level('--------------------------------------------------------------------------------');
		$this->output->$level('Unchanged files:         ' . $summary->getUnchanged());
		$this->output->$level('Outdated files:          ' . $summary->getOutdated());
		$this->output->$level('Missing files:           ' . $summary->getMissing());
		$this->output->$level('Obsolete files:          ' . $summary->getObsolete());
		$this->output->$level('Conflictive files:       ' . $summary->getConflicts());
		
		if($summary->getUnknown() > 0) {
			$output->$level('Unknown files:           ' . $summary->getUnknown());
		}

		$this->output->$level('--------------------------------------------------------------------------------');
		$width = max(strlen($source), strlen($target));
		$format = "%-${width}s %-${width}s";
		$updated = $summary->getUpdated();
		$added = $summary->getAdded();
		$preserved = $summary->getPreserved();
		$removed = $summary->getRemoved();
		
		$this->output->$level(sprintf('                         ' . $format, $source, $target));
		$this->output->$level(sprintf('Updated files:           ' . $format, $updated[0], $updated[1]));
		$this->output->$level(sprintf('Added files:             ' . $format, $added[0], $added[1]));
			
		if($preserved[0] > 0 || $preserved[1] > 0) {
			$this->output->$level(sprintf('Preserved files:         ' . $format, $preserved[0], $preserved[1]));
		}
		
		if($removed[0] > 0 || $removed[1] > 0 || ($preserved[0] == 0 && $preserved[1] == 0)) {
			$this->output->$level(sprintf('Removed files:           ' . $format, $removed[0], $removed[1]));
		}
		
		$skipped = $summary->getSkipped();
		$skippedTypes = count($skipped);
		if($skippedTypes == 0) {
			$this->output->$level('Skipped files:           0');
		} else {
			$numSkipped = 0;
			foreach($skipped as $value) {
				$numSkipped += $value[0] + $value[1];
			}
			
			if($skippedTypes == 1) {
				reset($skipped);
				$type = key($skipped);
				$this->output->$level(sprintf("Skipped %-16s %d", "($type):", $numSkipped));
			} else {
				$this->output->$level('Skipped files:           ' . $numSkipped);

				foreach($skipped as $type => $value) {
					switch($type) {
					case 'conflict':
						$text = 'conflicts';
						break;
					default:
						$text = $type;
					}
					
					if($skippedTypes > 1) {
						$text = '   - ' . ucfirst($text) . ':';
					} else {
						$text = 'Skipped ' . $text . ':';
					}
					$this->output->$level(sprintf('%-24s ' . $format, $text, $value[0], $value[1]));
				}
			}
		}
		
		$this->output->$level('--------------------------------------------------------------------------------');
	}
	
}
