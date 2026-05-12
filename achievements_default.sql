-- Insert default achievements into saved_records
INSERT INTO saved_records (record_type, title, amount, description, record_date, icon, color) VALUES
('achievement', 'First Member Registered', NULL, 'First member joined the Njangi group', '2024-01-01', 'fas fa-user-plus', '#28a745'),
('achievement', '10 Members Milestone', NULL, 'Reached 10 active members in the group', '2024-01-15', 'fas fa-users', '#17a2b8'),
('achievement', '25 Members Milestone', NULL, 'Reached 25 active members', '2024-02-01', 'fas fa-users', '#17a2b8'),
('achievement', '50 Members Milestone', NULL, 'Celebrating 50 active members!', '2024-03-01', 'fas fa-trophy', '#ffc107'),
('achievement', 'First Hand Opened', NULL, 'First Njangi hand was opened', '2024-01-05', 'fas fa-hand-holding-heart', '#28a745'),
('achievement', '10 Hands Active', NULL, '10 hands are now active in the system', '2024-02-10', 'fas fa-hands-helping', '#17a2b8'),
('achievement', 'First Contribution', 5000, 'First contribution was made', '2024-01-10', 'fas fa-coins', '#28a745'),
('achievement', '100,000 FCFA Savings', 100000, 'Total savings reached 100,000 FCFA', '2024-01-20', 'fas fa-piggy-bank', '#fd7e14'),
('achievement', '500,000 FCFA Savings', 500000, 'Total savings reached 500,000 FCFA', '2024-02-15', 'fas fa-star', '#ffc107'),
('achievement', '1,000,000 FCFA Savings', 1000000, 'MAJOR MILESTONE: 1 Million FCFA in savings!', '2024-03-20', 'fas fa-crown', '#ffc107'),
('achievement', 'Perfect Attendance Month', NULL, 'All members contributed on time for January', '2024-01-31', 'fas fa-calendar-check', '#28a745'),
('achievement', 'First Emergency Withdrawal', NULL, 'First emergency fund withdrawal processed', '2024-02-05', 'fas fa-ambulance', '#dc3545'),
('achievement', 'Group Anniversary', NULL, 'Njangi group 1 year anniversary', '2024-06-01', 'fas fa-birthday-cake', '#ffc107'),
('achievement', 'Highest Monthly Contribution', 150000, 'Record highest monthly contribution', '2024-03-31', 'fas fa-chart-line', '#17a2b8'),
('achievement', 'New Member Type Created', NULL, 'VIP Member type was created', '2024-01-25', 'fas fa-user-tag', '#6610f2');

-- Create achievements table for more detailed tracking (optional)
CREATE TABLE IF NOT EXISTS achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    achievement_date DATE,
    category ENUM('members', 'savings', 'hands', 'contributions', 'special') DEFAULT 'special',
    icon VARCHAR(50) DEFAULT 'fas fa-trophy',
    color VARCHAR(20) DEFAULT '#ffc107',
    target_value DECIMAL(12,2) NULL,
    achieved_value DECIMAL(12,2) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert into achievements table
INSERT INTO achievements (title, description, category, icon, color, target_value) VALUES
('Member Milestones', 'Track member growth', 'members', 'fas fa-users', '#17a2b8', NULL),
('Savings Goals', 'Track savings targets', 'savings', 'fas fa-piggy-bank', '#28a745', 1000000),
('Hand Management', 'Track hand activities', 'hands', 'fas fa-hand-holding-heart', '#fd7e14', NULL),
('Contribution Records', 'Track contribution patterns', 'contributions', 'fas fa-coins', '#ffc107', NULL),
('Special Events', 'Group celebrations', 'special', 'fas fa-star', '#dc3545', NULL);