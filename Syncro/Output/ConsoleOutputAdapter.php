<?php
namespace Syncro\Output;

/** This is the output console adapter. It writes message to Symfony Console OutputInterface
 *
 */

class ConsoleOutputAdapter extends Output {
	
	private $output;

	public function __construct($output) {
		$this->output = $output;
	}
	
	public function message($message, $newline = true) {
		$this->output->write($message, $newline);
	}
	
	public function notice($message, $newline = true) {
		if($this->output->isVerbose()) {
			$this->output->write($message, $newline);
		}
	}
	
	public function info($message, $newline = true) {
		if($this->output->isVeryVerbose()) {
			$this->output->write($message, $newline);
		}
	}
	
	public function debug($message, $newline = true) {
		if($this->output->isDebug()) {
			$this->output->write($message, $newline);
		}
	}
	
	public function success($message, $newline = true) {
		$this->message("<info>$message</info>", $newline);
	}
	
	public function warn($message, $newline = true) {
		$this->notice("<comment>$message</comment>", $newline);
	}
	
	public function error($message, $newline = true) {
		$this->message("<error>$message</error>", $newline);
	}
	
	public function highlight($message, $type = null, $level = 'message', $newline = true) {
		if(empty($type)) {
			$tag = 'question';
		} else {
			$bg = null;
			$fg = null;
			$options = null;
			
			$words = explode('-', $type);
			$isfg = true;
			$isbg = false;
			
			foreach($words as $word) {
				if($word == 'bg') {
					$isfg = false;
					$isbg = true;
				} else if($isfg) {
					$fg = $word;
					$isfg = false;
				} else if($isbg) {
					$bg = $word;
					$isbg = false;
				} else {
					$options = $word;
				}
			}
			
			$tags = array();
			
			if($fg) {
				$tags[] = "fg=$fg";
			}
			if($bg) {
				$tags[] = "bg=$bg";
			}
			if($options) {
				$tags[] = "options=$options";
			}
			
			$tag = implode(';', $tags);
		}
	
		$this->$level("<$tag>" . str_replace('<', '\\<', $message) . "</$tag>", $newline);
	}
	
	public function getVerbosity() {
		return $this->output->getVerbosity();
	}
	
	public function setVerbosity($level) {
		$this->output->setVerbosity($level);
	}

}
