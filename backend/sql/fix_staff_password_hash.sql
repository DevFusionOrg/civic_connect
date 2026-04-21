-- Fix password hash for existing staff accounts
-- Run this if you already imported the database with incorrect hashes

UPDATE users 
SET password_hash = '$2y$12$xxjXeGp06gJCnkI6HlS0LenWcxloHZUUcLrVDz2f.9zVZMuw0xwwG'
WHERE email IN (
  'pwd@gov.in',
  'electricity@gov.in',
  'sanitation@gov.in',
  'water@gov.in',
  'horticulture@gov.in',
  'police@gov.in',
  'municipal@gov.in',
  'helpline@gov.in'
);

-- Fix incorrect category mappings if they were inserted with uppercase values
DELETE FROM category_staff_mapping 
WHERE category IN ('Roads', 'Street Lights', 'Trash (Solid Waste)', 'Water & Drainage', 
                   'Parks & Recreation', 'Public Safety', 'Graffiti & Vandalism', 
                   'Noise', 'Other Issues');

-- Insert correct lowercase category mappings
INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'roads', id, 'Public Works Department (PWD)' FROM users WHERE email = 'pwd@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'street_lights', id, 'Electricity Department' FROM users WHERE email = 'electricity@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'trash', id, 'Sanitation Department' FROM users WHERE email = 'sanitation@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'water_drainage', id, 'Water Supply Board' FROM users WHERE email = 'water@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'parks_recreation', id, 'Horticulture Department' FROM users WHERE email = 'horticulture@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'public_safety', id, 'Police Department' FROM users WHERE email = 'police@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'graffiti_vandalism', id, 'Municipal Body' FROM users WHERE email = 'municipal@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'noise', id, 'Police Department' FROM users WHERE email = 'police@gov.in';

INSERT INTO category_staff_mapping (category, staff_id, department_name)
SELECT 'other', id, 'Municipal Helpline' FROM users WHERE email = 'helpline@gov.in';

-- Verify the updates
SELECT email, password_hash FROM users WHERE role = 'staff' ORDER BY email;
SELECT category, staff_id, department_name FROM category_staff_mapping ORDER BY category;
