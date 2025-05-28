<?php

namespace LCSEngine;

use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger
{
    private MonologLogger $logger;

    private TestHandler $testHandler;

    private bool $query_logs;

    private bool $enabled;

    private int $retention_days;

    // Constructor to initialize the logger with a log file
    public function __construct(array $config)
    {
        $this->query_logs = $config['query_logs'] ?? false;
        $this->enabled = $config['enabled'] ?? false;
        $this->retention_days = $config['retention_days'] ?? 7;
        $this->logger = new MonologLogger('lcs_logger');

        if ($this->enabled) {
            // Main handler for writing logs to file
            $fileHandler = new RotatingFileHandler($config['file_path'], $this->retention_days, Level::Debug);
            $this->logger->pushHandler($fileHandler);

            // BufferHandler stores logs temporarily (without flushing immediately)
            // This doesnt have the way to get the logs, It addes the logs to the file itself once buffer is flushed
            // $this->bufferHandler = new BufferHandler($fileHandler);
            // $this->logger->pushHandler($this->bufferHandler);

            // TestHandler for capturing logs in memory (without writing to file)
            $this->testHandler = new TestHandler;
            $this->logger->pushHandler($this->testHandler);

            $consoleHandler = new StreamHandler('php://stdout', Level::Debug);
            $this->logger->pushHandler($consoleHandler);
        } else {
            // NullHandler drops all records
            $this->logger->pushHandler(new NullHandler);
        }
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
    public function getLogs(?string $type = null): array
    {
        if (! $this->enabled || ! $this->testHandler) {
            return [];
        }

        if (isset($type)) {
            return array_reduce(
                $this->testHandler->getRecords(),
                function (array $carry, $record) use ($type) {
                    if (($record['context']['type'] ?? null) === $type) {
                        $carry[] = [
                            'level' => $record['level_name'],
                            'message' => $record['message'],
                            'context' => $record['context'],
                            'datetime' => $record['datetime']->format('Y-m-d H:i:s.u'),
                        ];
                    }

                    return $carry;
                },
                []
            );
        } else {
            return array_map(function ($record) {
                return [
                    'level' => $record['level_name'],
                    'message' => $record['message'],
                    'context' => $record['context'],
                    'datetime' => $record['datetime']->format('Y-m-d H:i:s.u'),
                ];
            }, $this->testHandler->getRecords());
        }
    }

    // Check if query logs are enabled
    public function isQueryLogsEnabled(): bool
    {
        return $this->query_logs;
    }

    // Check if logs are enabled
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
