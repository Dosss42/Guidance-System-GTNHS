<?php

namespace App\Enums;

/**
 * Actions recorded in the audit log. Descriptions must never contain
 * passwords or confidential counseling content.
 */
enum AuditAction: string
{
    case Login = 'login';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case AccountCreated = 'account_created';
    case AccountUpdated = 'account_updated';
    case AccountActivated = 'account_activated';
    case AccountDeactivated = 'account_deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Signed in',
            self::LoginFailed => 'Failed sign-in',
            self::Logout => 'Signed out',
            self::AccountCreated => 'Account created',
            self::AccountUpdated => 'Account updated',
            self::AccountActivated => 'Account activated',
            self::AccountDeactivated => 'Account deactivated',
        };
    }

    /**
     * @return list<self>
     */
    public static function authenticationEvents(): array
    {
        return [self::Login, self::LoginFailed, self::Logout];
    }

    /**
     * @return list<self>
     */
    public static function accountEvents(): array
    {
        return [self::AccountCreated, self::AccountUpdated, self::AccountActivated, self::AccountDeactivated];
    }
}
