DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10, 2) NOT NULL,
  `old_price` decimal(10, 2) DEFAULT NULL,
  `discount` decimal(5, 2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `pieces` int(11) DEFAULT NULL,
  `items` varchar(255) DEFAULT NULL,
  `compressed_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `admin_details` (`name`, `email`, `phone`, `gst_number`,`shopaddress`) VALUES
('Admin Name', 'admin@example.com', '1234567890', '22AAAAA0000A1Z5',"my address");


DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `gst_rate` DECIMAL(5, 2) NOT NULL,
  `discount` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `last_enquiry_number` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`gst_rate`, `discount`) VALUES
(18.00, 50.00);

DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
