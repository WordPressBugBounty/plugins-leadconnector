<?php
/**
 * Logger class for LeadConnector
 *
 * This file contains the Logger class which provides logging functionality
 * for the LeadConnector plugin.
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/includes/Logger
 */

/**
 * Logger class
 *
 * A singleton class that handles logging of errors, warnings, and info messages
 * to a log file with proper formatting.
 */
class Logger {
    /**
     * The single instance of the class.
     *
     * @var Logger|null
     */
    private static $instance = null;

    /**
     * Log file path.
     *
     * @var string
     */
    private $log_file;

    /**
     * Log levels.
     *
     * @var array
     */
    private $log_levels = [
        'ERROR'   => 'ERROR',
        'WARNING' => 'WARNING',
        'INFO'    => 'INFO',
        'DEBUG'   => 'DEBUG',
    ];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/leadconnector-logs/leadconnector-' . date('Y-m-d') . '.log';
        
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }

    /**
     * Get the singleton instance.
     *
     * @return Logger The singleton instance.
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {
        // Prevent cloning
    }

    /**
     * Prevent unserializing of the instance.
     */
    public function __wakeup() {
        // Prevent unserializing
    }

    /**
     * Log a message with the specified level.
     *
     * @param string $level   The log level.
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * 
     * @return bool True on success, false on failure.
     */
    private function log($level, $message, $context = []) {
        if (!isset($this->log_levels[$level])) {
            return false;
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted_message = $this->format_message($timestamp, $level, $message, $context);
        
        return $this->write_to_log($formatted_message);
    }

    /**
     * Format the log message.
     *
     * @param string $timestamp The timestamp.
     * @param string $level     The log level.
     * @param string $message   The message to log.
     * @param array  $context   Additional context data.
     * 
     * @return string The formatted message.
     */
    private function format_message($timestamp, $level, $message, $context = []) {
        $formatted = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $formatted .= " Context: " . json_encode($context, JSON_PRETTY_PRINT);
        }
        
        // Add backtrace for errors
        if ($level === $this->log_levels['ERROR']) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = isset($backtrace[2]) ? $backtrace[2] : $backtrace[0];
            
            $file = isset($caller['file']) ? $caller['file'] : 'unknown';
            $line = isset($caller['line']) ? $caller['line'] : 'unknown';
            $function = isset($caller['function']) ? $caller['function'] : 'unknown';
            
            $formatted .= " | Called from: {$file}:{$line} in function {$function}()";
        }
        
        return $formatted;
    }

    /**
     * Write the message to the log file.
     *
     * @param string $message The formatted message.
     * 
     * @return bool True on success, false on failure.
     */
    private function write_to_log($message) {
        $message .= PHP_EOL;
        
        return file_put_contents($this->log_file, $message, FILE_APPEND);
    }

    /**
     * Log an error message.
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * 
     * @return bool True on success, false on failure.
     */
    public function error($message, $context = []) {
        return $this->log($this->log_levels['ERROR'], $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * 
     * @return bool True on success, false on failure.
     */
    public function warning($message, $context = []) {
        return $this->log($this->log_levels['WARNING'], $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * 
     * @return bool True on success, false on failure.
     */
    public function info($message, $context = []) {
        return $this->log($this->log_levels['INFO'], $message, $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * 
     * @return bool True on success, false on failure.
     */
    public function debug($message, $context = []) {
        return $this->log($this->log_levels['DEBUG'], $message, $context);
    }

    /**
     * Clear the log file.
     *
     * @return bool True on success, false on failure.
     */
    public function clear_log() {
        return file_put_contents($this->log_file, '');
    }

    /**
     * Get the log file path.
     *
     * @return string The log file path.
     */
    public function get_log_file() {
        return $this->log_file;
    }
}
