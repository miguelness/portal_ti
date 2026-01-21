<?php
/**
 * Sistema de logging detalhado para debug de transações
 */

class DebugLogger {
    private static $logFile = __DIR__ . '/transaction_debug.log';
    
    public static function log($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        $caller = '';
        if (isset($backtrace[1])) {
            $caller = basename($backtrace[1]['file']) . ':' . $backtrace[1]['line'];
            if (isset($backtrace[1]['function'])) {
                $caller .= ' (' . $backtrace[1]['function'] . ')';
            }
        }
        
        $logEntry = "[$timestamp] [$caller] $message";
        
        if (!empty($context)) {
            $logEntry .= ' | Context: ' . json_encode($context);
        }
        
        $logEntry .= PHP_EOL;
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function logTransaction($pdo, $action) {
        $inTransaction = $pdo->inTransaction();
        self::log("TRANSACTION $action", [
            'in_transaction' => $inTransaction,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ]);
    }
    
    public static function logError($error, $context = []) {
        self::log("ERROR: " . $error, $context);
    }
    
    public static function clearLog() {
        if (file_exists(self::$logFile)) {
            unlink(self::$logFile);
        }
    }
    
    public static function getLog() {
        if (file_exists(self::$logFile)) {
            return file_get_contents(self::$logFile);
        }
        return '';
    }
}
?>