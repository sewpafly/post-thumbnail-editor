<?php

class PteLogMessage
{
	public static $ERROR = 1;
	public static $WARN  = 2;
	public static $INFO  = 4;
	public static $DEBUG = 8;
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
		case self::$INFO:
			return __( "INFO", PTE_DOMAIN );
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
		$this->message = trim( $message );
		$this->date = time();
	}

	public function __toString(){
		$type = $this->getTypeString();
		return sprintf('[%-7s][%s][ %s ]',
			$type,
		   	gmdate('c', $this->date),
			$this->message
		);
	}
	public function getType(){
		return $this->type;
	}
	public function getMessage(){
		return $this->message;
	}
	public static function max_log_level(){
		return self::$ERROR | self::$WARN | self::$INFO | self::$DEBUG;
	}
}

interface PteLogHandler {
	public function handle( PteLogMessage $message );
}

class PteLogChromeHandler implements PteLogHandler {
	protected $chrome = null;

	public function __construct() {
		if ( !class_exists( 'ChromePhp' ) ) {
			require_once( PTE_PLUGINPATH . 'php/chromephp/ChromePhp.php' );
		}
		ChromePhp::getInstance()->addSetting(ChromePhp::BACKTRACE_LEVEL, 5);
	}

	/**
	 * Using ChromePhp, log the message
	 */
	public function handle( PteLogMessage $message ) {

		switch( $message->getType() ) {
			case PteLogMessage::$ERROR:
				ChromePhp::error( $message->getMessage() );
				break;
			case PteLogMessage::$WARN:
				ChromePhp::warn( $message->getMessage() );
				break;
			case PteLogMessage::$INFO:
				ChromePhp::info( $message->getMessage() );
				break;
			case PteLogMessage::$DEBUG:
			default:
				ChromePhp::log( $message->getMessage() );
				break;
		}
	}
}

class PteLogFileHandler implements PteLogHandler
{
	protected $filename;

	protected $lines = 0;

	public function __construct()
	{
		$this->filename = self::getLogFileName();
		wp_mkdir_p( dirname( $this->filename ) );
		touch( $this->filename );
	}

	public static function getLogFileUrl()
	{
		// SETS PTE_TMP_DIR and PTE_TMP_URL
		extract( pte_tmp_dir() );
		return $PTE_TMP_URL . 'log.txt';
	}

	public static function getLogFileName()
	{
		// SETS PTE_TMP_DIR and PTE_TMP_URL
		extract( pte_tmp_dir() );
		return $PTE_TMP_DIR . 'log.txt';
	}

	private function logAndTruncate( $message )
	{
		$content = file( $this->filename, FILE_IGNORE_NEW_LINES );
		if ( $content === false ) {
			$content = array();
		}

		$content = array_merge( $content, explode( "\n", (string) $message ) );

		if ( count( $content ) > $this->lines ) {
			$content = array_slice( $content, $this->lines * -1);
		}

		file_put_contents( $this->filename,
			implode( "\n", $content )
		);
	}

	public function handle( PteLogMessage $message )
	{
		if ( isset( $this->lines ) && $this->lines )
			logAndTruncate( $message );
		// append to file
		$fp = fopen( $this->filename, 'a+' );
		fwrite( $fp, $message . "\n" );
		fclose($fp);
	}

}

class PteLogger implements PteLogHandler {
	private static $instance;
	private $messages    = array();
	private $counts      = array();
	//private $defaulttype = 4;
	//private $defaulttype = PteLogMessage::$DEBUG;
	private $defaulttype = NULL;
	private $handlers = array();

	private function __construct() {
		$this->defaulttype = PteLogMessage::$DEBUG;
		$options = pte_get_options();

		# Add chrome log handler
		if ( $options['pte_debug_out_chrome'] )
			$this->handlers[] = new PteLogChromeHandler;
		# Add file log handler
		if ( $options['pte_debug_out_file'] )
			$this->handlers[] = new PteLogFileHandler;

		$this->handlers[] = $this;
	}

	public static function singleton()
	{
		if (!isset(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className();
		}
		return self::$instance;
	}


	public function handle( PteLogMessage $message ) {
		//self::singleton()->chrome_log( $message );
		$type = $message->getType();

		if ( ! isset( $this->counts[ $type ] ) ){
			$this->counts[ $message->getType() ] = 1;
		}
		else {
			$this->counts[ $message->getType() ]++;
		}
		$this->messages[] = $message;
	}

	private function add_message( $message ) {
		foreach ( $this->handlers as $handler ){
			$handler->handle( $message );
		}
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
					$type = $this->defaulttype;
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
	public static function error( $message ){
		self::singleton()->pte_log( $message, PteLogMessage::$ERROR );
	}
	public static function warn( $message ){
		self::singleton()->pte_log( $message, PteLogMessage::$WARN );
	}
	public static function debug($message){
		self::singleton()->pte_log( $message, PteLogMessage::$DEBUG );
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
