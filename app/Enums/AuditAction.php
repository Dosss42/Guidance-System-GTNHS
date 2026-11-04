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
    case RecordCreated = 'record_created';
    case RecordUpdated = 'record_updated';
    case NotesUnsealed = 'notes_unsealed';
    case DocumentUploaded = 'document_uploaded';
    case DocumentDownloaded = 'document_downloaded';
    case ReportExported = 'report_exported';
    case PasswordChanged = 'password_changed';
    case BackupCreated = 'backup_created';
    case BackupVerified = 'backup_verified';
    case BackupFailed = 'backup_failed';
    case BackupRestored = 'backup_restored';

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
            self::RecordCreated => 'Record created',
            self::RecordUpdated => 'Record updated',
            self::NotesUnsealed => 'Notes unsealed',
            self::DocumentUploaded => 'Document uploaded',
            self::DocumentDownloaded => 'Document downloaded',
            self::ReportExported => 'Report exported',
            self::PasswordChanged => 'Password changed',
            self::BackupCreated => 'Backup created',
            self::BackupVerified => 'Backup verified',
            self::BackupFailed => 'Backup problem',
            self::BackupRestored => 'Backup restored',
        };
    }

    /**
     * @return list<self>
     */
    public static function systemEvents(): array
    {
        return [self::BackupCreated, self::BackupVerified, self::BackupFailed, self::BackupRestored];
    }

    /**
     * @return list<self>
     */
    public static function fileEvents(): array
    {
        return [self::DocumentUploaded, self::DocumentDownloaded, self::ReportExported];
    }

    /**
     * @return list<self>
     */
    public static function recordEvents(): array
    {
        return [self::RecordCreated, self::RecordUpdated, self::NotesUnsealed];
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
        return [self::AccountCreated, self::AccountUpdated, self::AccountActivated, self::AccountDeactivated, self::PasswordChanged];
    }
}
