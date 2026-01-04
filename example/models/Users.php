<?php

use PaigeJulianne\NanoORM;

/**
 * Users Model
 *
 * This class maps to the 'users' table in your database.
 * Extends NanoORM for all database operations.
 */
class Users extends NanoORM
{
    // Optionally override the table name
    // const TABLE_OVERRIDE = 'my_users';

    // Optionally specify a different connection
    // const CONNECTION = 'secondary';

    /**
     * Example: Get user's full name
     */
    public function getFullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Example: Check if user is active
     */
    public function isActive(): bool
    {
        return (bool)($this->is_active ?? false);
    }

    /**
     * Example: Get all active users
     */
    public static function getActiveUsers(): array
    {
        return self::getAllObjects('id', [
            ['is_active', null, '=', 1]
        ], 'AND', true);
    }
}
