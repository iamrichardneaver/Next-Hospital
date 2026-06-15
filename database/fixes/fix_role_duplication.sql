-- ============================================
-- NEXTHOSPITAL - ROLE DUPLICATION FIX
-- ============================================
-- This script consolidates duplicate roles and their permissions
-- Created: October 8, 2025
-- ============================================

-- BACKUP CURRENT STATE (for safety)
-- You should backup the database before running this script

SET @timestamp = NOW();

-- ============================================
-- 1. FIX: super_admin vs Super Admin
-- ============================================
-- Keep: admin (id 2, 278 permissions)
-- Migrate: Super Admin (id 14, 273 permissions) -> admin
--          super_admin (id 1, 258 permissions) -> admin

-- Step 1a: Migrate users from 'Super Admin' to 'admin'
UPDATE model_has_roles 
SET role_id = 2 
WHERE role_id = 14;

-- Step 1b: Migrate users from 'super_admin' to 'admin'
UPDATE model_has_roles 
SET role_id = 2 
WHERE role_id = 1;

-- Step 1c: Copy unique permissions from 'Super Admin' to 'admin'
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT DISTINCT rp.permission_id, 2
FROM role_has_permissions rp
WHERE rp.role_id = 14
AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions rp2
    WHERE rp2.role_id = 2 AND rp2.permission_id = rp.permission_id
);

-- Step 1d: Copy unique permissions from 'super_admin' to 'admin'
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT DISTINCT rp.permission_id, 2
FROM role_has_permissions rp
WHERE rp.role_id = 1
AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions rp2
    WHERE rp2.role_id = 2 AND rp2.permission_id = rp.permission_id
);

-- Step 1e: Delete permissions for duplicate roles
DELETE FROM role_has_permissions WHERE role_id IN (1, 14);

-- Step 1f: Delete the duplicate roles
DELETE FROM roles WHERE id IN (1, 14);

-- ============================================
-- 2. FIX: emergency_staff vs Emergency Staff
-- ============================================
-- Keep: emergency_staff (id 9, 32 permissions)
-- Migrate: Emergency Staff (id 15, 12 permissions) -> emergency_staff

-- Step 2a: Migrate users from 'Emergency Staff' to 'emergency_staff'
UPDATE model_has_roles 
SET role_id = 9 
WHERE role_id = 15;

-- Step 2b: Copy unique permissions from 'Emergency Staff' to 'emergency_staff'
INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT DISTINCT rp.permission_id, 9
FROM role_has_permissions rp
WHERE rp.role_id = 15
AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions rp2
    WHERE rp2.role_id = 9 AND rp2.permission_id = rp.permission_id
);

-- Step 2c: Delete permissions for duplicate role
DELETE FROM role_has_permissions WHERE role_id = 15;

-- Step 2d: Delete the duplicate role
DELETE FROM roles WHERE id = 15;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
-- Run these after the fix to verify results

-- Check remaining roles
SELECT 
    id, 
    name, 
    guard_name, 
    (SELECT COUNT(*) FROM role_has_permissions WHERE role_id = roles.id) as permission_count,
    (SELECT COUNT(*) FROM model_has_roles WHERE role_id = roles.id) as user_count
FROM roles 
ORDER BY name;

-- Check if any orphaned permissions exist
SELECT COUNT(*) as orphaned_permissions
FROM role_has_permissions rp
WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.id = rp.role_id);

-- Check if any orphaned user roles exist
SELECT COUNT(*) as orphaned_user_roles
FROM model_has_roles mhr
WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.id = mhr.role_id);

-- ============================================
-- EXPECTED RESULTS AFTER FIX
-- ============================================
-- Roles should be:
-- - admin (consolidated from admin, Super Admin, super_admin)
-- - emergency_staff (consolidated from emergency_staff, Emergency Staff)
-- - All other roles unchanged
--
-- All permissions should be preserved
-- All user assignments should be preserved
-- No duplicate roles should exist
-- ============================================
