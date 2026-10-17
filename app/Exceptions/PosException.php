<?php

namespace App\Exceptions;

use Exception;

/**
 * Generic business-rule violation with an Indonesian, user-facing message
 * (kurang bayar, batas kredit, PIN salah, dsb). Livewire components catch
 * this and surface $e->getMessage() directly as a toast.
 */
class PosException extends Exception
{
    //
}
