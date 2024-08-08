-- Table for storing user information
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL
);

-- Table for storing product information
CREATE TABLE products (
  id INT PRIMARY KEY AUTO_INCREMENT,
  seller_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  starting_price DECIMAL(10, 2) NOT NULL,
  current_price DECIMAL(10, 2) NOT NULL,
  current_winner_id INT,
  starting_datetime DATETIME NOT NULL,
  price_interval DECIMAL(10, 2) NOT NULL,
  price_reduction_interval INT NOT NULL,
  minimum_bid DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (seller_id) REFERENCES users(id),
  FOREIGN KEY (current_winner_id) REFERENCES users(id)
);

-- Table for storing bid information
CREATE TABLE bids (
  id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  bidder_id INT NOT NULL,
  bid_amount DECIMAL(10, 2) NOT NULL,
  bid_datetime DATETIME NOT NULL,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (bidder_id) REFERENCES users(id)
);