# Schema Regeneration Report

Generated: 2026-06-15 14:19:56
Source: nexthospital @ 127.0.0.1 (READ-ONLY introspection)

## Summary
- Migration files: 186
- Seeder files (tables with data): 71
- Skipped tables: migrations
- Deferred FK constraints: 0

## Migrations (run order)

1. `2024_01_01_000001_create_activity_logs_table.php` — `activity_logs`
2. `2024_01_01_000002_create_api_settings_table.php` — `api_settings`
3. `2024_01_01_000003_create_api_tokens_table.php` — `api_tokens`
4. `2024_01_01_000004_create_app_versions_table.php` — `app_versions`
5. `2024_01_01_000005_create_appointment_fees_table.php` — `appointment_fees`
6. `2024_01_01_000006_create_appointment_slots_table.php` — `appointment_slots`
7. `2024_01_01_000007_create_bed_assignments_table.php` — `bed_assignments`
8. `2024_01_01_000008_create_beds_table.php` — `beds`
9. `2024_01_01_000009_create_branches_table.php` — `branches`
10. `2024_01_01_000010_create_branding_settings_table.php` — `branding_settings`
11. `2024_01_01_000011_create_cache_table.php` — `cache`
12. `2024_01_01_000012_create_cache_locks_table.php` — `cache_locks`
13. `2024_01_01_000013_create_complaints_table.php` — `complaints`
14. `2024_01_01_000014_create_consultation_interventions_table.php` — `consultation_interventions`
15. `2024_01_01_000015_create_consultation_templates_table.php` — `consultation_templates`
16. `2024_01_01_000016_create_contrast_agents_table.php` — `contrast_agents`
17. `2024_01_01_000017_create_crash_carts_table.php` — `crash_carts`
18. `2024_01_01_000018_create_debtors_table.php` — `debtors`
19. `2024_01_01_000019_create_devices_table.php` — `devices`
20. `2024_01_01_000020_create_diagnoses_table.php` — `diagnoses`
21. `2024_01_01_000021_create_discount_schemes_table.php` — `discount_schemes`
22. `2024_01_01_000022_create_doctor_schedules_table.php` — `doctor_schedules`
23. `2024_01_01_000023_create_document_settings_table.php` — `document_settings`
24. `2024_01_01_000024_create_drug_interactions_table.php` — `drug_interactions`
25. `2024_01_01_000025_create_drug_orders_table.php` — `drug_orders`
26. `2024_01_01_000026_create_drugs_table.php` — `drugs`
27. `2024_01_01_000027_create_email_settings_table.php` — `email_settings`
28. `2024_01_01_000028_create_emergency_interventions_table.php` — `emergency_interventions`
29. `2024_01_01_000029_create_expense_categories_table.php` — `expense_categories`
30. `2024_01_01_000030_create_eye_services_table.php` — `eye_services`
31. `2024_01_01_000031_create_eye_test_comments_table.php` — `eye_test_comments`
32. `2024_01_01_000032_create_eye_test_images_table.php` — `eye_test_images`
33. `2024_01_01_000033_create_eye_test_parameters_table.php` — `eye_test_parameters`
34. `2024_01_01_000034_create_eye_test_results_table.php` — `eye_test_results`
35. `2024_01_01_000035_create_eye_test_templates_table.php` — `eye_test_templates`
36. `2024_01_01_000036_create_facility_users_table.php` — `facility_users`
37. `2024_01_01_000037_create_failed_jobs_table.php` — `failed_jobs`
38. `2024_01_01_000038_create_file_uploads_table.php` — `file_uploads`
39. `2024_01_01_000039_create_follow_ups_table.php` — `follow_ups`
40. `2024_01_01_000040_create_id_prefix_settings_table.php` — `id_prefix_settings`
41. `2024_01_01_000041_create_imaging_modalities_table.php` — `imaging_modalities`
42. `2024_01_01_000042_create_insurance_claims_table.php` — `insurance_claims`
43. `2024_01_01_000043_create_insurance_policies_table.php` — `insurance_policies`
44. `2024_01_01_000044_create_insurance_providers_table.php` — `insurance_providers`
45. `2024_01_01_000045_create_insurance_service_categories_table.php` — `insurance_service_categories`
46. `2024_01_01_000046_create_invoices_table.php` — `invoices`
47. `2024_01_01_000047_create_jitsi_settings_table.php` — `jitsi_settings`
48. `2024_01_01_000048_create_job_batches_table.php` — `job_batches`
49. `2024_01_01_000049_create_jobs_table.php` — `jobs`
50. `2024_01_01_000050_create_lab_consumables_table.php` — `lab_consumables`
51. `2024_01_01_000051_create_lab_critical_values_table.php` — `lab_critical_values`
52. `2024_01_01_000052_create_lab_delta_check_rules_table.php` — `lab_delta_check_rules`
53. `2024_01_01_000053_create_lab_equipment_table.php` — `lab_equipment`
54. `2024_01_01_000054_create_lab_equipment_calibration_table.php` — `lab_equipment_calibration`
55. `2024_01_01_000055_create_lab_equipment_maintenance_table.php` — `lab_equipment_maintenance`
56. `2024_01_01_000056_create_lab_inventory_transactions_table.php` — `lab_inventory_transactions`
57. `2024_01_01_000057_create_lab_quality_control_table.php` — `lab_quality_control`
58. `2024_01_01_000058_create_lab_reagents_table.php` — `lab_reagents`
59. `2024_01_01_000059_create_lab_reference_ranges_table.php` — `lab_reference_ranges`
60. `2024_01_01_000060_create_lab_reports_table.php` — `lab_reports`
61. `2024_01_01_000061_create_lab_request_templates_table.php` — `lab_request_templates`
62. `2024_01_01_000062_create_lab_result_comments_table.php` — `lab_result_comments`
63. `2024_01_01_000063_create_lab_results_table.php` — `lab_results`
64. `2024_01_01_000064_create_lab_test_categories_table.php` — `lab_test_categories`
65. `2024_01_01_000065_create_lab_test_templates_table.php` — `lab_test_templates`
66. `2024_01_01_000066_create_lab_tests_table.php` — `lab_tests`
67. `2024_01_01_000067_create_login_audit_table.php` — `login_audit`
68. `2024_01_01_000068_create_mobile_app_settings_table.php` — `mobile_app_settings`
69. `2024_01_01_000069_create_model_has_permissions_table.php` — `model_has_permissions`
70. `2024_01_01_000070_create_model_has_roles_table.php` — `model_has_roles`
71. `2024_01_01_000071_create_notes_table.php` — `notes`
72. `2024_01_01_000072_create_notifications_table.php` — `notifications`
73. `2024_01_01_000073_create_order_items_table.php` — `order_items`
74. `2024_01_01_000074_create_password_reset_tokens_table.php` — `password_reset_tokens`
75. `2024_01_01_000075_create_patient_allergies_table.php` — `patient_allergies`
76. `2024_01_01_000076_create_patient_medical_history_table.php` — `patient_medical_history`
77. `2024_01_01_000077_create_payment_settings_table.php` — `payment_settings`
78. `2024_01_01_000078_create_permissions_table.php` — `permissions`
79. `2024_01_01_000079_create_personal_access_tokens_table.php` — `personal_access_tokens`
80. `2024_01_01_000080_create_prescription_notifications_table.php` — `prescription_notifications`
81. `2024_01_01_000081_create_pricing_rules_table.php` — `pricing_rules`
82. `2024_01_01_000082_create_queues_table.php` — `queues`
83. `2024_01_01_000083_create_radiation_doses_table.php` — `radiation_doses`
84. `2024_01_01_000084_create_radiology_departments_table.php` — `radiology_departments`
85. `2024_01_01_000085_create_radiology_equipment_table.php` — `radiology_equipment`
86. `2024_01_01_000086_create_radiology_images_table.php` — `radiology_images`
87. `2024_01_01_000087_create_radiology_protocols_table.php` — `radiology_protocols`
88. `2024_01_01_000088_create_radiology_qc_checks_table.php` — `radiology_qc_checks`
89. `2024_01_01_000089_create_radiology_reports_table.php` — `radiology_reports`
90. `2024_01_01_000090_create_radiology_schedule_slots_table.php` — `radiology_schedule_slots`
91. `2024_01_01_000091_create_radiology_series_table.php` — `radiology_series`
92. `2024_01_01_000092_create_radiology_studies_table.php` — `radiology_studies`
93. `2024_01_01_000093_create_radiology_technicians_table.php` — `radiology_technicians`
94. `2024_01_01_000094_create_referrals_table.php` — `referrals`
95. `2024_01_01_000095_create_revenue_transactions_table.php` — `revenue_transactions`
96. `2024_01_01_000096_create_role_has_permissions_table.php` — `role_has_permissions`
97. `2024_01_01_000097_create_roles_table.php` — `roles`
98. `2024_01_01_000098_create_scans_table.php` — `scans`
99. `2024_01_01_000099_create_service_pricing_table.php` — `service_pricing`
100. `2024_01_01_000100_create_sessions_table.php` — `sessions`
101. `2024_01_01_000101_create_settings_table.php` — `settings`
102. `2024_01_01_000102_create_settings_audit_log_table.php` — `settings_audit_log`
103. `2024_01_01_000103_create_sms_settings_table.php` — `sms_settings`
104. `2024_01_01_000104_create_staff_attendances_table.php` — `staff_attendances`
105. `2024_01_01_000105_create_staff_profiles_table.php` — `staff_profiles`
106. `2024_01_01_000106_create_store_items_table.php` — `store_items`
107. `2024_01_01_000107_create_store_orders_table.php` — `store_orders`
108. `2024_01_01_000108_create_study_contrast_usage_table.php` — `study_contrast_usage`
109. `2024_01_01_000109_create_suppliers_table.php` — `suppliers`
110. `2024_01_01_000110_create_surgery_equipment_table.php` — `surgery_equipment`
111. `2024_01_01_000111_create_surgery_procedures_table.php` — `surgery_procedures`
112. `2024_01_01_000112_create_surgery_schedules_table.php` — `surgery_schedules`
113. `2024_01_01_000113_create_surgery_teams_table.php` — `surgery_teams`
114. `2024_01_01_000114_create_sync_logs_table.php` — `sync_logs`
115. `2024_01_01_000115_create_sync_queues_table.php` — `sync_queues`
116. `2024_01_01_000116_create_sync_settings_table.php` — `sync_settings`
117. `2024_01_01_000117_create_system_settings_table.php` — `system_settings`
118. `2024_01_01_000118_create_teleconsultation_chats_table.php` — `teleconsultation_chats`
119. `2024_01_01_000119_create_teleconsultation_files_table.php` — `teleconsultation_files`
120. `2024_01_01_000120_create_teleconsultations_table.php` — `teleconsultations`
121. `2024_01_01_000121_create_template_assignments_table.php` — `template_assignments`
122. `2024_01_01_000122_create_theatres_table.php` — `theatres`
123. `2024_01_01_000123_create_transfusions_table.php` — `transfusions`
124. `2024_01_01_000124_create_triage_assessments_table.php` — `triage_assessments`
125. `2024_01_01_000125_create_user_notification_preferences_table.php` — `user_notification_preferences`
126. `2024_01_01_000126_create_users_table.php` — `users`
127. `2024_01_01_000127_create_visits_table.php` — `visits`
128. `2024_01_01_000128_create_vitals_table.php` — `vitals`
129. `2024_01_01_000129_create_wards_table.php` — `wards`
130. `2024_01_01_000130_create_workflow_action_logs_table.php` — `workflow_action_logs`
131. `2024_01_01_000131_create_workflow_instances_table.php` — `workflow_instances`
132. `2024_01_01_000132_create_workflow_steps_table.php` — `workflow_steps`
133. `2024_01_01_000133_create_workflow_transitions_table.php` — `workflow_transitions`
134. `2024_01_01_000134_create_workflows_table.php` — `workflows`
135. `2024_01_01_000135_create_blood_inventory_table.php` — `blood_inventory`
136. `2024_01_01_000136_create_branch_settings_table.php` — `branch_settings`
137. `2024_01_01_000137_create_claim_items_table.php` — `claim_items`
138. `2024_01_01_000138_create_conversations_table.php` — `conversations`
139. `2024_01_01_000139_create_delivery_riders_table.php` — `delivery_riders`
140. `2024_01_01_000140_create_drug_stocks_table.php` — `drug_stocks`
141. `2024_01_01_000141_create_emergency_visits_table.php` — `emergency_visits`
142. `2024_01_01_000142_create_expenses_table.php` — `expenses`
143. `2024_01_01_000143_create_eye_service_billing_items_table.php` — `eye_service_billing_items`
144. `2024_01_01_000144_create_ghs_reports_table.php` — `ghs_reports`
145. `2024_01_01_000145_create_insurance_coverage_policies_table.php` — `insurance_coverage_policies`
146. `2024_01_01_000146_create_lab_equipment_maintenances_table.php` — `lab_equipment_maintenances`
147. `2024_01_01_000147_create_lab_inventory_movements_table.php` — `lab_inventory_movements`
148. `2024_01_01_000148_create_lab_inventory_stock_table.php` — `lab_inventory_stock`
149. `2024_01_01_000149_create_lab_purchase_orders_table.php` — `lab_purchase_orders`
150. `2024_01_01_000150_create_lab_requests_table.php` — `lab_requests`
151. `2024_01_01_000151_create_lab_test_parameters_table.php` — `lab_test_parameters`
152. `2024_01_01_000152_create_lab_test_types_table.php` — `lab_test_types`
153. `2024_01_01_000153_create_patients_table.php` — `patients`
154. `2024_01_01_000154_create_pharmacy_purchase_orders_table.php` — `pharmacy_purchase_orders`
155. `2024_01_01_000155_create_prescriptions_table.php` — `prescriptions`
156. `2024_01_01_000156_create_radiology_inventory_items_table.php` — `radiology_inventory_items`
157. `2024_01_01_000157_create_radiology_purchase_orders_table.php` — `radiology_purchase_orders`
158. `2024_01_01_000158_create_stock_counts_table.php` — `stock_counts`
159. `2024_01_01_000159_create_appointments_table.php` — `appointments`
160. `2024_01_01_000160_create_blood_donations_table.php` — `blood_donations`
161. `2024_01_01_000161_create_consultations_table.php` — `consultations`
162. `2024_01_01_000162_create_conversation_participants_table.php` — `conversation_participants`
163. `2024_01_01_000163_create_deliveries_table.php` — `deliveries`
164. `2024_01_01_000164_create_emergency_alerts_table.php` — `emergency_alerts`
165. `2024_01_01_000165_create_icu_logs_table.php` — `icu_logs`
166. `2024_01_01_000166_create_insurance_coverage_table.php` — `insurance_coverage`
167. `2024_01_01_000167_create_lab_purchase_order_items_table.php` — `lab_purchase_order_items`
168. `2024_01_01_000168_create_lab_quality_controls_table.php` — `lab_quality_controls`
169. `2024_01_01_000169_create_lab_test_results_table.php` — `lab_test_results`
170. `2024_01_01_000170_create_lab_test_type_items_table.php` — `lab_test_type_items`
171. `2024_01_01_000171_create_messages_table.php` — `messages`
172. `2024_01_01_000172_create_nhis_claims_table.php` — `nhis_claims`
173. `2024_01_01_000173_create_patient_cart_table.php` — `patient_cart`
174. `2024_01_01_000174_create_patient_dependents_table.php` — `patient_dependents`
175. `2024_01_01_000175_create_patient_payment_methods_table.php` — `patient_payment_methods`
176. `2024_01_01_000176_create_payments_table.php` — `payments`
177. `2024_01_01_000177_create_pharmacy_purchase_order_items_table.php` — `pharmacy_purchase_order_items`
178. `2024_01_01_000178_create_pre_authorizations_table.php` — `pre_authorizations`
179. `2024_01_01_000179_create_radiology_inventory_movements_table.php` — `radiology_inventory_movements`
180. `2024_01_01_000180_create_radiology_inventory_stock_table.php` — `radiology_inventory_stock`
181. `2024_01_01_000181_create_radiology_purchase_order_items_table.php` — `radiology_purchase_order_items`
182. `2024_01_01_000182_create_radiology_requests_table.php` — `radiology_requests`
183. `2024_01_01_000183_create_stock_count_items_table.php` — `stock_count_items`
184. `2024_01_01_000184_create_debtor_payment_histories_table.php` — `debtor_payment_histories`
185. `2024_01_01_000185_create_doctor_reviews_table.php` — `doctor_reviews`
186. `2024_01_01_000186_create_eye_test_requests_table.php` — `eye_test_requests`

## Seeders (run order)

1. `Generated/ActivityLogsSeeder.php` — `activity_logs` (9 rows)
2. `Generated/ApiSettingsSeeder.php` — `api_settings` (1 rows)
3. `Generated/AppVersionsSeeder.php` — `app_versions` (3 rows)
4. `Generated/AppointmentFeesSeeder.php` — `appointment_fees` (2 rows)
5. `Generated/BranchesSeeder.php` — `branches` (1 rows)
6. `Generated/BrandingSettingsSeeder.php` — `branding_settings` (1 rows)
7. `Generated/CacheSeeder.php` — `cache` (5 rows)
8. `Generated/ConsultationTemplatesSeeder.php` — `consultation_templates` (5 rows)
9. `Generated/ContrastAgentsSeeder.php` — `contrast_agents` (3 rows)
10. `Generated/DocumentSettingsSeeder.php` — `document_settings` (5 rows)
11. `Generated/DrugsSeeder.php` — `drugs` (601 rows)
12. `Generated/EmailSettingsSeeder.php` — `email_settings` (30 rows)
13. `Generated/ExpenseCategoriesSeeder.php` — `expense_categories` (12 rows)
14. `Generated/EyeServicesSeeder.php` — `eye_services` (5 rows)
15. `Generated/EyeTestParametersSeeder.php` — `eye_test_parameters` (10 rows)
16. `Generated/EyeTestTemplatesSeeder.php` — `eye_test_templates` (5 rows)
17. `Generated/FacilityUsersSeeder.php` — `facility_users` (26 rows)
18. `Generated/FailedJobsSeeder.php` — `failed_jobs` (1 rows)
19. `Generated/IdPrefixSettingsSeeder.php` — `id_prefix_settings` (63 rows)
20. `Generated/ImagingModalitiesSeeder.php` — `imaging_modalities` (7 rows)
21. `Generated/InsuranceProvidersSeeder.php` — `insurance_providers` (5 rows)
22. `Generated/JitsiSettingsSeeder.php` — `jitsi_settings` (1 rows)
23. `Generated/JobsSeeder.php` — `jobs` (7 rows)
24. `Generated/LabConsumablesSeeder.php` — `lab_consumables` (3 rows)
25. `Generated/LabCriticalValuesSeeder.php` — `lab_critical_values` (6 rows)
26. `Generated/LabEquipmentSeeder.php` — `lab_equipment` (3 rows)
27. `Generated/LabReagentsSeeder.php` — `lab_reagents` (3 rows)
28. `Generated/LabReferenceRangesSeeder.php` — `lab_reference_ranges` (39 rows)
29. `Generated/LabRequestTemplatesSeeder.php` — `lab_request_templates` (71 rows)
30. `Generated/LabTestCategoriesSeeder.php` — `lab_test_categories` (5 rows)
31. `Generated/LabTestTemplatesSeeder.php` — `lab_test_templates` (23 rows)
32. `Generated/LabTestsSeeder.php` — `lab_tests` (37 rows)
33. `Generated/MobileAppSettingsSeeder.php` — `mobile_app_settings` (2 rows)
34. `Generated/ModelHasPermissionsSeeder.php` — `model_has_permissions` (1510 rows)
35. `Generated/ModelHasRolesSeeder.php` — `model_has_roles` (53 rows)
36. `Generated/NotificationsSeeder.php` — `notifications` (5556 rows)
37. `Generated/OrderItemsSeeder.php` — `order_items` (5 rows)
38. `Generated/PasswordResetTokensSeeder.php` — `password_reset_tokens` (2 rows)
39. `Generated/PaymentSettingsSeeder.php` — `payment_settings` (32 rows)
40. `Generated/PermissionsSeeder.php` — `permissions` (395 rows)
41. `Generated/PersonalAccessTokensSeeder.php` — `personal_access_tokens` (164 rows)
42. `Generated/RadiologyDepartmentsSeeder.php` — `radiology_departments` (3 rows)
43. `Generated/RadiologyEquipmentSeeder.php` — `radiology_equipment` (5 rows)
44. `Generated/RadiologyProtocolsSeeder.php` — `radiology_protocols` (5 rows)
45. `Generated/RadiologyReportsSeeder.php` — `radiology_reports` (1 rows)
46. `Generated/RadiologyStudiesSeeder.php` — `radiology_studies` (2 rows)
47. `Generated/RoleHasPermissionsSeeder.php` — `role_has_permissions` (1573 rows)
48. `Generated/RolesSeeder.php` — `roles` (21 rows)
49. `Generated/ServicePricingSeeder.php` — `service_pricing` (12 rows)
50. `Generated/SessionsSeeder.php` — `sessions` (5 rows)
51. `Generated/SettingsSeeder.php` — `settings` (19 rows)
52. `Generated/SmsSettingsSeeder.php` — `sms_settings` (30 rows)
53. `Generated/StaffProfilesSeeder.php` — `staff_profiles` (27 rows)
54. `Generated/StoreItemsSeeder.php` — `store_items` (650 rows)
55. `Generated/StoreOrdersSeeder.php` — `store_orders` (5 rows)
56. `Generated/SuppliersSeeder.php` — `suppliers` (3 rows)
57. `Generated/SyncSettingsSeeder.php` — `sync_settings` (1 rows)
58. `Generated/SystemSettingsSeeder.php` — `system_settings` (1 rows)
59. `Generated/TemplateAssignmentsSeeder.php` — `template_assignments` (1 rows)
60. `Generated/UserNotificationPreferencesSeeder.php` — `user_notification_preferences` (23 rows)
61. `Generated/UsersSeeder.php` — `users` (39 rows)
62. `Generated/WorkflowActionLogsSeeder.php` — `workflow_action_logs` (81 rows)
63. `Generated/WorkflowInstancesSeeder.php` — `workflow_instances` (81 rows)
64. `Generated/WorkflowStepsSeeder.php` — `workflow_steps` (27 rows)
65. `Generated/WorkflowTransitionsSeeder.php` — `workflow_transitions` (24 rows)
66. `Generated/WorkflowsSeeder.php` — `workflows` (5 rows)
67. `Generated/BranchSettingsSeeder.php` — `branch_settings` (5 rows)
68. `Generated/EmergencyVisitsSeeder.php` — `emergency_visits` (1 rows)
69. `Generated/LabTestParametersSeeder.php` — `lab_test_parameters` (52 rows)
70. `Generated/LabTestTypesSeeder.php` — `lab_test_types` (37 rows)
71. `Generated/EmergencyAlertsSeeder.php` — `emergency_alerts` (2 rows)

## Tables with zero rows (no seeder generated)

- `api_tokens`
- `appointment_slots`
- `bed_assignments`
- `beds`
- `cache_locks`
- `complaints`
- `consultation_interventions`
- `crash_carts`
- `debtors`
- `devices`
- `diagnoses`
- `discount_schemes`
- `doctor_schedules`
- `drug_interactions`
- `drug_orders`
- `emergency_interventions`
- `eye_test_comments`
- `eye_test_images`
- `eye_test_results`
- `file_uploads`
- `follow_ups`
- `insurance_claims`
- `insurance_policies`
- `insurance_service_categories`
- `invoices`
- `job_batches`
- `lab_delta_check_rules`
- `lab_equipment_calibration`
- `lab_equipment_maintenance`
- `lab_inventory_transactions`
- `lab_quality_control`
- `lab_reports`
- `lab_result_comments`
- `lab_results`
- `login_audit`
- `notes`
- `patient_allergies`
- `patient_medical_history`
- `prescription_notifications`
- `pricing_rules`
- `queues`
- `radiation_doses`
- `radiology_images`
- `radiology_qc_checks`
- `radiology_schedule_slots`
- `radiology_series`
- `radiology_technicians`
- `referrals`
- `revenue_transactions`
- `scans`
- `settings_audit_log`
- `staff_attendances`
- `study_contrast_usage`
- `surgery_equipment`
- `surgery_procedures`
- `surgery_schedules`
- `surgery_teams`
- `sync_logs`
- `sync_queues`
- `teleconsultation_chats`
- `teleconsultation_files`
- `teleconsultations`
- `theatres`
- `transfusions`
- `triage_assessments`
- `visits`
- `vitals`
- `wards`
- `blood_inventory`
- `claim_items`
- `conversations`
- `delivery_riders`
- `drug_stocks`
- `expenses`
- `eye_service_billing_items`
- `ghs_reports`
- `insurance_coverage_policies`
- `lab_equipment_maintenances`
- `lab_inventory_movements`
- `lab_inventory_stock`
- `lab_purchase_orders`
- `lab_requests`
- `patients`
- `pharmacy_purchase_orders`
- `prescriptions`
- `radiology_inventory_items`
- `radiology_purchase_orders`
- `stock_counts`
- `appointments`
- `blood_donations`
- `consultations`
- `conversation_participants`
- `deliveries`
- `icu_logs`
- `insurance_coverage`
- `lab_purchase_order_items`
- `lab_quality_controls`
- `lab_test_results`
- `lab_test_type_items`
- `messages`
- `nhis_claims`
- `patient_cart`
- `patient_dependents`
- `patient_payment_methods`
- `payments`
- `pharmacy_purchase_order_items`
- `pre_authorizations`
- `radiology_inventory_movements`
- `radiology_inventory_stock`
- `radiology_purchase_order_items`
- `radiology_requests`
- `stock_count_items`
- `debtor_payment_histories`
- `doctor_reviews`
- `eye_test_requests`

## Test commands (SEPARATE database only)

```bash
# Create test database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS nexthospital_schema_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations + seeders against test DB
# IMPORTANT: disable permission auto-sync so Generated seeders preserve live IDs
cd backend
set DB_DATABASE=nexthospital_schema_test
set PERMISSIONS_AUTO_SYNC=false
php artisan migrate --seed
```

## Ambiguous items for manual review

- None identified.

## Notes
- Credentials used: root (from backend/.env), not nexthospital user
- Laravel `migrations` table excluded (managed by framework)
- Old migration files deleted; old seeders remain on disk but are not called by DatabaseSeeder