<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConsultationTemplate;
use App\Models\User;

class ConsultationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user as creator
        $admin = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$admin) {
            $admin = User::first();
        }

        $templates = [
            [
                'name' => 'General Medicine Consultation',
                'description' => 'Standard template for general medical consultations',
                'specialty' => 'General Medicine',
                'template_data' => [
                    'chief_complaint' => 'Please describe the main reason for your visit',
                    'history_of_present_illness' => 'When did symptoms start? How have they progressed?',
                    'past_medical_history' => 'Any previous medical conditions?',
                    'drug_history' => 'Current medications and dosages',
                    'allergy_history' => 'Known drug allergies or reactions',
                    'family_history' => 'Relevant family medical history',
                    'social_history' => 'Smoking, alcohol, occupation, lifestyle',
                    'general_examination' => 'General appearance, vital signs, overall condition',
                    'cardiovascular_examination' => 'Heart sounds, pulses, edema',
                    'respiratory_examination' => 'Breath sounds, chest examination',
                    'abdominal_examination' => 'Abdominal palpation, organomegaly',
                    'neurological_examination' => 'Mental status, reflexes, cranial nerves',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Cardiology Consultation',
                'description' => 'Specialized template for cardiac consultations',
                'specialty' => 'Cardiology',
                'template_data' => [
                    'chief_complaint' => 'Primary cardiac symptom (chest pain, shortness of breath, palpitations)',
                    'history_of_present_illness' => 'Onset, duration, triggers, relieving factors',
                    'past_medical_history' => 'Previous cardiac events, risk factors (DM, HTN, smoking)',
                    'drug_history' => 'Cardiac medications, anticoagulants',
                    'family_history' => 'Family history of heart disease, sudden death',
                    'social_history' => 'Smoking history, exercise habits, diet',
                    'general_examination' => 'Vital signs, JVP, peripheral pulses',
                    'cardiovascular_examination' => 'Heart sounds, murmurs, gallops, apex beat',
                    'respiratory_examination' => 'Chest examination, signs of heart failure',
                    'abdominal_examination' => 'Hepatomegaly, ascites',
                    'neurological_examination' => 'Mental status (if cardiac event suspected)',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Pediatric Consultation',
                'description' => 'Template for pediatric consultations',
                'specialty' => 'Pediatrics',
                'template_data' => [
                    'chief_complaint' => 'Parent/caregiver description of the problem',
                    'history_of_present_illness' => 'Onset, progression, associated symptoms',
                    'past_medical_history' => 'Birth history, developmental milestones, vaccinations',
                    'drug_history' => 'Current medications, allergies',
                    'family_history' => 'Genetic conditions, family medical history',
                    'social_history' => 'School performance, activities, family situation',
                    'general_examination' => 'Growth parameters, vital signs, general appearance',
                    'cardiovascular_examination' => 'Heart sounds, murmurs',
                    'respiratory_examination' => 'Breath sounds, respiratory effort',
                    'abdominal_examination' => 'Abdominal palpation, organomegaly',
                    'neurological_examination' => 'Developmental assessment, reflexes',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Emergency Medicine Consultation',
                'description' => 'Template for emergency consultations',
                'specialty' => 'Emergency Medicine',
                'template_data' => [
                    'chief_complaint' => 'Primary emergency complaint',
                    'history_of_present_illness' => 'Rapid history of presenting complaint',
                    'past_medical_history' => 'Relevant medical history for emergency',
                    'drug_history' => 'Current medications, allergies',
                    'allergy_history' => 'Known allergies (critical for emergency)',
                    'family_history' => 'Relevant family history',
                    'general_examination' => 'Primary survey, vital signs, GCS',
                    'cardiovascular_examination' => 'Cardiovascular status',
                    'respiratory_examination' => 'Airway, breathing assessment',
                    'abdominal_examination' => 'Abdominal examination',
                    'neurological_examination' => 'Neurological assessment, GCS',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Orthopedic Consultation',
                'description' => 'Template for orthopedic consultations',
                'specialty' => 'Orthopedics',
                'template_data' => [
                    'chief_complaint' => 'Musculoskeletal complaint',
                    'history_of_present_illness' => 'Mechanism of injury, onset, progression',
                    'past_medical_history' => 'Previous injuries, surgeries, bone conditions',
                    'drug_history' => 'Pain medications, anti-inflammatory drugs',
                    'family_history' => 'Family history of bone/joint conditions',
                    'social_history' => 'Occupation, sports activities, physical demands',
                    'general_examination' => 'General appearance, gait, posture',
                    'cardiovascular_examination' => 'Peripheral pulses if vascular involvement',
                    'respiratory_examination' => 'If chest trauma involved',
                    'abdominal_examination' => 'If abdominal trauma involved',
                    'neurological_examination' => 'Neurovascular status, reflexes, sensation',
                ],
                'is_system' => true,
            ],
        ];

        foreach ($templates as $templateData) {
            $templateData['created_by'] = $admin->id;
            ConsultationTemplate::create($templateData);
        }
    }
}