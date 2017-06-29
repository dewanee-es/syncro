<?php
namespace Syncro\Interaction;

use Symfony\Component\Console\Question\ChoiceQuestion;

class InputInteraction extends Interaction {

	private $helper;
	private $input;
	private $output;
	private $auto = false;
	
	public function __construct($helper, $input, $output) {
		$this->helper = $helper;
		$this->input = $input;
		$this->output = $output;
	}

	public function choice($message, $options, $default = null) {
		if($default && isset($options[$default]))
			$options[$default] .= ' (default)';
			
		$question = new ChoiceQuestion('=> ' . $message, $options, $default);
		return $this->helper->ask($this->input, $this->output, $question);
	}
	
	public function auto() {
		return $this->auto;
	}
	
	public function interactive() {
		return $this->input->isInteractive();
	}
	
	public function setAuto($auto) {
		$this->auto = $auto;
		
		if($auto) {	// Setting auto mode also sets non interactive
			$this->input->setInteractive(false);
		}
	}

}
