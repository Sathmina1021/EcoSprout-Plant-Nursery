-- EcoSprout Plant Nursery Database
-- Run this in phpMyAdmin or MySQL CLI

SET FOREIGN_KEY_CHECKS=0;
DROP DATABASE IF EXISTS ecosprout_db;
CREATE DATABASE ecosprout_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS=1;
USE ecosprout_db;

-- =============================================
-- USERS TABLE (customers, staff, admins)
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'staff', 'admin') DEFAULT 'customer',
    profile_image VARCHAR(255) DEFAULT 'default.png',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- PLANT CATEGORIES
-- =============================================
CREATE TABLE plant_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- PLANTS TABLE
-- =============================================
CREATE TABLE plants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    botanical_name VARCHAR(150),
    description TEXT,
    care_instructions TEXT,
    sunlight_requirement ENUM('full_sun', 'partial_shade', 'full_shade') DEFAULT 'partial_shade',
    water_frequency ENUM('daily', 'every_2_days', 'weekly', 'bi_weekly') DEFAULT 'weekly',
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    image VARCHAR(255) DEFAULT 'default_plant.jpg',
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES plant_categories(id) ON DELETE SET NULL
);

-- =============================================
-- GARDENING TOOLS/ACCESSORIES
-- =============================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    image VARCHAR(255) DEFAULT 'default_product.jpg',
    category ENUM('tools', 'accessories', 'fertilizers', 'pots') DEFAULT 'tools',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- SERVICES (landscaping, garden design, etc.)
-- =============================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    duration VARCHAR(50),
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- WORKSHOPS / EVENTS
-- =============================================
CREATE TABLE workshops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    instructor VARCHAR(100),
    workshop_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    location VARCHAR(200),
    max_participants INT DEFAULT 20,
    current_participants INT DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0.00,
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- WORKSHOP REGISTRATIONS
-- =============================================
CREATE TABLE workshop_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workshop_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (workshop_id, user_id)
);

-- =============================================
-- ORDERS
-- =============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_address TEXT NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash_on_delivery', 'bank_transfer', 'online') DEFAULT 'cash_on_delivery',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- ORDER ITEMS
-- =============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_type ENUM('plant', 'product') NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- =============================================
-- SERVICE BOOKINGS
-- =============================================
CREATE TABLE service_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    booking_date DATE NOT NULL,
    preferred_time TIME,
    address TEXT NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    total_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- =============================================
-- CUSTOMER QUERIES / INQUIRIES
-- =============================================
CREATE TABLE queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    category ENUM('plant_care', 'order', 'service', 'workshop', 'general') DEFAULT 'general',
    status ENUM('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
    response TEXT,
    responded_by INT,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- BLOG POSTS
-- =============================================
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    image VARCHAR(255),
    category ENUM('plant_care', 'diy', 'seasonal', 'landscaping', 'news') DEFAULT 'plant_care',
    is_published TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- CART (session-based but DB backed)
-- =============================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_type ENUM('plant', 'product') NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- SEED DEFAULT DATA
-- =============================================

-- Default login accounts
-- Admin password: Admin@123
-- Staff password: Staff@123
-- Customer password: User@123
INSERT INTO users (full_name, email, password, role, phone, address, is_active) VALUES
('System Administrator', 'admin@ecosprout.lk', '$2y$12$GKi6GkaOPLtmlrmiFmrnYuCSHwmyEEYE0Z22.lzigdV1942NZfaTG', 'admin', '0710000001', 'EcoSprout Head Office', 1),
('Staff Member', 'staff@ecosprout.lk', '$2y$12$0GsFVMhX9SLhlOnOUsqRZ.QtCiMoTAtJarXPjc3hEGP//RlcOG1pO', 'staff', '0710000002', 'EcoSprout Nursery, Kegalle', 1),
('Demo Customer', 'user@ecosprout.lk', '$2y$12$TNYaakkZ54RAvxQefmMgSeqthcULZunv2hbiKfKeItbF2izX2OM96', 'customer', '0710000003', 'Kegalle, Sri Lanka', 1);

-- Plant categories
INSERT INTO plant_categories (name, description, image) VALUES
('Indoor Plants', 'Perfect for brightening up your living spaces', 'category-indoor.jpg'),
('Outdoor Plants', 'Hardy plants for gardens and landscapes', 'category-outdoor.jpg'),
('Ornamental Plants', 'Beautiful flowering and decorative varieties', 'category-ornamental.jpg'),
('Edible Plants', 'Grow your own food - herbs, vegetables & fruits', 'category-edible.jpg');

-- Sample plants
INSERT INTO plants (category_id, name, botanical_name, description, care_instructions, sunlight_requirement, water_frequency, price, stock_quantity, image, is_featured) VALUES
(1, 'Peace Lily', 'Spathiphyllum wallisii', 'The Peace Lily is a beautiful indoor plant known for its elegant white blooms and air-purifying qualities.', 'Water weekly. Keep in indirect light. Mist leaves occasionally.', 'partial_shade', 'weekly', 850.00, 25, 'peace-lily.jpg', 1),
(1, 'Snake Plant', 'Sansevieria trifasciata', 'One of the hardiest houseplants. Excellent air purifier that removes toxins from the air.', 'Water every 2-3 weeks. Tolerates low light. Avoid overwatering.', 'partial_shade', 'bi_weekly', 650.00, 40, 'snake-plant.jpg', 1),
(1, 'Monstera', 'Monstera deliciosa', 'The iconic Swiss cheese plant with stunning split leaves. A statement piece for any home.', 'Water weekly. Bright indirect light. Wipe leaves monthly.', 'partial_shade', 'weekly', 1200.00, 15, 'monstera.jpg', 1),
(2, 'Bougainvillea', 'Bougainvillea spectabilis', 'Vibrant climbing plant with stunning magenta bracts. Perfect for garden walls and fences.', 'Water regularly. Full sun. Prune after flowering.', 'full_sun', 'every_2_days', 450.00, 30, 'bougainvillea.jpg', 1),
(2, 'Hibiscus', 'Hibiscus rosa-sinensis', 'Tropical beauty with large colorful blooms. National flower of Malaysia.', 'Water daily. Full sun. Fertilize monthly during growing season.', 'full_sun', 'daily', 380.00, 45, 'hibiscus.jpg', 1),
(3, 'Anthurium', 'Anthurium andraeanum', 'Stunning waxy red spathes make this an exotic centerpiece plant.', 'Water weekly. Indirect light. High humidity preferred.', 'partial_shade', 'weekly', 950.00, 20, 'anthurium.jpg', 1),
(4, 'Curry Leaf', 'Murraya koenigii', 'Essential cooking herb in Sri Lankan cuisine. Grow your own fresh curry leaves.', 'Water regularly. Full sun. Fertilize every 2 months.', 'full_sun', 'every_2_days', 320.00, 60, 'curry-leaf.jpg', 1),
(4, 'Chilli', 'Capsicum annuum', 'Easy to grow chilli plants. Great for home gardens and balconies.', 'Water daily. Full sun. Stake when fruiting.', 'full_sun', 'daily', 180.00, 80, 'chilli.jpg', 1);

-- Sample services
INSERT INTO services (name, description, price, duration, image) VALUES
('Garden Design & Landscaping', 'Complete garden transformation with professional design and planting.', 15000.00, '1-2 days', 'landscape-design.jpg'),
('Lawn Maintenance', 'Regular lawn mowing, edging, and fertilizing service.', 3500.00, '3-4 hours', 'lawn-maintenance.jpg'),
('Plant Health Check', 'Expert assessment of your plants with treatment recommendations.', 1500.00, '1-2 hours', 'plant-health-check.jpg'),
('Pot Planting Service', 'Professional pot arrangement and planting for indoor/outdoor spaces.', 2500.00, '2-3 hours', 'pot-planting.jpg'),
('Irrigation System Setup', 'Install drip or sprinkler irrigation for efficient watering.', 8000.00, '1 day', 'irrigation-setup.jpg');

-- Sample workshops
INSERT INTO workshops (title, description, instructor, workshop_date, start_time, end_time, location, max_participants, price, image) VALUES
('Introduction to Indoor Gardening', 'Learn the basics of caring for indoor plants. Perfect for beginners.', 'Ms. Chamari Silva', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '09:00:00', '12:00:00', 'EcoSprout Nursery, Kegalle', 15, 1500.00, 'monstera.jpg'),
('DIY Terrarium Workshop', 'Create your own beautiful glass terrarium to take home.', 'Mr. Nimal Perera', DATE_ADD(CURDATE(), INTERVAL 21 DAY), '14:00:00', '17:00:00', 'EcoSprout Nursery, Kegalle', 12, 2500.00, 'snake-plant.jpg'),
('Organic Composting Masterclass', 'Turn kitchen waste into garden gold. Hands-on composting session.', 'Dr. Priya Fernando', DATE_ADD(CURDATE(), INTERVAL 28 DAY), '09:00:00', '13:00:00', 'EcoSprout Nursery, Kegalle', 20, 1000.00, 'curry-leaf.jpg');

-- Sample blog posts
INSERT INTO blog_posts (author_id, title, slug, excerpt, content, image, category, is_published) VALUES
(1, '10 Best Indoor Plants for Sri Lankan Homes', '10-best-indoor-plants-sri-lanka', 'Discover the perfect indoor plants that thrive in Sri Lanka's tropical climate.', '<p>Sri Lanka's warm, humid climate is actually perfect for many beautiful indoor plants. Here are our top picks...</p>', 'blog-indoor-plants.jpg', 'plant_care', 1),
(1, 'How to Start a Kitchen Garden at Home', 'start-kitchen-garden-home', 'Grow your own vegetables and herbs right at home with these simple tips.', '<p>Starting a kitchen garden is easier than you think. All you need is a small space, some pots, and the right plants...</p>', 'blog-kitchen-garden.jpg', 'diy', 1),
(1, 'Monsoon Season Plant Care Guide', 'monsoon-season-plant-care', 'Protect your garden during Sri Lanka's monsoon season with these expert tips.', '<p>The monsoon season brings heavy rainfall that can damage plants if you're not prepared. Here's how to keep your garden thriving...</p>', 'blog-monsoon-care.jpg', 'seasonal', 1);

-- Sample products
INSERT INTO products (name, description, price, stock_quantity, category) VALUES
('Garden Trowel Set', 'Premium stainless steel trowel set for precise planting.', 1200.00, 30, 'tools'),
('Watering Can (5L)', 'Elegant copper-finish watering can with long spout.', 1800.00, 20, 'tools'),
('Organic Fertilizer (1kg)', 'Slow-release organic fertilizer perfect for all plants.', 650.00, 50, 'fertilizers'),
('Ceramic Pot Set (3 sizes)', 'Beautiful hand-painted ceramic pots in earthy tones.', 2200.00, 15, 'pots'),
('Plant Mister Bottle', 'Fine mist spray bottle for humidity-loving plants.', 450.00, 40, 'accessories');
