<?php

namespace Esign\EmailWhitelisting\Models;

use Illuminate\Database\Eloquent\Model;

class WhitelistedEmailAddress extends Model
{
    public const TABLE = 'whitelist_email_addresses';

    protected $table = self::TABLE;

    protected $fillable = [
        'email',
        'redirect_email'
    ];

    protected $casts = [
        'redirect_email' => 'boolean',
    ];

    // --- Getters & setters ---

    // --- Relations ---

    // --- QueryScopes ---

    // --- Functions ---
}
