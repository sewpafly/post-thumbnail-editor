<?php

class PteLogMessage
{
	public static $ERROR = 1;
	public static $WARN  = 2;
	public static $DEBUG = 4;
	protected $message;
	protected $type;
	protected $date;

	private function getTypeString(){
		switch ($this->type){
		case self::$ERROR:
			return __( "ERROR", PTE_DOMAIN );
			break;
		case self::$WARN:
			return __( "WARNING", PTE_DOMAIN );
			break;
		default:
			return __( "DEBUG", PTE_DOMAIN );
		}
	}

	public function __construct( $type, $message ){
		if ( !is_int( $type ) || !( $type & self::max_log_level() ) ){
			throw new Exception( "Invalid Log Type: '{$type}'" );
		}
		$this->type = $type;
		$this->message = $message;
		$this->date = getdate();
	}

	public function __toString(){
		$type = $this->getTypeString();
		return sprintf( "[%s] - %s", $type, $this->message );
	}
	public function getType(){
		return $this->type;
	}
	public function getMessage(){
		return $this->message;
	}
	public static function max_log_level(){
		return self::$ERROR | self::$WARN | self::$DEBUG;
	}
}

class PteLogger {
	private static $instance;
	private $messages    = array();
	private $counts      = array();
	//private $defaulttype = 4;
	//private $defaulttype = PteLogMessage::$DEBUG;
	private $defaulttype = NULL;

	private function __construct() {
		$this->defaulttype = PteLogMessage::$DEBUG;
	}

	public static function singleton()
	{
		if (!isset(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	private function add_message( $message ){
		$type = $message->getType();

		if ( ! isset( $this->counts[ $type ] ) ){
			$this->counts[ $message->getType() ] = 1;
		}
		else {
			$this->counts[ $message->getType() ]++;
		}
		$this->messages[] = $message;
	}

	public function get_log_count( $type ){
		if ( !isset( $this->counts[ $type ] ) || !is_int( $this->counts[ $type ] ) )
			return 0;
		return $this->counts[$type];
	}

	/*
	 * pte_log
	 */
	private function pte_log($message, $type=NULL){
		if ( ! $message instanceof PteLogMessage ){
			if ( is_string( $message ) ){
				if ( is_null( $type ) ){
					$type = $defaulttype;
				}
				try {
					$message = new PteLogMessage( $type, $message );
				}
				catch ( Exception $e ){
					printf( __( "ERROR Logging Message: %s", PTE_DOMAIN ), $message );
				}
			}
			else{
				return false;
			}
		}
		// If debug isn't enabled only track WARN and ERROR messages
		// (throw away DEBUG messages)
		$options = pte_get_options();
		if ( ! $options['pte_debug'] and $type == PteLogMessage::$DEBUG ){
			return false;
		}

		$this->add_message( $message );
		return true;
	}

	/*
	 * pte_log helper functions
	 */
	public function error( $message ){
		$this->pte_log( $message, PteLogMessage::$ERROR );
	}
	public function warn( $message ){
		$this->pte_log( $message, PteLogMessage::$WARN );
	}
	public function debug($message){
		$this->pte_log( $message, PteLogMessage::$DEBUG );
	}

	public function get_logs( $levels=NULL ){
		// Check that $levels is valid
		$max = PteLogMessage::max_log_level();
		if ( !is_int( $levels ) or $levels < 0 or $levels > $max ){
			$levels = $max;
		}

		foreach ( $this->messages as $message ){
			// If the current Level is requested, add to output
			if ( $levels & $message->getType() ){
				$output[] = $message->__toString();
			}
		}
		return $output;
	}

}
