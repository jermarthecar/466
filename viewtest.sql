USE z1978803;

-- View all employees
SELECT * FROM Employee;

-- View all customers
SELECT * FROM Customer;

-- View all products
SELECT * FROM Product;

-- View orders with customer names
SELECT o.OrderID, c.Name, o.Status, o.OrderTotal 
FROM `Order` o
JOIN Customer c ON o.CustomerID = c.CustomerID;