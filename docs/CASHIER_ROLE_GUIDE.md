# Cashier Role Guide

## Overview
The **Cashier** role is a specialized role designed for hospital staff who handle payments and billing operations. This role has comprehensive permissions to intelligently process payments from all modules in the NextHospital system.

## Role Purpose
The Cashier role is responsible for:
- Processing payments for all hospital services
- Managing patient billing across all modules
- Generating receipts and invoices
- Handling insurance claims and payments
- Managing outstanding debts
- Providing financial reporting

## Permissions Granted

### Core Payment Processing
- `process_payments` - Main cashier permission for payment processing
- `view_invoices` - View all invoices
- `create_invoices` - Create new invoices
- `edit_invoices` - Modify existing invoices
- `view_payments` - View payment history
- `create_payments` - Record new payments
- `edit_payments` - Modify payment records

### Patient Management
- `view_patients` - Search and view patient details
- `search_patients` - Search patients by various criteria
- `create_patients` - Register new patients if needed
- `view_visits` - View patient visit history
- `create_visits` - Create visits for walk-in patients

### Service Module Access
- `view_consultations` - View consultation details and billing
- `view_lab_requests` - View lab test requests and billing
- `view_lab_results` - View lab results for context
- `view_prescriptions` - View prescription details and billing
- `view_drugs` - View drug information and pricing
- `view_radiology_requests` - View radiology requests and billing
- `view_radiology_results` - View radiology results for context
- `view_wards` - View ward information for billing context

### Appointment Management
- `view_appointments` - View appointment details for billing context

### Insurance Management
- `view_insurance` - View insurance information
- `view_insurance_providers` - View insurance provider details
- `view_insurance_policies` - View patient insurance policies
- `view_insurance_claims` - View insurance claims
- `create_insurance_claims` - Create new insurance claims
- `edit_insurance_claims` - Modify insurance claims
- `calculate_insurance_coverage` - Calculate insurance coverage
- `process_insurance_claims` - Process insurance claims

### Financial Management
- `view_service_pricing` - View service pricing information
- `view_revenue_analytics` - View revenue analytics and reports
- `view_debtors` - View debtor information
- `edit_debtors` - Manage debtor accounts

### System Access
- `view_dashboard` - Access main dashboard
- `view_queues` - View patient queues
- `call_patients` - Call patients from queues
- `serve_patients` - Serve patients in queues
- `view_emergency_visits` - Handle emergency billing
- `view_surgery_schedules` - Handle surgery billing
- `view_reports` - Access reporting system
- `generate_reports` - Generate financial reports
- `view_workflow_dashboard` - View workflow status
- `view_queue_statistics` - View queue statistics

### E-commerce & Store
- `view_store_items` - View store items for billing
- `view_store_orders` - View store orders
- `view_inventory` - View inventory for pricing

### Administrative
- `view_branches` - View branch information
- `view_users` - View user information (limited)
- `view_settings` - View system settings

## Sample Cashier User
A sample cashier user has been created for testing:
- **Email**: cashier@nexthospital.com
- **Password**: password
- **Role**: Cashier

## Key Features Accessible to Cashier Role

### 1. Centralized Payment Dashboard
- Access via `/cashier` route
- Patient search and selection
- View all pending charges from all modules
- Process consolidated payments
- Generate receipts

### 2. Individual Module Receipts
- Generate receipts for specific services
- Routes: `/cashier/{module}/{id}/receipt`
- Supported modules: consultation, lab, prescription, radiology

### 3. Payment History Tracking
- View payment history by module
- Track pending vs paid amounts
- Monitor outstanding debts

### 4. Insurance Processing
- Process insurance claims
- Calculate coverage amounts
- Handle co-payments

### 5. Financial Reporting
- Generate revenue reports
- View payment analytics
- Track department performance

## Security Considerations
- Cashier role has **NO** administrative permissions
- Cannot delete critical data
- Cannot modify system settings
- Cannot manage user accounts (except viewing)
- Focused on financial operations only

## Workflow Integration
The Cashier role integrates with all hospital modules:
1. **Consultations** → Billing for consultation fees
2. **Lab Tests** → Billing for laboratory services
3. **Prescriptions** → Billing for medications
4. **Radiology** → Billing for imaging services
5. **Wards** → Billing for ward services
6. **Emergency** → Billing for emergency services
7. **Surgery** → Billing for surgical procedures
8. **E-commerce** → Billing for store purchases

## Best Practices
1. **Always verify patient identity** before processing payments
2. **Check insurance eligibility** before billing
3. **Generate receipts** for all transactions
4. **Update payment status** across all modules
5. **Maintain accurate records** for audit purposes
6. **Follow hospital billing policies** and procedures

## Training Requirements
Cashiers should be trained on:
- Hospital billing procedures
- Insurance claim processing
- Payment method handling
- Receipt generation
- Patient data privacy
- System navigation and features

## Support
For technical support or questions about the Cashier role, contact the system administrator or IT support team.
