<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EnrollmentForm;

class EnrollmentFormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EnrollmentForm::create([
            'title' => '1ST YEAR - 2nd Semester 2025-2026',
            'description' => 'Provide the complete information needed in each field. Fields marked with an asterisk (*) are required. This form is PART OF ENROLLMENT PROCEDURE. Filling out this form does not guarantee that you are enrolled.',
            'is_active' => false,
            'assigned_year' => '1st Year',
            'assigned_semester' => 'Second Semester',
            'questions' => [
                [
                    'id' => 1,
                    'type' => 'description',
                    'title' => 'Important Instructions',
                    'description' => '• Use the appropriate format when entering dates, numbers, or names. • Double-check your responses before submitting. • Save or screenshot the confirmation message for your records. • The form will automatically close at a specified time and date.'
                ],
                [
                    'id' => 2,
                    'type' => 'section',
                    'title' => 'Student General Information',
                    'description' => 'Please provide your basic information accurately'
                ],
                [
                    'id' => 3,
                    'type' => 'question',
                    'questionText' => 'STUDENT NUMBER (Ex. DComC-2025-0001)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 4,
                    'type' => 'question',
                    'questionText' => 'LAST NAME (Ex. GARCIA)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 5,
                    'type' => 'question',
                    'questionText' => 'FIRST NAME (Ex. ART JANUS)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 6,
                    'type' => 'question',
                    'questionText' => 'MIDDLE NAME (Ex. RAÑOLA)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => false
                ],
                [
                    'id' => 7,
                    'type' => 'question',
                    'questionText' => 'GENDER',
                    'questionType' => 'radio',
                    'options' => ['Male', 'Female'],
                    'required' => true
                ],
                [
                    'id' => 8,
                    'type' => 'question',
                    'questionText' => 'CIVIL STATUS',
                    'questionType' => 'radio',
                    'options' => ['Single', 'Married', 'Separated', 'Widowed'],
                    'required' => true
                ],
                [
                    'id' => 9,
                    'type' => 'question',
                    'questionText' => 'CITIZENSHIP (Ex. Filipino)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 10,
                    'type' => 'question',
                    'questionText' => 'DATE OF BIRTH (Month, day, year)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 11,
                    'type' => 'question',
                    'questionText' => 'PLACE OF BIRTH (Ex. Saint Augustine Village, Tanza, Cavite)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 12,
                    'type' => 'question',
                    'questionText' => 'CONTACT NUMBER (Ex. 09458046704)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 13,
                    'type' => 'section',
                    'title' => 'Address Information',
                    'description' => 'Please provide your complete address'
                ],
                [
                    'id' => 14,
                    'type' => 'question',
                    'questionText' => 'ADDRESS - PUROK/ZONE (Ex. Purok 4/Zone 4)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 15,
                    'type' => 'question',
                    'questionText' => 'ADDRESS - BARANGAY (Ex. Anislag)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 16,
                    'type' => 'question',
                    'questionText' => 'ADDRESS - MUNICIPALITY/CITY (Ex. Daraga/Legazpi City)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 17,
                    'type' => 'question',
                    'questionText' => 'ADDRESS - PROVINCE (Ex. Albay)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 18,
                    'type' => 'question',
                    'questionText' => 'ADDRESS - ZIPCODE (Ex. 4501)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 19,
                    'type' => 'section',
                    'title' => 'Course & Major/Block',
                    'description' => 'Select your course and block'
                ],
                [
                    'id' => 20,
                    'type' => 'question',
                    'questionText' => 'COURSE',
                    'questionType' => 'dropdown',
                    'options' => [
                        'Bachelor of Elementary Education',
                        'Bachelor of Secondary Education',
                        'Bachelor of Culture and Arts Education',
                        'Bachelor of Physical Education',
                        'Bachelor of Technical-Vocational Teacher Education',
                        'Bachelor of Science in Entrepreneurship'
                    ],
                    'required' => true
                ],
                [
                    'id' => 21,
                    'type' => 'question',
                    'questionText' => 'MAJOR/BLOCK',
                    'questionType' => 'dropdown',
                    'options' => [],
                    'required' => true,
                    'majorsByCourse' => [
                        'Bachelor of Elementary Education' => ['BEED 1-1', 'BEED 1-2', 'BEED 1-3', 'BEED 1-4', 'BEED 1-5', 'BEED 1-6', 'BEED 1-9', 'BEED 1-11 (NIGHT)', 'BEED 1-12 (NIGHT)'],
                        'Bachelor of Secondary Education' => ['ENGLISH 1-B', 'ENGLISH 1-C', 'FILIPINO 1-A', 'FILIPINO 1-B', 'FILIPINO 1-C', 'MATH 1-A', 'MATH 1-B', 'SCIENCE 1-B', 'SOCIAL STUDIES 1-A', 'VALUES EDUCATION 1-A', 'VALUES EDUCATION 1-B', 'VALUES EDUCATION 1-C'],
                        'Bachelor of Culture and Arts Education' => ['BCAED 1-A'],
                        'Bachelor of Physical Education' => ['BPED 1-A', 'BPED 1-B', 'BPED 1-C'],
                        'Bachelor of Technical-Vocational Teacher Education' => ['FSM 1-A', 'FSM 1-B', 'GFD 1-A'],
                        'Bachelor of Science in Entrepreneurship' => ['ENTREP 1-B']
                    ]
                ],
                [
                    'id' => 22,
                    'type' => 'section',
                    'title' => 'Family Information',
                    'description' => 'Please provide your family details'
                ],
                [
                    'id' => 23,
                    'type' => 'question',
                    'questionText' => 'FATHER\'S FULL NAME (Ex. Lawrence Russel Garcia)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 24,
                    'type' => 'question',
                    'questionText' => 'FATHER\'S OCCUPATION (Ex. Electrician)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 25,
                    'type' => 'question',
                    'questionText' => 'MOTHER\'S MAIDEN NAME (Ex. Klarisse Lona Rañola)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 26,
                    'type' => 'question',
                    'questionText' => 'MOTHER\'S OCCUPATION (Ex. Beautician)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 27,
                    'type' => 'question',
                    'questionText' => 'HOUSEHOLD\'S MONTHLY INCOME (Ex. 10,000)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 28,
                    'type' => 'question',
                    'questionText' => 'NUMBER OF FAMILY MEMBERS (Ex. 5)',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 29,
                    'type' => 'question',
                    'questionText' => 'DSWD HOUSEHOLD NO',
                    'questionType' => 'short',
                    'options' => [],
                    'required' => true
                ],
                [
                    'id' => 30,
                    'type' => 'section',
                    'title' => 'Data Privacy Reminder & Consent',
                    'description' => 'Please read carefully before submitting'
                ],
                [
                    'id' => 31,
                    'type' => 'description',
                    'title' => 'DATA PRIVACY REMINDER',
                    'description' => 'Please be assured that all collected information will be treated with the utmost confidentiality and will be used solely for academic and enrollment purposes only. This consent form is in compliance with Republic Act. No. 10173, or the Data Privacy Act of 2012 (DPA). By filling out the form, you are consenting to the processing of your data for the above-stated purpose.'
                ],
                [
                    'id' => 32,
                    'type' => 'question',
                    'questionText' => 'I have read and understood the Data Privacy Notice and consent to the processing of my personal data',
                    'questionType' => 'radio',
                    'options' => ['Yes, I consent', 'No, I do not consent'],
                    'required' => true
                ]
            ]
        ]);
    }
}
