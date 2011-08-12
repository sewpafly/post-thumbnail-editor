<?php
class PteLogMessage
{
	protected $message;
	protected $type;
	protected $date;
	public function __construct($type, $message){
		$this->type = $type;
		$this->message = $message;
		$this->date = getdate();
	}

	public function __toString(){
		return $this->message;
	}
	public function getType(){
		return $this->type;
	}
}

class PteError extends PteLogMessage {
	public function __construct($message){
		parent::__construct("ERROR", $message);
	}
}
class PteDebug extends PteLogMessage {
	public function __construct($message){
		parent::__construct("DEBUG", $message);
	}
}
?>
