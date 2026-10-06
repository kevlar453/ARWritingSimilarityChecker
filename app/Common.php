<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (!function_exists('report_exception_to_telegram')) {
    /**
     * Helper global untuk melaporkan pengecualian ke Telegram Bot institusi.
     */
    function report_exception_to_telegram(\Throwable $exception, array $context = []): string
    {
        return \App\Libraries\TelegramNotifier::notifyException($exception, $context);
    }
}
