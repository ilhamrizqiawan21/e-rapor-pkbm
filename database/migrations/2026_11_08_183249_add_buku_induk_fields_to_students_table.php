<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            // Data Kependudukan & Pribadi (Standar Dapodik & Dukcapil)
            $table->string('nik', 20)->nullable()->after('national_student_number');
            $table->string('family_card_number', 20)->nullable()->after('nik');
            $table->string('nickname', 100)->nullable()->after('full_name');
            $table->string('religion', 50)->nullable()->after('birth_date');
            $table->string('citizenship', 50)->default('WNI')->nullable()->after('religion');
            $table->unsignedSmallInteger('birth_order')->nullable()->after('citizenship');
            $table->unsignedSmallInteger('siblings_count')->nullable()->after('birth_order');

            // Kontak & Tempat Tinggal
            $table->string('postal_code', 10)->nullable()->after('address');
            $table->string('phone_number', 30)->nullable()->after('postal_code');
            $table->string('living_with', 50)->nullable()->after('phone_number');
            $table->string('transportation_mode', 50)->nullable()->after('living_with');

            // Data Orang Tua Kandung & Wali
            $table->string('father_name', 255)->nullable()->after('transportation_mode');
            $table->string('father_nik', 20)->nullable()->after('father_name');
            $table->string('father_birth_place', 100)->nullable()->after('father_nik');
            $table->date('father_birth_date')->nullable()->after('father_birth_place');
            $table->string('father_education', 50)->nullable()->after('father_birth_date');
            $table->string('father_occupation', 100)->nullable()->after('father_education');
            $table->string('father_income', 100)->nullable()->after('father_occupation');
            $table->string('father_phone', 30)->nullable()->after('father_income');

            $table->string('mother_name', 255)->nullable()->after('father_phone');
            $table->string('mother_nik', 20)->nullable()->after('mother_name');
            $table->string('mother_birth_place', 100)->nullable()->after('mother_nik');
            $table->date('mother_birth_date')->nullable()->after('mother_birth_place');
            $table->string('mother_education', 50)->nullable()->after('mother_birth_date');
            $table->string('mother_occupation', 100)->nullable()->after('mother_education');
            $table->string('mother_income', 100)->nullable()->after('mother_occupation');
            $table->string('mother_phone', 30)->nullable()->after('mother_income');

            $table->string('guardian_relation', 50)->nullable()->after('guardian_name');
            $table->string('guardian_occupation', 100)->nullable()->after('guardian_relation');
            $table->string('guardian_phone', 30)->nullable()->after('guardian_occupation');

            // Data Pendidikan Sebelumnya & Penerimaan di PKBM
            $table->string('previous_school_name', 255)->nullable()->after('guardian_phone');
            $table->string('previous_diploma_number', 100)->nullable()->after('previous_school_name');
            $table->date('entry_date')->nullable()->after('previous_diploma_number');

            // Keterangan Kelulusan
            $table->string('graduation_certificate_number', 100)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn([
                'nik',
                'family_card_number',
                'nickname',
                'religion',
                'citizenship',
                'birth_order',
                'siblings_count',
                'postal_code',
                'phone_number',
                'living_with',
                'transportation_mode',
                'father_name',
                'father_nik',
                'father_birth_place',
                'father_birth_date',
                'father_education',
                'father_occupation',
                'father_income',
                'father_phone',
                'mother_name',
                'mother_nik',
                'mother_birth_place',
                'mother_birth_date',
                'mother_education',
                'mother_occupation',
                'mother_income',
                'mother_phone',
                'guardian_relation',
                'guardian_occupation',
                'guardian_phone',
                'previous_school_name',
                'previous_diploma_number',
                'entry_date',
                'graduation_certificate_number',
            ]);
        });
    }
};
