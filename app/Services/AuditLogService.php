<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Field yang tidak boleh pernah disimpan ke audit log.
     *
     * Password hanya boleh direpresentasikan sebagai:
     * password_changed => true
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'remember_token',
        'token',
        'access_token',
        'refresh_token',
    ];

    /**
     * Mencatat aktivitas ke audit log.
     */
    public function record(
        Request $request,
        string $action,
        string $module,
        string $description,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        $cleanOldValues = $this->sanitizeValues(
            $oldValues
        );

        $cleanNewValues = $this->sanitizeValues(
            $newValues
        );

        return AuditLog::create([
            'user_id' => $request->user()?->id,

            'action' => trim($action),

            'module' => trim($module),

            'auditable_type' =>
                $auditable?->getMorphClass(),

            'auditable_id' =>
                $auditable?->getKey(),

            'description' =>
                trim($description),

            'old_values' =>
                $cleanOldValues,

            'new_values' =>
                $cleanNewValues,

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),
        ]);
    }

    /**
     * Membersihkan data sebelum disimpan.
     *
     * Field sensitif dihapus agar password/token
     * tidak pernah masuk ke audit log.
     */
    private function sanitizeValues(
        ?array $values
    ): ?array {
        if ($values === null) {
            return null;
        }

        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower(
                trim((string) $key)
            );

            if (
                in_array(
                    $normalizedKey,
                    self::SENSITIVE_FIELDS,
                    true
                )
            ) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] =
                    $this->sanitizeValues($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
