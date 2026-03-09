<?php

namespace App\Http\Controllers;

use App\Models\RegistrarFeatureAccess;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RegistrarAccessController extends Controller
{
    /**
     * Feature groups (modules) and their registrar-togglable features.
     *
     * Keys are stored in DB; labels/descriptions are for UI only.
     */
    protected function featureGroups(): array
    {
        return [
            'admission' => [
                'label' => 'Admission',
                'features' => [
                    'registrar_admission_manual_register' => [
                        'label' => 'Manual Register',
                        'description' => 'Access to manual registration.',
                    ],
                    'registrar_admission_form_builder' => [
                        'label' => 'Form Builder',
                        'description' => 'Access to enrollment form builder.',
                    ],
                    'registrar_admission_responses' => [
                        'label' => 'Responses',
                        'description' => 'Access to registration responses and response folders.',
                    ],
                ],
            ],
            'student_records' => [
                'label' => 'Student Records',
                'features' => [
                    'registrar_student_records_students_explorer' => [
                        'label' => 'Students Explorer',
                        'description' => 'Access to Students Explorer (tree + table + edit student record).',
                    ],
                    'registrar_student_records_irregularities' => [
                        'label' => 'Irregularities',
                        'description' => 'Access to irregular students and create-schedule workflow.',
                    ],
                    'registrar_student_records_blocks' => [
                        'label' => 'Blocks',
                        'description' => 'Access to Block Management table.',
                    ],
                    'registrar_student_records_block_requests' => [
                        'label' => 'Block Requests',
                        'description' => 'Access to block change requests.',
                    ],
                    'registrar_student_records_cor_archive' => [
                        'label' => 'COR Archive',
                        'description' => 'Access to COR archive.',
                    ],
                    'registrar_student_records_irregular_cor_archive' => [
                        'label' => 'Irregular COR Archive',
                        'description' => 'Access to irregular COR archive.',
                    ],
                ],
            ],
            'schedule' => [
                'label' => 'Schedule',
                'features' => [
                    'registrar_schedule_program_schedule' => [
                        'label' => 'Program Schedule',
                        'description' => 'Access to registrar Program Schedule folders and table.',
                    ],
                    'registrar_schedule_create_schedule' => [
                        'label' => 'Create Schedule (Irregular)',
                        'description' => 'Access to Create Schedule templates and deploy to students.',
                    ],
                ],
            ],
            'workflow' => [
                'label' => 'Workflow & QA',
                'features' => [
                    'registrar_workflow_qa' => [
                        'label' => 'Workflow QA',
                        'description' => 'Access to workflow QA checklist for registrar.',
                    ],
                ],
            ],
            'reports' => [
                'label' => 'Reports',
                'features' => [
                    'registrar_reports_analytics' => [
                        'label' => 'Analytics',
                        'description' => 'Access to analytics dashboard.',
                    ],
                    'registrar_reports_reports' => [
                        'label' => 'Reports',
                        'description' => 'Access to registrar reports.',
                    ],
                ],
            ],
            'settings' => [
                'label' => 'Settings',
                'features' => [
                    'registrar_settings_school_years' => [
                        'label' => 'School Years',
                        'description' => 'Access to school year settings.',
                    ],
                    'registrar_settings_semesters' => [
                        'label' => 'Semesters',
                        'description' => 'Access to academic semester settings.',
                    ],
                    'registrar_settings_year_levels' => [
                        'label' => 'Year Levels',
                        'description' => 'Access to academic year-level settings.',
                    ],
                    'registrar_settings_blocks' => [
                        'label' => 'Blocks',
                        'description' => 'Access to block templates.',
                    ],
                    'registrar_settings_subjects' => [
                        'label' => 'Subjects',
                        'description' => 'Access to subject settings.',
                    ],
                    'registrar_settings_fees' => [
                        'label' => 'Fees',
                        'description' => 'Access to fee settings.',
                    ],
                    'registrar_settings_professors' => [
                        'label' => 'Professors',
                        'description' => 'Access to professor settings.',
                    ],
                    'registrar_settings_rooms' => [
                        'label' => 'Rooms',
                        'description' => 'Access to room settings.',
                    ],
                    'registrar_settings_cor_scopes' => [
                        'label' => 'COR Scope Templates',
                        'description' => 'Access to COR scope templates.',
                    ],
                    'registrar_settings_staff_access' => [
                        'label' => 'Staff access',
                        'description' => 'Access to configure staff feature access.',
                    ],
                    'registrar_settings_unifast_access' => [
                        'label' => 'Unifast access',
                        'description' => 'Access to configure Unifast feature access.',
                    ],
                ],
            ],
        ];
    }

    protected function featureDefinitions(): array
    {
        $groups = $this->featureGroups();
        $flat = [];

        foreach ($groups as $groupKey => $group) {
            $groupLabel = $group['label'] ?? ucfirst(str_replace('_', ' ', $groupKey));
            foreach ($group['features'] as $featureKey => $meta) {
                $flat[$featureKey] = array_merge($meta, [
                    'group' => $groupKey,
                    'group_label' => $groupLabel,
                ]);
            }
        }

        return $flat;
    }

    /**
     * Show the Registrar Access settings page (Admin only).
     */
    public function index(Request $request): View
    {
        $features = $this->featureDefinitions();
        $featureKeys = array_keys($features);
        $states = RegistrarFeatureAccess::statesFor($featureKeys);

        return view('dashboards.settings-registrar-access', [
            'featureGroups' => $this->featureGroups(),
            'features' => $features,
            'states' => $states,
        ]);
    }

    /**
     * Toggle a specific feature on/off for registrar role.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'feature' => ['required', 'string', 'max:255'],
            'enabled' => ['required', 'boolean'],
        ]);

        RegistrarFeatureAccess::query()->updateOrCreate(
            ['feature' => $data['feature']],
            ['enabled' => $data['enabled']]
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Registrar access settings updated.');
    }
}

