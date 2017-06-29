<?php
namespace Syncro\Synchronizer\File;

class Diff {

	private $output;
	private $path1;
	private $path2;
	private $name1;
	private $name2;

	public function __construct($output, $path1, $path2, $name1 = null, $name2 = null) {
		if(!$name1) {
			$name1 = $path1;
		}
		if(!$name2) {
			$name2 = $path2;
		}
		
		$this->output = $output;
		$this->path1 = $path1;
		$this->path2 = $path2;
		$this->name1 = $name1;
		$this->name2 = $name2;
	}

	public static function showDiff($output, $path1, $path2, $name1 = null, $name2 = null) {
		$diff = new self($output, $path1, $path2, $name1, $name2);
		return $diff->show();
	}
	
	public function show() {
		$diff = shell_exec('diff -su ' . escapeshellarg($this->path1) . ' ' . escapeshellarg($this->path2));
		
		if($diff == null) {
			$this->output->error('ERROR: diff system command not found');
			return false;
		}
		
		$this->output->message('--------------------------------------------------------------------------------');
		$this->write1(sprintf("%-79s", $this->name1), true);
		$this->write2(sprintf("%-79s", $this->name2), true);
		$lines = explode("\n", $diff);
		
		if(count($lines) > 3) {
			$lines = array_slice($lines, 3);	// Removes file names and first difference header
			
			foreach($lines as $line) {
				if(strlen($line) > 0) {
					switch($line{0}) {
					case '@':
						$this->writeSeparator();
						break;
					case '-':
						$this->write1(substr($line, 1));
						break;
					case '+':
						$this->write2(substr($line, 1));
						break;
					default:
						$this->write(substr($line, 1));
					}
				}
			}
		}
		
		$this->output->message('--------------------------------------------------------------------------------');
		
		return true;
	}
	
	public function write($message, $mark = ' ', $highlight = null) {
		$message = str_replace("\t", "  ", $message);
		
		$this->output->message($mark, false);

		if($highlight) {
			$this->output->highlight($message, $highlight);
		} else {
			$this->output->message($message);
		}
	}
	
	public function write1($message, $header = false) {
		$class = 'bg-red';
		
		if($header) {
			$class .= '-bold';
		}
		
		$this->write($message, '-', $class);
	}
	
	public function write2($message, $header = false) {
		$class = 'bg-green';
	
		if($header) {
			$class .= '-bold';
		}
		
		$this->write($message, '+', $class);
	}
	
	public function writeSeparator() {
		$this->output->highlight(sprintf("%35s/--------/", ' '), 'cyan');
	}

}
