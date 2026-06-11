<?php

namespace App\Support\Siakad;

final class SiakadResource
{
    public const HEALTH = 'health';

    public const STUDY_PROGRAMS = 'study_programs';

    public const PROGRAMS = 'programs';

    public const STATUS_AWAL = 'status_awal';

    public const ACADEMIC_YEARS = 'academic_years';

    public const COHORTS = 'cohorts';

    public const MAHASISWA_SYNC = 'mahasiswa_sync';

    public const LECTURERS = 'lecturers';

    public const KHS = 'khs';

    public const CLASSES = 'classes';

    public const CLASS_PARTICIPANTS = 'class_participants';

    public const GRADES = 'grades';

    public const CONVERSION_GRADES = 'conversion_grades';

    public const STUDENT_EXIT = 'student_exit';

    public const GRADUATION_STATUS = 'graduation_status';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::HEALTH,
            self::STUDY_PROGRAMS,
            self::PROGRAMS,
            self::STATUS_AWAL,
            self::ACADEMIC_YEARS,
            self::COHORTS,
            self::MAHASISWA_SYNC,
            self::LECTURERS,
            self::KHS,
            self::CLASSES,
            self::CLASS_PARTICIPANTS,
            self::GRADES,
            self::CONVERSION_GRADES,
            self::STUDENT_EXIT,
            self::GRADUATION_STATUS,
        ];
    }
}
