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

-- CARTITEM table
CREATE TABLE CartItem (
    CartItemID INT AUTO_INCREMENT PRIMARY KEY,
    CartID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL CHECK (Quantity > 0),
    FOREIGN KEY (CartID) REFERENCES Cart(CartID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Product(ProductID) ON DELETE CASCADE,
    UNIQUE (CartID, ProductID)
);

-- ORDER table
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
('John Lennon', 'jlennon@beatles.com', SHA2('imagine123', 256), '251 Menlove Ave, Liverpool, UK'),
('Paul McCartney', 'paul@wings.com', SHA2('letitbe456', 256), '20 Forthlin Rd, Liverpool, UK'),
('Mick Jagger', 'mick@stones.com', SHA2('satisfaction789', 256), '3 Cheyne Walk, London, UK'),
('David Bowie', 'david@bowieworld.com', SHA2('ziggy1234', 256), '155 Philly Ave, NYC, USA'),
('Freddie Mercury', 'freddie@queen.com', SHA2('bohemian567', 256), '1 Logan Pl, London, UK'),
('Jimi Hendrix', 'jimi@exp.com', SHA2('purplehaze89', 256), '23 Brook St, London, UK');

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
('Brian Epstein', 'brian@musicstore.com', SHA2('beatles123', 256), 'Owner'),
('Paris Richards', 'parisr@music.wav', SHA2('parisfrance18', 256), 'Employee'),
('Jermar Johnson', 'jermar@diddy.com', SHA2('diddy', 256), 'Employee');

-- INSERT SAMPLE DATA INTO CART TABLE
INSERT INTO Cart (CustomerID) VALUES
(1), (2), (3), (4), (5), (6);

-- INSERT SAMPLE DATA INTO CARTITEM TABLE
INSERT INTO CartItem (CartID, ProductID, Quantity) VALUES
(1, 3, 2),  -- John has 2 Thriller records
(1, 7, 1),  -- John has 1 Bob Dylan
(2, 1, 1),  -- Paul has 1 Abbey Road
(3, 10, 3), -- Mick has 3 Exile on Main St
(4, 2, 1),  -- David has 1 Dark Side
(4, 4, 1),  -- David has 1 Led Zeppelin
(5, 5, 2),  -- Freddie has 2 Rumours
(6, 8, 1);  -- Jimi has 1 OK Computer

-- INSERT SAMPLE DATA INTO ORDER TABLE
INSERT INTO `Order` (CustomerID, Status, ShippingAddress, BillingAddress, OrderTotal) VALUES
(1, 'Delivered', '251 Menlove Ave, Liverpool, UK', '251 Menlove Ave, Liverpool, UK', 86.97),
(2, 'Shipped', '20 Forthlin Rd, Liverpool, UK', '20 Forthlin Rd, Liverpool, UK', 29.99),
(3, 'Processing', '3 Cheyne Walk, London, UK', '3 Cheyne Walk, London, UK', 107.97),
(4, 'Delivered', '155 Philly Ave, NYC, USA', '155 Philly Ave, NYC, USA', 67.98),
(5, 'Shipped', '1 Logan Pl, London, UK', '1 Logan Pl, London, UK', 57.98),
(6, 'Processing', '23 Brook St, London, UK', '23 Brook St, London, UK', 33.99);

-- INSERT SAMPLE DATA INTO ORDERITEM TABLE
INSERT INTO OrderItem (OrderID, ProductID, Quantity, PriceAtOrderTime) VALUES
(1, 3, 2, 27.99),  -- John's Thriller
(1, 7, 1, 26.99),  -- John's Bob Dylan
(2, 1, 1, 29.99),  -- Paul's Abbey Road
(3, 10, 3, 35.99), -- Mick's Exile on Main St
(4, 2, 1, 34.99),  -- David's Dark Side
(4, 4, 1, 32.99),  -- David's Led Zeppelin
(5, 5, 2, 28.99),  -- Freddie's Rumours
(6, 8, 1, 33.99);  -- Jimi's OK Computer

-- INSERT SAMPLE DATA INTO SHIPMENT TABLE
INSERT INTO Shipment (OrderID, DateShipped, TrackingNum, Notes) VALUES
(1, '2023-05-15 10:30:00', 'UPS123456789', 'Left at front porch'),
(2, '2023-06-01 14:15:00', 'FEDEX987654321', 'Signed by neighbor'),
(4, '2023-06-10 09:45:00', 'USPS456123789', 'Delivered to mailbox'),
(5, '2023-06-12 16:20:00', 'DHL789456123', 'Requires signature');
