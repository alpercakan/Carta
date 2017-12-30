<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/backend-systems/includes/database_constants.php');

/**
 * Records the given error to the database
 *
 * If even the error logging fails,
 * it silently ignores
 *
 * The database must have a table with
 * the name ERROR_LOGGING_SYSTEM_TABLE_NAME
 * and that table must have this structure:<pre>
 *      ['ID': INTEGER, PRIMARY_ID, AUTO_INCREMENT;
 *       'MESSAGE': VARCHAR(1000);
 *       'FILE': VARCHAR(1000);
 *       'LOG_TIME': INTEGER;
 *       'PHP_ERROR_MESSAGE': VARCHAR(1000),
 *       'LINE': INTEGER,
 *       'TRACE': VARCHAR(2000),
 *       'EXCEPTION_MESSAGE': VARCHAR(1000),
 *       'IP': VARCHAR(256)]
 *
 * @return boolean True on success, false on failure
 */
function logError($message, $file, $line, $trace, $exceptionMessage): bool
{
    $db = new mysqli(DATABASE_HOST,
        ERROR_LOGGING_SYSTEM_DATABASE_USER_NAME,
        ERROR_LOGGING_SYSTEM_DATABASE_PASSWORD,
        ERROR_LOGGING_SYSTEM_DATABASE_NAME);

    if ($db->connect_error) {
        /*
         * Database connection failed.
         */
        return false;
    }

    $query = $db->prepare("INSERT INTO " . ERROR_LOGGING_SYSTEM_TABLE_NAME .
        " (MESSAGE, FILE, LOG_TIME, PHP_ERROR_MESSAGE, LINE, TRACE, EXCEPTION_MESSAGE, IP) " .
        "VALUES(?, ?, ?, ?, ?, ?, ?, ?)");

    if ($query === false) {
        return false;
    }

    if (!isset($php_errormsg)) {
        $phperrormsg = "";
    } else {
        $phperrormsg = $php_errormsg;
    }

    $logTime = time();

    if ($query->bind_param("ssisisss",
            $message,
            $file,
            $logTime,
            $phperrormsg,
            $line,
            $trace,
            $exceptionMessage, $_SERVER['REMOTE_ADDR'])
        === false
    ) {
        return false;
    }

    if ($query->execute() === false) {
        return false;
    }

    return true;
}

/**
 * Records the given exception to the database
 *
 * If even the error logging failes,
 * it silently ignores
 *
 * The database must have a table with
 * the name ERROR_LOGGING_SYSTEM_TABLE_NAME
 * and that table must have this structure:<pre>
 *      ['ID': INTEGER, PRIMARY_ID, AUTO_INCREMENT;
 *       'MESSAGE': VARCHAR(1000);
 *       'FILE': VARCHAR(1000);
 *       'LOG_TIME': INTEGER;
 *       'PHP_ERROR_MESSAGE': VARCHAR(1000),
 *       'LINE': INTEGER,
 *       'TRACE': VARCHAR(2000),
 *       'EXCEPTION_MESSAGE': VARCHAR(1000),
 *       'IP': VARCHAR(256)]
 *
 * @param $exception Exception
 * @param $errorMessage string Error message
 * (default value is empty string)
 * @return boolean True on success, false on failure
 */
function logException(Exception $exception, $errorMessage = ""): bool
{
    if ($errorMessage === null) {
        $errorMessage = "";
    }

    return logError($errorMessage,
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString(),
        $exception->getMessage());
}

/**
 * Gets the error logs from the database
 * @return array Numerical indexed array
 * which contains the error logs. Every
 * member of the array is also an array,
 * which is associative and has the following
 * keys: 'ID', 'MESSAGE', 'FILE',
 * 'LOG_TIME', 'PHP_ERROR_MESSAGE', 'LINE',
 * 'TRACE', 'EXCEPTION_MESSAGE', 'IP'.
 *
 * @throws Exception
 */
function getErrorLogs(): array
{
    $db = new mysqli(DATABASE_HOST,
        ERROR_LOGGING_SYSTEM_DATABASE_USER_NAME,
        ERROR_LOGGING_SYSTEM_DATABASE_PASSWORD,
        ERROR_LOGGING_SYSTEM_DATABASE_NAME);

    if ($db->connect_error) {
        /*
         * Database connection failed.
         */
        throw new Exception("Database connection failed.");
    }

    $query = $db->prepare("SELECT * FROM " . ERROR_LOGGING_SYSTEM_TABLE_NAME .
        " ORDER BY ID DESC");

    if ($query === false) {
        throw new Exception("Query preparation failed.");
    }

    if ($query->execute() === false) {
        throw new Exception("Query execution failed.");
    }

    if ($query->bind_result($resID, $resMessage, $resFile, $resTime, $resPHPMes, $resLine, $resTrace, $resExcMsg, $resIP)
        === false
    ) {
        throw new Exception("Query result binding failed.");
    }

    $logs = array();

    while (($fetchStatus = $query->fetch()) === true) {
        $logs[] = array('ID' => $resID,
            'MESSAGE' => $resMessage,
            'FILE' => $resFile,
            'LOG_TIME' => $resTime,
            'PHP_ERROR_MESSAGE' => $resPHPMes,
            'LINE' => $resLine,
            'TRACE' => $resTrace,
            'EXCEPTION_MESSAGE' => $resExcMsg,
            'IP' => $resIP);
    }

    if ($fetchStatus === false) {
        throw new Exception("Fetching query result failed.");
    }

    return $logs;
}

function fatalErrorHandler()
{
    $error = error_get_last();

    if ($error["type"] == E_ERROR)
        logError($error["message"],
            $error["file"],
            $error["line"],
            "N\A",
            "PHP Fatal Error! Type: " . $error["type"]);
}

function phpErrorHandler(int $errno, string $errstr, string $errfile, int $errline, array $errcontext): bool
{
    logError($errstr, $errfile, $errline, "N\A", "PHP Error! Errno = ".$errno);

    return true;
}

function phpExceptionHandler(Throwable $throwable): void {
    logError("", $throwable->getFile(), $throwable->getLine(), $throwable->getTraceAsString(), $throwable->getMessage());
}

register_shutdown_function("fatalErrorHandler");
set_error_handler("phpErrorHandler");
set_exception_handler("phpExceptionHandler");
ini_set("display_errors", 0);
ini_set("track_errors", 1);
error_reporting(E_ALL);