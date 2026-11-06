<?php

namespace App\Enums;

enum ReportSignerType: string
{
    case HeadOfPkbm = 'HEAD_OF_PKBM';
    case HomeroomTeacher = 'HOMEROOM_TEACHER';
    case ParentGuardian = 'PARENT_GUARDIAN';

    public function label(): string
    {
        return match ($this) {
            self::HeadOfPkbm => 'Kepala PKBM',
            self::HomeroomTeacher => 'Wali Kelas',
            self::ParentGuardian => 'Orang Tua/Wali',
        };
    }
}
