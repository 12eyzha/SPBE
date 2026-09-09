<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AuditLogBackupExport implements FromCollection, WithHeadings
{
    /**
     * ============================================================
     * DATA AUDIT LOG YANG AKAN DIEKSPORT
     * ============================================================
     */
    private Collection $logs;

    /**
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     *
     * Menerima collection AuditLog yang sebelumnya sudah diambil
     * oleh controller.
     */
    public function __construct(Collection $logs)
    {
        $this->logs = $logs;
    }

    /**
     * ============================================================
     * HEADINGS EXCEL
     * ============================================================
     */
    public function headings(): array
    {
        return [
            'ID',
            'User ID',
            'Nama User',
            'Email User',
            'Action',
            'Module',
            'Auditable Type',
            'Auditable ID',
            'Description',
            'Old Values',
            'New Values',
            'IP Address',
            'User Agent',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * ============================================================
     * DATA ROW EXCEL
     * ============================================================
     */
    public function collection(): Collection
    {
        return $this->logs->map(function (AuditLog $log): array {
            return [
                $log->id,
                $log->user_id,
                $log->user?->name,
                $log->user?->email,

                $log->action,

                $log->module,

                $log->auditable_type,

                $log->auditable_id,

                $log->description,

                $this->jsonValue($log->old_values),

                $this->jsonValue($log->new_values),

                $log->ip_address,

                $log->user_agent,

                $log->created_at?->format('Y-m-d H:i:s'),

                $log->updated_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * ============================================================
     * JSON FORMATTER
     * ============================================================
     *
     * old_values dan new_values sudah di-cast menjadi array
     * oleh model AuditLog.
     */
    private function jsonValue(?array $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}