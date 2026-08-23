<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\AppSetting;
use App\Models\AssessmentRubricItem;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Notice;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->siwesFaculties() as $facultyData) {
            $faculty = Faculty::query()->updateOrCreate(
                ['code' => $facultyData['code']],
                ['name' => $facultyData['name'], 'is_active' => true],
            );

            foreach ($facultyData['departments'] as $departmentData) {
                Department::query()->updateOrCreate(
                    ['faculty_id' => $faculty->id, 'code' => $departmentData['code']],
                    ['name' => $departmentData['name'], 'is_active' => true],
                );
            }
        }

        AcademicLevel::query()
            ->where('level', '<', 200)
            ->update(['is_active' => false]);

        foreach ([200, 300, 400, 500, 600] as $level) {
            AcademicLevel::query()->updateOrCreate(
                ['level' => $level],
                ['name' => "{$level}L", 'is_active' => true],
            );
        }

        AcademicLevel::query()
            ->where('level', '>=', 200)
            ->get()
            ->each(fn (AcademicLevel $level): bool => $level->update(['name' => "{$level->level}L"]));

        $session = AcademicSession::query()->updateOrCreate(
            ['name' => '2026/2027'],
            [
                'starts_on' => '2026-09-01',
                'ends_on' => '2027-08-31',
                'is_active' => true,
            ],
        );
        $session->activate();

        foreach ($this->settings() as $setting) {
            AppSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }

        foreach ($this->rubricItems() as $rubricItem) {
            AssessmentRubricItem::query()->updateOrCreate(
                ['name' => $rubricItem['name']],
                $rubricItem,
            );
        }

        foreach ($this->notices() as $notice) {
            Notice::query()->updateOrCreate(
                ['title' => $notice['title']],
                $notice,
            );
        }
    }

    /**
     * @return array<int, array{code: string, name: string, departments: array<int, array{code: string, name: string}>}>
     */
    private function siwesFaculties(): array
    {
        return [
            [
                'code' => 'AGRIC',
                'name' => 'Faculty of Agricultural Science',
                'departments' => [
                    ['code' => 'AGE', 'name' => 'Agric Economics & Extension'],
                    ['code' => 'ANS', 'name' => 'Animal Science'],
                    ['code' => 'CRS', 'name' => 'Crop Science'],
                    ['code' => 'FSH', 'name' => 'Fishery'],
                    ['code' => 'FST', 'name' => 'Food Science and Tech'],
                    ['code' => 'SOS', 'name' => 'Soil Science'],
                ],
            ],
            [
                'code' => 'ART',
                'name' => 'Faculty of Art',
                'departments' => [
                    ['code' => 'MUS', 'name' => 'Music'],
                    ['code' => 'THA', 'name' => 'Theater Art'],
                ],
            ],
            [
                'code' => 'BMS',
                'name' => 'Faculty of Basic Medical Science',
                'departments' => [
                    ['code' => 'ANA', 'name' => 'Anatomy'],
                    ['code' => 'PHY', 'name' => 'Physiology'],
                ],
            ],
            [
                'code' => 'EDU',
                'name' => 'Faculty of Education',
                'departments' => [
                    ['code' => 'VED', 'name' => 'Vocational Education'],
                    ['code' => 'SED', 'name' => 'Science Education'],
                ],
            ],
            [
                'code' => 'ENG',
                'name' => 'Faculty of Engineering',
                'departments' => [
                    ['code' => 'CVE', 'name' => 'Civil Engineering'],
                    ['code' => 'CHE', 'name' => 'Chemical Engineering'],
                    ['code' => 'EEE', 'name' => 'Electrical/Electronic Engineering'],
                    ['code' => 'MEE', 'name' => 'Mechanical Engineering'],
                ],
            ],
            [
                'code' => 'ENV',
                'name' => 'Faculty of Environmental Sciences',
                'departments' => [
                    ['code' => 'ARC', 'name' => 'Architecture'],
                    ['code' => 'EMT', 'name' => 'Environmental Management'],
                    ['code' => 'ESM', 'name' => 'Estate Management'],
                    ['code' => 'URP', 'name' => 'Urban and Regional Planning'],
                ],
            ],
            [
                'code' => 'MGT',
                'name' => 'Faculty of Management Sciences',
                'departments' => [
                    ['code' => 'ACC', 'name' => 'Accountancy'],
                ],
            ],
            [
                'code' => 'NAT',
                'name' => 'Faculty of Natural Science',
                'departments' => [
                    ['code' => 'BCH', 'name' => 'Biochemistry'],
                    ['code' => 'BIS', 'name' => 'Biological Science'],
                    ['code' => 'MCB', 'name' => 'Microbiology'],
                ],
            ],
            [
                'code' => 'PHR',
                'name' => 'Faculty of Pharmaceutical Sciences',
                'departments' => [
                    ['code' => 'CPM', 'name' => 'Clinical Pharmacy and Pharmacy Management'],
                    ['code' => 'PPT', 'name' => 'Pharmaceutics and Pharmaceutical Technology'],
                    ['code' => 'PMB', 'name' => 'Pharmaceutical Microbiology and Biotechnology'],
                    ['code' => 'PGT', 'name' => 'Pharmacognosy and Traditional Medicines'],
                    ['code' => 'PTO', 'name' => 'Pharmacology and Toxicology'],
                ],
            ],
            [
                'code' => 'PHYSCI',
                'name' => 'Faculty of Physical Sciences',
                'departments' => [
                    ['code' => 'CSC', 'name' => 'Computer Science'],
                    ['code' => 'GEO', 'name' => 'Geology'],
                    ['code' => 'IPH', 'name' => 'Industrial Physics'],
                    ['code' => 'MTH', 'name' => 'Mathematics'],
                    ['code' => 'PIC', 'name' => 'Pure and Industrial Chemistry'],
                    ['code' => 'STA', 'name' => 'Statistics'],
                ],
            ],
            [
                'code' => 'SOC',
                'name' => 'Faculty of Social Sciences',
                'departments' => [
                    ['code' => 'LIS', 'name' => 'Library Science and Information Tech.'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{group: string, key: string, value: mixed, type: string, is_public: bool}>
     */
    private function settings(): array
    {
        return [
            ['group' => 'site', 'key' => 'site.name', 'value' => 'COOU SIWES Portal', 'type' => 'string', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.welcome.enabled', 'value' => true, 'type' => 'boolean', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.welcome.title', 'value' => 'Welcome to COOU SIWES', 'type' => 'string', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.welcome.message', 'value' => 'Access your industrial training portal, follow official notices, and continue your SIWES workflow securely.', 'type' => 'string', 'is_public' => true],
            ['group' => 'site', 'key' => 'site.welcome.duration_seconds', 'value' => 6, 'type' => 'integer', 'is_public' => true],
            ['group' => 'academic', 'key' => 'academic.active_session', 'value' => '2026/2027', 'type' => 'string', 'is_public' => false],
            ['group' => 'payment', 'key' => 'payment.provider', 'value' => 'korapay', 'type' => 'string', 'is_public' => false],
            ['group' => 'otp', 'key' => 'otp.ttl_minutes', 'value' => 10, 'type' => 'integer', 'is_public' => false],
            ['group' => 'upload', 'key' => 'upload.max_mb', 'value' => 5, 'type' => 'integer', 'is_public' => false],
            ['group' => 'theme', 'key' => 'theme.default_mode', 'value' => 'system', 'type' => 'string', 'is_public' => true],
            ['group' => 'notifications', 'key' => 'notifications.push_enabled', 'value' => true, 'type' => 'boolean', 'is_public' => false],
        ];
    }

    /**
     * @return array<int, array{name: string, description: string, max_score: int, weight: int, sort_order: int, is_active: bool}>
     */
    private function rubricItems(): array
    {
        return [
            [
                'name' => 'Punctuality',
                'description' => 'Attendance, timeliness, and reliability during industrial attachment.',
                'max_score' => 10,
                'weight' => 1,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Technical Skill',
                'description' => 'Ability to apply relevant professional and technical skills.',
                'max_score' => 10,
                'weight' => 2,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Communication',
                'description' => 'Clarity, documentation quality, and workplace communication.',
                'max_score' => 10,
                'weight' => 1,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Professional Conduct',
                'description' => 'Ethics, teamwork, initiative, and adherence to workplace standards.',
                'max_score' => 10,
                'weight' => 2,
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, body: string, audience: string, tone: string, is_pinned: bool, published_at: string}>
     */
    private function notices(): array
    {
        return [
            [
                'title' => 'SIWES student portal is open',
                'body' => 'Students can sign in to complete profile details, review activation status, and continue SIWES registration requirements.',
                'audience' => 'students',
                'tone' => 'success',
                'is_pinned' => true,
                'published_at' => now()->subDay()->toDateTimeString(),
            ],
            [
                'title' => 'Supervisor assessment workspace',
                'body' => 'Supervisors should monitor assigned students and submit assessments through the supervisor portal when field reports are due.',
                'audience' => 'supervisors',
                'tone' => 'info',
                'is_pinned' => false,
                'published_at' => now()->subHours(8)->toDateTimeString(),
            ],
        ];
    }
}
