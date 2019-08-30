<?php
namespace Syncro\Interaction;

class SynchronizerInteractionAdapter extends Interaction {

	private $interaction;
	
	public function __construct($interaction) {
		$this->interaction = $interaction;
	}
	
  public function choice($message, $options, $default = null) {
    return $this->interaction ? $this->interaction->choice($message, $options, $default) : $default;
  }
  
  public function auto() {
    return $this->interaction ? $this->interaction->auto() : false;
  }
  
  public function interactive() {
    return $this->interaction ? $this->interaction->interactive() : false;
  }
  
  public function setAuto($auto) {
    if($this->interaction) {
      $this->interaction->setAuto($auto);
    }
  }
	
}
