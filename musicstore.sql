-- CSCI 466(SECTION 1) GROUP PROJECT

-- CREATE THE DATABASE
-- CREATE DATABASE MusicStore;
USE z1978803; -- Use your own z-number database

DROP TABLE IF EXISTS Shipment;
DROP TABLE IF EXISTS OrderItem;
DROP TABLE IF EXISTS `Order`;
DROP TABLE IF EXISTS CartItem;
DROP TABLE IF EXISTS Cart;
DROP TABLE IF EXISTS Employee;
DROP TABLE IF EXISTS Product;
DROP TABLE IF EXISTS Customer;

-- CUSTOMER TABLE
CREATE TABLE Customer (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    ShippingAddress TEXT NOT NULL
);

-- PRODUCT TABLE
CREATE TABLE Product (
    ProductID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Price DECIMAL(10,2) NOT NULL CHECK (Price > 0),
    StockQuantity INT NOT NULL CHECK (StockQuantity >= 0),
    Description TEXT,
    Category VARCHAR(50)
);

-- CART TABLE
CREATE TABLE Cart (
    CartID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    FOREIGN KEY (CustomerID) REFERENCES Customer(CustomerID) ON DELETE CASCADE,
    UNIQUE (CustomerID)
);

-- CARTITEM TABLE
CREATE TABLE CartItem (
    CartItemID INT AUTO_INCREMENT PRIMARY KEY,
    CartID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL CHECK (Quantity > 0),
    FOREIGN KEY (CartID) REFERENCES Cart(CartID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Product(ProductID) ON DELETE CASCADE,
    UNIQUE (CartID, ProductID)
);

-- ORDER TABLE
CREATE TABLE `Order` (
    OrderID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerID INT NOT NULL,
    OrderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Status ENUM('Processing', 'Shipped', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Processing',
    ShippingAddress TEXT NOT NULL,
    BillingAddress TEXT NOT NULL,
    OrderTotal DECIMAL(10,2) NOT NULL,
    PaymentMethod ENUM('Credit Card', 'Debit Card') NOT NULL,
    FOREIGN KEY (CustomerID) REFERENCES Customer(CustomerID)
);

-- ORDERITEM TABLE
CREATE TABLE OrderItem (
    OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL CHECK (Quantity > 0),
    PriceAtOrderTime DECIMAL(10,2) NOT NULL,
    Subtotal DECIMAL(10,2) GENERATED ALWAYS AS (Quantity * PriceAtOrderTime) STORED,
    FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Product(ProductID)
);

-- EMPLOYEE TABLE
CREATE TABLE Employee (
    EmployeeID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    AccessLevel ENUM('Owner', 'Employee') NOT NULL DEFAULT 'Employee'
);

-- SHIPMENT TABLE
CREATE TABLE Shipment (
    ShipmentID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID INT NOT NULL,
    DateShipped TIMESTAMP NOT NULL,
    TrackingNum VARCHAR(50) NOT NULL,
    Notes TEXT,
    FOREIGN KEY (OrderID) REFERENCES `Order`(OrderID)
);


-- INSERT SAMPLE DATA INTO CUSTOMER TABLE
INSERT INTO Customer (Name, Email, Password, ShippingAddress) VALUES
('Sarah Johnson', 'sarah.johnson@email.com', SHA2('password123', 256), '123 Maple Street, Chicago, IL 60601'),
('Michael Chen', 'michael.chen@email.com', SHA2('securepass456', 256), '456 Oak Avenue, Boston, MA 02108'),
('Emily Wilson', 'emily.wilson@email.com', SHA2('wilson789', 256), '789 Pine Road, Austin, TX 78701'),
('David Kim', 'david.kim@email.com', SHA2('kimdavid2023', 256), '321 Elm Boulevard, Denver, CO 80202'),
('Jessica Martinez', 'jessica.m@email.com', SHA2('jessm2023!', 256), '654 Cedar Lane, Seattle, WA 98101'),
('Robert Taylor', 'robert.taylor@email.com', SHA2('taylorrobert99', 256), '987 Birch Street, Miami, FL 33101');

-- INSERT SAMPLE DATA INTO PRODUCT TABLE (Vinyl Records)
INSERT INTO Product (Name, Price, StockQuantity, Description, Category) VALUES
('The Beatles - Abbey Road', 29.99, 50, '1969 Original Master Recording', 'Rock'),
('Pink Floyd - Dark Side of the Moon', 34.99, 30, '180g Anniversary Edition', 'Progressive Rock'),
('Michael Jackson - Thriller', 27.99, 40, 'Limited Edition Red Vinyl', 'Pop'),
('Led Zeppelin - IV', 32.99, 25, 'Remastered 180g Vinyl', 'Rock'),
('Fleetwood Mac - Rumours', 28.99, 35, '2017 Reissue', 'Rock'),
('Prince - Purple Rain', 31.99, 20, 'Original Soundtrack', 'Funk/Rock'),
('Bob Dylan - Highway 61 Revisited', 26.99, 45, 'Mono Version', 'Folk Rock'),
('Radiohead - OK Computer', 33.99, 15, 'OKNOTOK Reissue', 'Alternative Rock'),
('Nirvana - Nevermind', 30.99, 18, '30th Anniversary Edition', 'Grunge'),
('The Rolling Stones - Exile on Main St', 35.99, 22, 'Double LP', 'Rock');

-- INSERT SAMPLE DATA INTO EMPLOYEE TABLE
INSERT INTO Employee (Name, Email, Password, AccessLevel) VALUES
('Puff Daddy', 'pdiddy@freak.mp3', SHA2('babyoil69', 256), 'Owner'),
('Paris Richards', 'parisr@music.wav', SHA2('parisfrance18', 256), 'Employee'),
('Jermar Johnson', 'jermar@diddy.com', SHA2('diddy', 256), 'Employee')
('Tosin Nojeed', 'tsin999@music.mp3', SHA2('tsin99', 256), 'Employee')
('Matthew Chris', 'matt@musicstore.com, SHA2('shawncombs68', 256), 'Employee');

-- INSERT SAMPLE DATA INTO CART TABLE
INSERT INTO Cart (CustomerID) VALUES
(1), (2), (3), (4), (5), (6);

-- INSERT SAMPLE DATA INTO CARTITEM TABLE
INSERT INTO CartItem (CartID, ProductID, Quantity) VALUES
(1, 3, 1),  -- Sarah has 1 Thriller record
(1, 7, 1),  -- Sarah has 1 Bob Dylan
(2, 1, 2),  -- Michael has 2 Abbey Road
(3, 10, 1), -- Emily has 1 Exile on Main St
(4, 2, 1),  -- David has 1 Dark Side
(4, 4, 1),  -- David has 1 Led Zeppelin
(5, 5, 1),  -- Jessica has 1 Rumours
(6, 8, 2);  -- Robert has 2 OK Computer

-- INSERT SAMPLE DATA INTO ORDER TABLE
INSERT INTO `Order` (CustomerID, Status, ShippingAddress, BillingAddress, OrderTotal, PaymentMethod) VALUES
(1, 'Delivered', '123 Maple Street, Chicago, IL 60601', '123 Maple Street, Chicago, IL 60601', 54.98, 'Credit Card'),
(2, 'Shipped', '456 Oak Avenue, Boston, MA 02108', '456 Oak Avenue, Boston, MA 02108', 59.98, 'Debit Card'),
(3, 'Processing', '789 Pine Road, Austin, TX 78701', '789 Pine Road, Austin, TX 78701', 35.99, 'Credit Card'),
(4, 'Delivered', '321 Elm Boulevard, Denver, CO 80202', '321 Elm Boulevard, Denver, CO 80202', 67.98, 'Credit Card'),
(5, 'Shipped', '654 Cedar Lane, Seattle, WA 98101', '654 Cedar Lane, Seattle, WA 98101', 28.99, 'Debit Card'),
(6, 'Processing', '987 Birch Street, Miami, FL 33101', '987 Birch Street, Miami, FL 33101', 67.98, 'Credit Card');

-- INSERT SAMPLE DATA INTO ORDERITEM TABLE
INSERT INTO OrderItem (OrderID, ProductID, Quantity, PriceAtOrderTime) VALUES
(1, 3, 2, 27.99),  -- Sarah's Thriller
(1, 7, 1, 26.99),  -- Sarah's Bob Dylan
(2, 1, 1, 29.99),  -- Michael's Abbey Road
(3, 10, 3, 35.99), -- Emily's Exile on Main St
(4, 2, 1, 34.99),  -- David's Dark Side
(4, 4, 1, 32.99),  -- David's Led Zeppelin
(5, 5, 2, 28.99),  -- Jessica's Rumours
(6, 8, 1, 33.99);  -- Robert's OK Computer

-- INSERT SAMPLE DATA INTO SHIPMENT TABLE
INSERT INTO Shipment (OrderID, DateShipped, TrackingNum, Notes) VALUES
(1, '2023-05-15 10:30:00', 'UPS123456789', 'Left at front porch'),
(2, '2023-06-01 14:15:00', 'FEDEX987654321', 'Signed by neighbor'),
(4, '2023-06-10 09:45:00', 'USPS456123789', 'Delivered to mailbox'),
(5, '2023-06-12 16:20:00', 'DHL789456123', 'Requires signature');
