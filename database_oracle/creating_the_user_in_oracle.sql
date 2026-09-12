ALTER SESSION SET CONTAINER = XEPDB1;
CREATE USER hostel IDENTIFIED BY hostel123;

-- Grant necessary privileges
GRANT CONNECT, RESOURCE TO hostel;
GRANT CREATE SESSION TO hostel;
GRANT UNLIMITED TABLESPACE TO hostel;

-- Connect with the new user
CONNECT hostel/hostel123@localhost:1521/XEPDB1
