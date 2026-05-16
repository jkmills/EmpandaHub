-- EmpandaHub seed data for testing
-- Run AFTER schema.sql

SET NAMES utf8mb4;

-- Organization
INSERT INTO organizations (id, name, primary_color, timezone, fiscal_year_start) VALUES
(1, 'Greenfield Community Foundation', '#2563eb', 'America/New_York', 1);

-- Super admin user (password: Admin1234!)
INSERT INTO users (org_id, name, email, password, role) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin'),
(1, 'Staff Member', 'staff@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
(1, 'Read Only', 'readonly@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'readonly');

-- Membership tiers (password for all seeded users is: password)
INSERT INTO membership_tiers (org_id, name, description, billing_cycle, amount, grace_period_days, sort_order) VALUES
(1, 'Member', 'Standard annual membership', 'annual', 75.00, 30, 1),
(1, 'Associate Member', 'Associate membership for organizations', 'annual', 150.00, 30, 2),
(1, 'Lifetime Member', 'One-time lifetime membership', 'lifetime', 0.00, 0, 3);

-- 50 contacts
INSERT INTO contacts (org_id, first_name, last_name, email, phone, city, state) VALUES
(1,'Alice','Johnson','alice@example.com','555-1001','Springfield','IL'),
(1,'Bob','Williams','bob@example.com','555-1002','Springfield','IL'),
(1,'Carol','Davis','carol@example.com','555-1003','Shelbyville','IL'),
(1,'David','Miller','david@example.com','555-1004','Springfield','IL'),
(1,'Eve','Wilson','eve@example.com','555-1005','Capital City','IL'),
(1,'Frank','Moore','frank@example.com','555-1006','Springfield','IL'),
(1,'Grace','Taylor','grace@example.com','555-1007','Shelbyville','IL'),
(1,'Henry','Anderson','henry@example.com','555-1008','Springfield','IL'),
(1,'Iris','Thomas','iris@example.com','555-1009','Springfield','IL'),
(1,'Jack','Jackson','jack@example.com','555-1010','Capital City','IL'),
(1,'Kate','White','kate@example.com','555-1011','Springfield','IL'),
(1,'Liam','Harris','liam@example.com','555-1012','Shelbyville','IL'),
(1,'Mia','Martin','mia@example.com','555-1013','Springfield','IL'),
(1,'Noah','Garcia','noah@example.com','555-1014','Springfield','IL'),
(1,'Olivia','Martinez','olivia@example.com','555-1015','Capital City','IL'),
(1,'Peter','Robinson','peter@example.com','555-1016','Springfield','IL'),
(1,'Quinn','Clark','quinn@example.com','555-1017','Shelbyville','IL'),
(1,'Rachel','Rodriguez','rachel@example.com','555-1018','Springfield','IL'),
(1,'Sam','Lewis','sam@example.com','555-1019','Springfield','IL'),
(1,'Tara','Lee','tara@example.com','555-1020','Capital City','IL'),
(1,'Uma','Walker','uma@example.com','555-1021','Springfield','IL'),
(1,'Victor','Hall','victor@example.com','555-1022','Shelbyville','IL'),
(1,'Wendy','Allen','wendy@example.com','555-1023','Springfield','IL'),
(1,'Xander','Young','xander@example.com','555-1024','Springfield','IL'),
(1,'Yara','Hernandez','yara@example.com','555-1025','Capital City','IL'),
(1,'Zach','King','zach@example.com','555-1026','Springfield','IL'),
(1,'Amy','Wright','amy@example.com','555-1027','Shelbyville','IL'),
(1,'Brian','Lopez','brian@example.com','555-1028','Springfield','IL'),
(1,'Cindy','Hill','cindy@example.com','555-1029','Springfield','IL'),
(1,'Dennis','Scott','dennis@example.com','555-1030','Capital City','IL'),
(1,'Elena','Green','elena@example.com','555-1031','Springfield','IL'),
(1,'Fred','Adams','fred@example.com','555-1032','Shelbyville','IL'),
(1,'Gina','Baker','gina@example.com','555-1033','Springfield','IL'),
(1,'Harold','Gonzalez','harold@example.com','555-1034','Springfield','IL'),
(1,'Inga','Nelson','inga@example.com','555-1035','Capital City','IL'),
(1,'James','Carter','james@example.com','555-1036','Springfield','IL'),
(1,'Kira','Mitchell','kira@example.com','555-1037','Shelbyville','IL'),
(1,'Leon','Perez','leon@example.com','555-1038','Springfield','IL'),
(1,'Maya','Roberts','maya@example.com','555-1039','Springfield','IL'),
(1,'Neil','Turner','neil@example.com','555-1040','Capital City','IL'),
(1,'Olga','Phillips','olga@example.com','555-1041','Springfield','IL'),
(1,'Paul','Campbell','paul@example.com','555-1042','Shelbyville','IL'),
(1,'Rena','Parker','rena@example.com','555-1043','Springfield','IL'),
(1,'Steve','Evans','steve@example.com','555-1044','Springfield','IL'),
(1,'Tina','Edwards','tina@example.com','555-1045','Capital City','IL'),
(1,'Uri','Collins','uri@example.com','555-1046','Springfield','IL'),
(1,'Vera','Stewart','vera@example.com','555-1047','Shelbyville','IL'),
(1,'Walt','Sanchez','walt@example.com','555-1048','Springfield','IL'),
(1,'Xena','Morris','xena@example.com','555-1049','Springfield','IL'),
(1,'Yuki','Rogers','yuki@example.com','555-1050','Capital City','IL');

-- Memberships (contacts 1-20: Members, 21-35: Associates, 36-40: Lifetime)
INSERT INTO memberships (org_id, contact_id, tier_id, start_date, end_date, status) VALUES
(1,1,1,'2024-01-01','2025-01-01','active'),
(1,2,1,'2024-03-15','2025-03-15','active'),
(1,3,1,'2024-06-01','2025-06-01','active'),
(1,4,1,'2023-01-01','2024-01-01','expired'),
(1,5,1,'2024-11-01','2025-11-01','active'),
(1,6,2,'2024-02-01','2025-02-01','active'),
(1,7,2,'2024-04-01','2025-04-01','active'),
(1,8,2,'2023-06-01','2024-06-01','expired'),
(1,9,3,'2022-01-01',NULL,'lifetime'),
(1,10,3,'2021-05-01',NULL,'lifetime');

-- Campaigns
INSERT INTO campaigns (org_id, name, description, goal_amount, start_date, end_date, is_active) VALUES
(1,'Annual Fund 2024','Our primary annual fundraising campaign',50000,'2024-01-01','2024-12-31',1),
(1,'Capital Campaign','Building renovation fund',200000,'2024-06-01','2025-12-31',1),
(1,'Emergency Relief Fund','Rapid response community support',10000,'2024-09-01','2024-12-31',0);

-- Donations (20 records)
INSERT INTO donations (org_id, contact_id, campaign_id, amount, donated_on, method, fiscal_year) VALUES
(1,1,1,500.00,'2024-03-15','Check',2024),
(1,2,1,250.00,'2024-04-10','Card',2024),
(1,3,1,1000.00,'2024-05-22','Check',2024),
(1,4,2,5000.00,'2024-06-01','Wire',2024),
(1,5,1,100.00,'2024-07-04','Card',2024),
(1,6,1,750.00,'2024-08-15','Check',2024),
(1,7,2,2500.00,'2024-09-01','Card',2024),
(1,8,3,200.00,'2024-09-15','Cash',2024),
(1,9,1,50.00,'2024-10-01','Card',2024),
(1,10,1,300.00,'2024-10-20','Check',2024),
(1,11,1,150.00,'2024-11-01','Card',2024),
(1,12,2,10000.00,'2024-11-15','Wire',2024),
(1,13,1,75.00,'2024-12-01','Card',2024),
(1,14,1,500.00,'2024-12-10','Check',2024),
(1,15,3,100.00,'2024-12-15','Cash',2024),
(1,16,1,250.00,'2025-01-05','Card',2025),
(1,17,1,200.00,'2025-01-20','Check',2025),
(1,18,2,1000.00,'2025-02-01','Wire',2025),
(1,19,1,50.00,'2025-03-01','Card',2025),
(1,20,1,125.00,'2025-03-15','Check',2025);

-- Volunteers (10)
INSERT INTO volunteers (org_id, contact_id, skills, availability, is_active) VALUES
(1,21,'Event setup, Registration desk','Weekends',1),
(1,22,'Tutoring, Mentoring','Weekday evenings',1),
(1,23,'Social media, Photography','Flexible',1),
(1,24,'Food service, Kitchen help','Saturdays',1),
(1,25,'Administrative, Data entry','Weekday mornings',1),
(1,26,'Construction, Repair','Weekend mornings',1),
(1,27,'Childcare, Education','Weekday afternoons',1),
(1,28,'Transportation, Driving','Flexible',1),
(1,29,'Accounting, Finance','Weekday evenings',1),
(1,30,'Public speaking, Outreach','Weekends',1);

-- Events (5)
INSERT INTO events (org_id, title, description, event_date, start_time, end_time, location, capacity, price, is_published) VALUES
(1,'Annual Gala 2024','Our signature fundraising dinner','2024-11-15','18:00:00','22:00:00','Grand Ballroom, Springfield',200,150.00,1),
(1,'Volunteer Orientation','Welcome new volunteers','2025-01-10','10:00:00','12:00:00','Community Center',50,0.00,1),
(1,'Spring Luncheon','Member appreciation event','2025-04-15','12:00:00','14:00:00','Hilltop Restaurant',80,0.00,1),
(1,'Grant Writing Workshop','Learn to write winning grants','2025-03-20','09:00:00','16:00:00','Library Meeting Room',30,25.00,1),
(1,'Summer Picnic','Annual community picnic','2025-07-04','11:00:00','15:00:00','City Park',500,0.00,0);

-- Funders (3 for grants)
INSERT INTO funders (org_id, name, contact_name, email, website) VALUES
(1,'Smith Family Foundation','Jane Smith','grants@smithfamily.org','https://smithfamily.org'),
(1,'Community Health Alliance','Robert Chen','rfp@cha.org','https://cha.org'),
(1,'State Arts Council','Maria Rodriguez','grants@statearts.gov','https://statearts.gov');

-- Grants (3)
INSERT INTO `grants` (org_id, funder_id, title, status, amount_requested, deadline_date, submitted_date, period_start, period_end) VALUES
(1,1,'Community Leadership Initiative','awarded',25000.00,'2024-09-30','2024-09-15','2025-01-01','2025-12-31'),
(1,2,'Health Outreach Program','submitted',15000.00,'2025-04-30','2025-03-01','2025-07-01','2026-06-30'),
(1,3,'Arts Education Grant','drafting',10000.00,'2025-06-15',NULL,'2025-09-01','2026-08-31');

-- Transactions (auto-generated from donations)
INSERT INTO transactions (org_id, source_type, source_id, contact_id, amount, direction, category, transaction_date, fiscal_year) VALUES
(1,'donation',1,1,500.00,'credit','donation','2024-03-15',2024),
(1,'donation',2,2,250.00,'credit','donation','2024-04-10',2024),
(1,'donation',3,3,1000.00,'credit','donation','2024-05-22',2024),
(1,'donation',4,4,5000.00,'credit','donation','2024-06-01',2024),
(1,'donation',5,5,100.00,'credit','donation','2024-07-04',2024),
(1,'grant',1,NULL,25000.00,'credit','grant','2024-12-01',2024);
