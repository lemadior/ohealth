<?php

declare(strict_types=1);

namespace App\Traits;

use App\Classes\Cipher\Exceptions\CipherApiException;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use JsonException;
use Throwable;

trait LogsExceptions
{
    /**
     * Log error messages if any exception occur during database interaction.
     *
     * @param  Exception|Throwable  $exception
     * @param  string  $message
     * @return void
     */
    protected function logDatabaseErrors(Exception|Throwable $exception, string $message): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];

        Log::channel('db_errors')->error($message, [
            'class' => $caller['class'] ?? 'unknown_class',
            'method' => $caller['function'] ?? 'unknown_method',
            'error_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line_in_file' => $exception->getLine()
        ]);
    }

    /**
     * Handle eHealth API exceptions with logging and user-facing flash message.
     *
     * @param  ConnectionException|EHealthValidationException|EHealthResponseException  $exception
     * @param  string  $logMessage
     * @return void
     */
    protected function handleEHealthExceptions(
        ConnectionException|EHealthValidationException|EHealthResponseException $exception,
        string $logMessage
    ): void {
        if ($exception instanceof ConnectionException) {
            Log::channel('e_health_errors')->error($logMessage, [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
            Session::flash('error', __('messages.connection_exception'));

            return;
        }

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];

        Log::channel('e_health_errors')->error($logMessage, [
            'class' => $caller['class'] ?? 'unknown_class',
            'method' => $caller['function'] ?? 'unknown_method',
            'exception_type' => get_class($exception),
            'error_message' => $exception->getDetails()
        ]);

        $errorMessage = $exception instanceof EHealthValidationException
            ? $exception->getFormattedMessage()
            : __('patients.messages.ehealth_error', ['message' => $exception->getMessage()]);
        Session::flash('error', $errorMessage);
    }

    /**
     * Handle Cipher API exceptions with logging and user-facing flash message.
     *
     * @param  ConnectionException|CipherApiException|JsonException  $exception
     * @param  string  $logMessage
     * @return void
     */
    protected function handleCipherExceptions(
        ConnectionException|CipherApiException|JsonException $exception,
        string $logMessage
    ): void {
        if ($exception instanceof ConnectionException) {
            Log::channel('e_health_errors')->error($logMessage, [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
            Session::flash('error', __('messages.connection_exception'));

            return;
        }

        if ($exception instanceof JsonException) {
            $this->logDatabaseErrors($exception, $logMessage);
            Session::flash('error', __('patients.messages.data_processing_error'));

            return;
        }

        Log::channel('api_errors')->error($logMessage, [
            'message' => $exception->response->json(['message']),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
        Session::flash('error', $exception->getMessage());
    }
}
