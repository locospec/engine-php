<?php

namespace Locospec\Engine;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger
{
    private MonologLogger $logger;

    private TestHandler $testHandler;

    // Constructor to initialize the logger with a log file
    public function __construct(string $logFilePath, int $maxFiles = 7)
    {
        $this->logger = new MonologLogger('lcs_logger');

        // Main handler for writing logs to file
        $fileHandler = new RotatingFileHandler($logFilePath, $maxFiles, Level::Debug);
        $this->logger->pushHandler($fileHandler);

        // BufferHandler stores logs temporarily (without flushing immediately)
        // This doesnt have the way to get the logs, It addes the logs to the file itself once buffer is flushed
        // $this->bufferHandler = new BufferHandler($fileHandler);
        // $this->logger->pushHandler($this->bufferHandler);

        // TestHandler for capturing logs in memory (without writing to file)
        $this->testHandler = new TestHandler;
        $this->logger->pushHandler($this->testHandler);
    }

    // Method to log an info message
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    // Method to log a warning message
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    // Method to log an error message
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    // Retrieve log records from the in-memory TestHandler
    public function getLogs(): array
    {
        return array_map(function ($record) {
            // dd($record);
            return [
                'level' => $record['level_name'],
                'message' => $record['message'],
                'context' => $record['context'],
                'datetime' => $record['datetime']->format('Y-m-d H:i:s.u'),
            ];
        }, $this->testHandler->getRecords());
    }
}
