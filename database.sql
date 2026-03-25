-- ============================================================
-- Museo de Labo — Database Setup
-- Run this once in your MySQL/MariaDB server
-- ============================================================

CREATE DATABASE IF NOT EXISTS museo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE museo_db;

-- Admins
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories / Departments
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  image_path VARCHAR(300),
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exhibits / Artifacts
CREATE TABLE IF NOT EXISTS exhibits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  description TEXT,
  category_id INT,
  image_path VARCHAR(300),
  donated_by VARCHAR(200),
  artifact_year VARCHAR(100),
  origin VARCHAR(200),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- News & Events
CREATE TABLE IF NOT EXISTS news_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  content TEXT NOT NULL,
  type ENUM('news','event') DEFAULT 'news',
  event_date DATE NULL,
  date_posted DATE DEFAULT (CURRENT_DATE),
  image_path VARCHAR(300),
  is_archived TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Guests / Visitors (Logbook)
CREATE TABLE IF NOT EXISTS guests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  guest_name VARCHAR(200) NOT NULL,
  visitor_type ENUM('Individual','Group') DEFAULT 'Individual',
  organization VARCHAR(200),
  gender ENUM('Male','Female','Other'),
  residence VARCHAR(200),
  nationality VARCHAR(100) DEFAULT 'Filipino',
  headcount INT DEFAULT 1,
  male_count INT DEFAULT 0,
  female_count INT DEFAULT 0,
  num_days INT DEFAULT 1,
  purpose VARCHAR(200),
  contact_no VARCHAR(20),
  visit_date DATE DEFAULT (CURRENT_DATE),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin (password: password123)
INSERT IGNORE INTO admins (username, password) VALUES
('admin', 'password123');

-- Categories
INSERT INTO categories (id, name, image_path, description) VALUES
(3, 'Local History Books',     'books.jpg',                                              'Books documenting the rich history of Labo'),
(4, 'Old Lamps',               '238f30d987b072e5475d3fc5cb007cfb.jpg',                  'Various old lamps used by forefathers'),
(5, 'OLD WOODEN CANE AND HAT', '637157823_926852483074221_5622699732933520824_n.jpg',   'Colonial-era symbols of authority'),
(6, 'Camera',                  'd1cee0a9bd9adff6604c500fcbe368e0.jpg',                  'Historical cameras from Labo');

-- Exhibits
INSERT INTO exhibits (id, title, description, category_id, image_path, donated_by, artifact_year, origin) VALUES
(5, 'KASAYSAY PAMANA NG LAHI VOL. 1', 'The first ever local history books of Labo from 1591-1998 with special feature of Centennial celebration of Philippine Independence in town on June 12, 1998.', 3, '1book.jpg', 'Briones Family', 'June 12, 1998', 'Labo'),
(6, 'KASAYSAY PAMANA NG LAHI VOL. 2', 'After 25 years the updated version of Kasaysayan Vol. 1 covering 1570-2023, many special events that happened in the town are already included.', 3, '2book.jpg', 'Malagueño', '1570-2023', 'Labo'),
(7, 'OLD LAMPS', 'Different sizes and design of old lamps used by our forefathers for various purposes donated by local populace.', 4, '639087160_1992852244773915_1201390354977356726_n.jpg', 'Elon', '1994', 'Labo'),
(8, 'OLD WOODEN CANE AND HAT', 'During Spanish times these were the common things carried by local leaders and encargado that symbolized authority and power in the community.', 5, '637157823_926852483074221_5622699732933520824_n.jpg', 'Garen', '1993', 'Labo'),
(9, 'COMPUR KODAK CAMERA', '(1910) owned by Mr. William Paguirillo (Ching Studio) is the oldest studio camera in Labo, Camarines Norte and still existing today.', 6, '638496593_3821753597961247_6027973135724181512_n.jpg', 'Paguirillo', '1890', 'Labo');

-- Add is_archived column if upgrading existing database
ALTER TABLE news_events ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) DEFAULT 0;

-- News & Events seed data (id, title, content, type, event_date, date_posted, image_path) VALUES
(1, "Unearthing Labo's Pre-Colonial Gold", "We are thrilled to announce the opening of our newest permanent exhibit highlighting the rich, pre-colonial gold mining history of Camarines Norte. Featuring over 50 newly restored artifacts excavated near the Labo River, this collection showcases the incredible craftsmanship of our ancestors long before the Spanish era. Come visit the museum this week to be among the first to see these stunning historical treasures!", 'news', NULL, '2026-03-10', NULL),
(2, 'National Heritage Month: Free Guided Tours', 'In celebration of National Heritage Month, the Museo de Labo will be hosting free, fully guided tours for all students and families. Local historians will be on-site to walk visitors through our colonial-era exhibits, sharing untold stories of local heroes and the founding of our municipality. Refreshments will be provided in the courtyard after the tour. Please sign our digital guestbook to reserve your spot!', 'event', '2026-05-15', '2026-03-10', NULL),
(3, 'Local Family Donates 19th Century Heirloom', 'The Museo de Labo extends its deepest gratitude to the Dela Cruz family for their generous donation of a beautifully preserved 19th-century weaving loom. This incredible piece of local history was used by their great-grandparents and has been kept in pristine condition for over a hundred years. It is currently undergoing minor preservation work and will be on display in the Cultural Relics wing by the end of the month.', 'news', NULL, '2026-03-10', NULL),
(4, 'Museo de Labo Launches Comprehensive Digital Catalog', 'We are incredibly proud to announce the official launch of the Museo de Labo Digital Catalog! In our ongoing effort to preserve the rich heritage of Camarines Norte and make it accessible to everyone, students, researchers, and history enthusiasts can now request access to view our historical artifacts online. Approved guests can browse high-resolution images and detailed historical accounts of our collections from anywhere in the world.', 'news', NULL, '2026-03-10', '1773106158_hjpmdjax21rmt0cwt9majw9k70.png');

-- Guests
INSERT INTO guests (id, guest_name, visitor_type, organization, gender, residence, nationality, headcount, male_count, female_count, num_days, purpose, contact_no, visit_date) VALUES
(2, 'jonel ramos', 'Individual', NULL, 'Male', 'Labo', 'Filipino', 1, 1, 0, 1, 'Information', '09929139222', '2026-02-27'),
(3, 'Eric John Kenneth Briones', 'Individual', NULL, 'Male', 'Labo', 'Filipino', 1, 1, 0, 3, 'Information', '0912345678', '2026-02-27'),
(4, 'Vincent', 'Individual', NULL, 'Male', 'Labo', 'Filipino', 1, 1, 0, 6, 'Information', '0912345678', '2026-02-27'),
(6, 'Garou', 'Group', 'Labo National High School', 'Male', 'Labo', 'Filipino', 30, 10, 20, 1, 'School Visit', '+639123123213', '2026-03-13');
