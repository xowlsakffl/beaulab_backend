<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use Illuminate\Support\Facades\DB;

final class HospitalChatBotConsultationReadForStaffQuery
{
    public function attentionCount(int $staffId): int
    {
        return DB::table('hospital_chatbot_consultations as c')
            ->where(function ($query) use ($staffId) {
                $query->where('c.status', 'WAITING')
                    ->orWhere(function ($connected) use ($staffId) {
                        $connected->where('c.status', 'CONNECTED')->where('c.assigned_staff_id', $staffId)
                            ->whereExists(function ($messages) use ($staffId) {
                                $messages->selectRaw('1')->from('hospital_chatbot_consultation_messages as m')
                                    ->leftJoin('hospital_chatbot_consultation_reads as r', function ($join) use ($staffId) {
                                        $join->on('r.consultation_id', '=', 'm.consultation_id')->where('r.staff_id', $staffId);
                                    })
                                    ->whereColumn('m.consultation_id', 'c.id')
                                    ->where('m.sender_type', 'HOSPITAL_USER')
                                    ->whereRaw('m.sequence > COALESCE(r.last_read_sequence, 0)');
                            });
                    });
            })->count();
    }

    public function mark(int $consultationId, int $staffId, int $sequence): void
    {
        DB::transaction(function () use ($consultationId, $staffId, $sequence) {
            DB::table('hospital_chatbot_consultation_reads')->insertOrIgnore([
                'consultation_id' => $consultationId,
                'staff_id' => $staffId,
                'last_read_sequence' => 0,
                'updated_at' => now(),
            ]);
            DB::table('hospital_chatbot_consultation_reads')
                ->where('consultation_id', $consultationId)->where('staff_id', $staffId)
                ->where('last_read_sequence', '<', $sequence)
                ->update(['last_read_sequence' => $sequence, 'updated_at' => now()]);
        });
    }

    public function counts(array $ids, int $staffId): array
    {
        return DB::table('hospital_chatbot_consultation_messages as m')
            ->leftJoin('hospital_chatbot_consultation_reads as r', function ($join) use ($staffId) {
                $join->on('r.consultation_id', '=', 'm.consultation_id')->where('r.staff_id', $staffId);
            })
            ->whereIn('m.consultation_id', $ids)->where('m.sender_type', 'HOSPITAL_USER')
            ->whereRaw('m.sequence > COALESCE(r.last_read_sequence, 0)')
            ->groupBy('m.consultation_id')->selectRaw('m.consultation_id, COUNT(*) as total')
            ->pluck('total', 'consultation_id')->all();
    }
}
