-- Updated Comprehensive Seed Data for Auth System
-- File: auth_seed_data_updated.sql
-- Run this after your auth.sql schema

BEGIN;

-- ============== ADD MISSING CONSTRAINTS FIRST ==============

-- Add unique constraint on user_id in user_details (should be one profile per user)
ALTER TABLE auth.user_details 
ADD CONSTRAINT user_details_user_id_unique UNIQUE (user_id);

-- ============== ROLES SEED DATA ==============
INSERT INTO auth.roles (name, display_name, description, is_active) VALUES 
('super_admin', 'Super Administrator', 'Full system access with all permissions', true),
('admin', 'Administrator', 'Administrative access to manage system', true),
('hr_manager', 'HR Manager', 'Human Resources management access', true),
('finance_manager', 'Finance Manager', 'Financial management and oversight access', true),
('department_head', 'Department Head', 'Department leadership access', true),
('manager', 'Manager', 'Team management access', true),
('supervisor', 'Supervisor', 'Team supervision access', true),
('senior_staff', 'Senior Staff', 'Senior level employee access', true),
('staff', 'Staff', 'Regular employee access', true),
('intern', 'Intern', 'Internship program access', true),
('contractor', 'Contractor', 'External contractor access', true),
('vendor', 'Vendor', 'Vendor/supplier access', true),
('auditor', 'Auditor', 'System auditing access', true),
('authority_user', 'Authority User', 'Road authority personnel access', true),
('client', 'Client', 'Client/applicant access', true),
('board_member', 'Board Member', 'Board of directors access', true),
('viewer', 'Viewer', 'Read-only access', true),
('guest', 'Guest', 'Limited guest access', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- ============== GROUPS SEED DATA ==============
-- Keep your existing groups structure
INSERT INTO auth.groups (name, display_name, description, is_active) VALUES 
('admin', 'Admin Group', 'Administrative staff including HR, Finance, IT, and Management', true),
('client', 'Client Group', 'Applicants for road corridor submissions', true),
('authority', 'Authority Group', 'Road Authorities and Government Officials', true),
('vendor', 'Vendor Group', 'External vendors and suppliers', true),
('board', 'Board Members', 'Board of directors and executives', true)
ON CONFLICT (name) DO UPDATE SET
    display_name = EXCLUDED.display_name,
    description = EXCLUDED.description,
    updated_at = NOW();

-- ============== USERS SEED DATA ==============

DO $$
DECLARE
    -- User IDs for reference
    super_admin_id uuid;
    admin_user_id uuid;
    hr_manager_id uuid;
    finance_manager_id uuid;
    it_manager_id uuid;
    engineer_lead_id uuid;
    staff_user1_id uuid;
    staff_user2_id uuid;
    intern_user_id uuid;
    contractor_id uuid;
    authority_user_id uuid;
    client_user_id uuid;
    board_member_id uuid;
    
    -- Role IDs
    super_admin_role_id bigint;
    admin_role_id bigint;
    hr_manager_role_id bigint;
    finance_manager_role_id bigint;
    manager_role_id bigint;
    senior_staff_role_id bigint;
    staff_role_id bigint;
    intern_role_id bigint;
    contractor_role_id bigint;
    authority_user_role_id bigint;
    client_role_id bigint;
    board_member_role_id bigint;
    
    -- Group IDs
    admin_group_id bigint;
    client_group_id bigint;
    authority_group_id bigint;
    vendor_group_id bigint;
    board_group_id bigint;

BEGIN
    -- Get role IDs
    SELECT id INTO super_admin_role_id FROM auth.roles WHERE name = 'super_admin';
    SELECT id INTO admin_role_id FROM auth.roles WHERE name = 'admin';
    SELECT id INTO hr_manager_role_id FROM auth.roles WHERE name = 'hr_manager';
    SELECT id INTO finance_manager_role_id FROM auth.roles WHERE name = 'finance_manager';
    SELECT id INTO manager_role_id FROM auth.roles WHERE name = 'manager';
    SELECT id INTO senior_staff_role_id FROM auth.roles WHERE name = 'senior_staff';
    SELECT id INTO staff_role_id FROM auth.roles WHERE name = 'staff';
    SELECT id INTO intern_role_id FROM auth.roles WHERE name = 'intern';
    SELECT id INTO contractor_role_id FROM auth.roles WHERE name = 'contractor';
    SELECT id INTO authority_user_role_id FROM auth.roles WHERE name = 'authority_user';
    SELECT id INTO client_role_id FROM auth.roles WHERE name = 'client';
    SELECT id INTO board_member_role_id FROM auth.roles WHERE name = 'board_member';
    
    -- Get group IDs
    SELECT id INTO admin_group_id FROM auth.groups WHERE name = 'admin';
    SELECT id INTO client_group_id FROM auth.groups WHERE name = 'client';
    SELECT id INTO authority_group_id FROM auth.groups WHERE name = 'authority';
    SELECT id INTO vendor_group_id FROM auth.groups WHERE name = 'vendor';
    SELECT id INTO board_group_id FROM auth.groups WHERE name = 'board';

    -- ============== SUPER ADMIN USER ==============
    
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'System Administrator',
        'superadmin@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye', -- password: password
        NOW(),
        true
    )
    ON CONFLICT (email) DO UPDATE SET
        name = EXCLUDED.name,
        updated_at = NOW()
    RETURNING id INTO super_admin_id;
    
    IF super_admin_id IS NULL THEN
        SELECT id INTO super_admin_id FROM auth.users WHERE email = 'superadmin@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, 
        city, state, country, bio, telegram_id
    ) VALUES (
        super_admin_id, 
        'System', 
        'Administrator',
        '+60123456789',
        'SA001',
        'Kuala Terengganu',
        'Terengganu',
        'Malaysia',
        'System super administrator with full access to all system functions.',
        123456789
    ) ON CONFLICT (user_id) DO UPDATE SET
        first_name = EXCLUDED.first_name,
        last_name = EXCLUDED.last_name,
        updated_at = NOW();

    -- ============== ADMIN GROUP USERS ==============
    
    -- General Admin
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Ahmad Zaki',
        'admin@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO UPDATE SET
        name = EXCLUDED.name,
        updated_at = NOW()
    RETURNING id INTO admin_user_id;
    
    IF admin_user_id IS NULL THEN
        SELECT id INTO admin_user_id FROM auth.users WHERE email = 'admin@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country, bio
    ) VALUES (
        admin_user_id, 
        'Ahmad', 
        'Zaki',
        '+60137654321',
        'ADM001',
        'Male',
        'Kuala Terengganu',
        'Terengganu',
        'Malaysia',
        'General Administrator responsible for system management and coordination.'
    ) ON CONFLICT (user_id) DO UPDATE SET
        first_name = EXCLUDED.first_name,
        last_name = EXCLUDED.last_name,
        updated_at = NOW();
    
    -- HR Manager
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Sarah Ahmad',
        'sarah.ahmad@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO hr_manager_id;
    
    IF hr_manager_id IS NULL THEN
        SELECT id INTO hr_manager_id FROM auth.users WHERE email = 'sarah.ahmad@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        unit_no, street_name, city, state, postcode, country, telegram_id
    ) VALUES (
        hr_manager_id, 
        'Sarah', 
        'Ahmad',
        '+60129876543',
        'HR001',
        'Female',
        '15A',
        'Jalan Mawar',
        'Kuala Terengganu',
        'Terengganu',
        '20100',
        'Malaysia',
        987654321
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- Finance Manager
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Siti Nurhaliza',
        'siti.nurhaliza@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO finance_manager_id;
    
    IF finance_manager_id IS NULL THEN
        SELECT id INTO finance_manager_id FROM auth.users WHERE email = 'siti.nurhaliza@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country, bio
    ) VALUES (
        finance_manager_id, 
        'Siti', 
        'Nurhaliza',
        '+60145678901',
        'FIN001',
        'Female',
        'Kuala Terengganu',
        'Terengganu',
        'Malaysia',
        'Finance Manager responsible for financial planning and budget management.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- IT Manager
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Muhammad Faiz',
        'faiz.ibrahim@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO it_manager_id;
    
    IF it_manager_id IS NULL THEN
        SELECT id INTO it_manager_id FROM auth.users WHERE email = 'faiz.ibrahim@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country, bio
    ) VALUES (
        it_manager_id, 
        'Muhammad', 
        'Faiz Ibrahim',
        '+60156789012',
        'IT001',
        'Male',
        'Kuala Terengganu',
        'Terengganu',
        'Malaysia',
        'IT Manager with expertise in system architecture and technology infrastructure.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- Senior Staff
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Nurul Aina',
        'nurul.aina@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO staff_user1_id;
    
    IF staff_user1_id IS NULL THEN
        SELECT id INTO staff_user1_id FROM auth.users WHERE email = 'nurul.aina@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country, bio
    ) VALUES (
        staff_user1_id, 
        'Nurul', 
        'Aina',
        '+60167890123',
        'ST001',
        'Female',
        'Kuala Terengganu',
        'Terengganu',
        'Malaysia',
        'Senior Staff member with administrative responsibilities.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- Regular Staff
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Razak Hassan',
        'razak.hassan@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO staff_user2_id;
    
    IF staff_user2_id IS NULL THEN
        SELECT id INTO staff_user2_id FROM auth.users WHERE email = 'razak.hassan@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country
    ) VALUES (
        staff_user2_id, 
        'Razak', 
        'Hassan',
        '+60178901234',
        'ST002',
        'Male',
        'Kuala Terengganu',
        'Terengganu',
        'Malaysia'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- Intern
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Aishah Binti Ali',
        'aishah.ali@intern.kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO intern_user_id;
    
    IF intern_user_id IS NULL THEN
        SELECT id INTO intern_user_id FROM auth.users WHERE email = 'aishah.ali@intern.kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country, bio
    ) VALUES (
        intern_user_id, 
        'Aishah', 
        'Ali',
        '+60189012345',
        'INT001',
        'Female',
        'Kuala Terengganu',
        'Terengganu',
        'Malaysia',
        'IT Intern - Computer Science student from UMT.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- ============== CLIENT GROUP USER ==============
    
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'ABC Construction Sdn Bhd',
        'client@abcconstruction.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO client_user_id;
    
    IF client_user_id IS NULL THEN
        SELECT id INTO client_user_id FROM auth.users WHERE email = 'client@abcconstruction.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id,
        city, state, country, bio
    ) VALUES (
        client_user_id, 
        'ABC Construction', 
        'Sdn Bhd',
        '+60312345678',
        'CLI001',
        'Kuala Lumpur',
        'Selangor',
        'Malaysia',
        'Construction company applying for road corridor permits and submissions.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- ============== AUTHORITY GROUP USER ==============
    
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Dato Rahman Abdullah',
        'rahman.abdullah@authority.gov.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO authority_user_id;
    
    IF authority_user_id IS NULL THEN
        SELECT id INTO authority_user_id FROM auth.users WHERE email = 'rahman.abdullah@authority.gov.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country, bio
    ) VALUES (
        authority_user_id, 
        'Dato Rahman', 
        'Abdullah',
        '+60398765432',
        'AUTH001',
        'Male',
        'Putrajaya',
        'Selangor',
        'Malaysia',
        'Senior Authority Representative for Road Development and Infrastructure.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- ============== VENDOR GROUP USER ==============
    
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Tech Solutions Sdn Bhd',
        'vendor@techsolutions.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO contractor_id;
    
    IF contractor_id IS NULL THEN
        SELECT id INTO contractor_id FROM auth.users WHERE email = 'vendor@techsolutions.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id,
        city, state, country, bio
    ) VALUES (
        contractor_id, 
        'Tech Solutions', 
        'Sdn Bhd',
        '+60190123456',
        'VEN001',
        'Kuala Lumpur',
        'Selangor',
        'Malaysia',
        'Technology vendor providing specialized IT services and consultation.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- ============== BOARD GROUP USER ==============
    
    INSERT INTO auth.users (id, name, email, password, email_verified_at, is_active)
    VALUES (
        uuid_generate_v4(),
        'Tan Sri Ahmad Ibrahim',
        'board.chairman@kutt.my',
        '$2y$10$K0rMxTkYxN/d3rDOAhnw2eV0PnDHCiyEhAV1u/W0L7/uoiTFDp3Ye',
        NOW(),
        true
    ) ON CONFLICT (email) DO NOTHING RETURNING id INTO board_member_id;
    
    IF board_member_id IS NULL THEN
        SELECT id INTO board_member_id FROM auth.users WHERE email = 'board.chairman@kutt.my';
    END IF;
    
    INSERT INTO auth.user_details (
        user_id, first_name, last_name, phone, employee_id, gender,
        city, state, country, bio
    ) VALUES (
        board_member_id, 
        'Tan Sri Ahmad', 
        'Ibrahim',
        '+60123334444',
        'BOD001',
        'Male',
        'Kuala Lumpur',
        'Selangor',
        'Malaysia',
        'Chairman of the Board of Directors, overseeing strategic direction and governance.'
    ) ON CONFLICT (user_id) DO NOTHING;
    
    -- ============== ASSIGN ROLES TO USERS ==============
    
    -- Super Admin
    INSERT INTO auth.role_user (user_id, role_id, created_at, updated_at)
    VALUES (super_admin_id, super_admin_role_id, NOW(), NOW())
    ON CONFLICT (user_id, role_id) DO NOTHING;
    
    -- Admin Group Users
    INSERT INTO auth.role_user (user_id, role_id, created_at, updated_at)
    VALUES 
        (admin_user_id, admin_role_id, NOW(), NOW()),
        (hr_manager_id, hr_manager_role_id, NOW(), NOW()),
        (finance_manager_id, finance_manager_role_id, NOW(), NOW()),
        (it_manager_id, manager_role_id, NOW(), NOW()),
        (staff_user1_id, senior_staff_role_id, NOW(), NOW()),
        (staff_user2_id, staff_role_id, NOW(), NOW()),
        (intern_user_id, intern_role_id, NOW(), NOW())
    ON CONFLICT (user_id, role_id) DO NOTHING;
    
    -- Other Groups
    INSERT INTO auth.role_user (user_id, role_id, created_at, updated_at)
    VALUES 
        (client_user_id, client_role_id, NOW(), NOW()),
        (authority_user_id, authority_user_role_id, NOW(), NOW()),
        (contractor_id, contractor_role_id, NOW(), NOW()),
        (board_member_id, board_member_role_id, NOW(), NOW())
    ON CONFLICT (user_id, role_id) DO NOTHING;
    
    -- ============== ASSIGN USERS TO GROUPS ==============
    
    -- Admin Group (includes all internal staff)
    INSERT INTO auth.group_user (user_id, group_id, created_at, updated_at)
    VALUES 
        (admin_user_id, admin_group_id, NOW(), NOW()),
        (hr_manager_id, admin_group_id, NOW(), NOW()),
        (finance_manager_id, admin_group_id, NOW(), NOW()),
        (it_manager_id, admin_group_id, NOW(), NOW()),
        (staff_user1_id, admin_group_id, NOW(), NOW()),
        (staff_user2_id, admin_group_id, NOW(), NOW()),
        (intern_user_id, admin_group_id, NOW(), NOW())
    ON CONFLICT (user_id, group_id) DO NOTHING;
    
    -- Client Group
    INSERT INTO auth.group_user (user_id, group_id, created_at, updated_at)
    VALUES (client_user_id, client_group_id, NOW(), NOW())
    ON CONFLICT (user_id, group_id) DO NOTHING;
    
    -- Authority Group
    INSERT INTO auth.group_user (user_id, group_id, created_at, updated_at)
    VALUES (authority_user_id, authority_group_id, NOW(), NOW())
    ON CONFLICT (user_id, group_id) DO NOTHING;
    
    -- Vendor Group
    INSERT INTO auth.group_user (user_id, group_id, created_at, updated_at)
    VALUES (contractor_id, vendor_group_id, NOW(), NOW())
    ON CONFLICT (user_id, group_id) DO NOTHING;
    
    -- Board Group
    INSERT INTO auth.group_user (user_id, group_id, created_at, updated_at)
    VALUES (board_member_id, board_group_id, NOW(), NOW())
    ON CONFLICT (user_id, group_id) DO NOTHING;
    
    RAISE NOTICE 'Updated seed data created successfully!';
    RAISE NOTICE 'Super Admin: superadmin@kutt.my (password: password)';
    RAISE NOTICE 'Admin Group Users:';
    RAISE NOTICE '  - admin@kutt.my (General Admin)';
    RAISE NOTICE '  - sarah.ahmad@kutt.my (HR Manager)';
    RAISE NOTICE '  - siti.nurhaliza@kutt.my (Finance Manager)';
    RAISE NOTICE '  - faiz.ibrahim@kutt.my (IT Manager)';
    RAISE NOTICE '  - nurul.aina@kutt.my (Senior Staff)';
    RAISE NOTICE '  - razak.hassan@kutt.my (Staff)';
    RAISE NOTICE '  - aishah.ali@intern.kutt.my (Intern)';
    RAISE NOTICE 'Client: client@abcconstruction.my';
    RAISE NOTICE 'Authority: rahman.abdullah@authority.gov.my';
    RAISE NOTICE 'Vendor: vendor@techsolutions.my';
    RAISE NOTICE 'Board: board.chairman@kutt.my';
    RAISE NOTICE 'All users use password: password (for testing)';

END $$;

-- ============== UPDATED HELPER VIEWS ==============

-- Updated user summary view
CREATE OR REPLACE VIEW auth.user_summary AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.is_active,
    u.last_login_at,
    ud.employee_id,
    ud.first_name,
    ud.last_name,
    ud.phone,
    string_agg(DISTINCT r.display_name, ', ' ORDER BY r.display_name) as roles,
    string_agg(DISTINCT g.display_name, ', ' ORDER BY g.display_name) as groups,
    u.created_at
FROM auth.users u
LEFT JOIN auth.user_details ud ON u.id = ud.user_id
LEFT JOIN auth.role_user ru ON u.id = ru.user_id
LEFT JOIN auth.roles r ON ru.role_id = r.id AND r.is_active = true
LEFT JOIN auth.group_user gu ON u.id = gu.user_id
LEFT JOIN auth.groups g ON gu.group_id = g.id AND g.is_active = true
GROUP BY u.id, u.name, u.email, u.is_active, u.last_login_at, 
         ud.employee_id, ud.first_name, ud.last_name, ud.phone, u.created_at
ORDER BY u.created_at;

-- View for group composition
CREATE OR REPLACE VIEW auth.group_composition AS
SELECT 
    g.name as group_name,
    g.display_name as group_display,
    COUNT(gu.user_id) as user_count,
    string_agg(u.name || ' (' || r.display_name || ')', ', ' ORDER BY r.display_name, u.name) as members_with_roles
FROM auth.groups g
LEFT JOIN auth.group_user gu ON g.id = gu.group_id
LEFT JOIN auth.users u ON gu.user_id = u.id AND u.is_active = true
LEFT JOIN auth.role_user ru ON u.id = ru.user_id
LEFT JOIN auth.roles r ON ru.role_id = r.id AND r.is_active = true
WHERE g.is_active = true
GROUP BY g.id, g.name, g.display_name
ORDER BY user_count DESC, g.name;

COMMIT;

-- Display summary
SELECT 'Updated Seed Data Summary' as info;
SELECT COUNT(*) as total_users FROM auth.users;
SELECT COUNT(*) as total_roles FROM auth.roles;
SELECT COUNT(*) as total_groups FROM auth.groups;
SELECT COUNT(*) as total_role_assignments FROM auth.role_user;
SELECT COUNT(*) as total_group_assignments FROM auth.group_user;

-- Show group composition
SELECT * FROM auth.group_composition;