-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2024 at 04:38 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rentronic`
--

-- --------------------------------------------------------

--
-- Table structure for table `agent`
--

CREATE TABLE `agent` (
  `AgentID` varchar(6) NOT NULL,
  `AgentName` varchar(255) NOT NULL,
  `AgentEmail` varchar(255) NOT NULL,
  `AgentWhatsapp` varchar(20) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `AgentType` varchar(50) NOT NULL CHECK (`AgentType` in ('Studio','Non-Studio')),
  `AccessLevel` int(11) NOT NULL CHECK (`AccessLevel` between 1 and 2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agent`
--

INSERT INTO `agent` (`AgentID`, `AgentName`, `AgentEmail`, `AgentWhatsapp`, `Password`, `AgentType`, `AccessLevel`) VALUES
('A001', 'Admin', 'accs.owais@gmail.com', '013 660 0635', '1001', 'Studio', 1),
('A002', 'Syed', 'syedr10.sr@gmail.com', '011 2542 3742', '2001', 'Studio', 2),
('A003', 'Maton', 'halimatonsyuhada98@gmail.com', '013 941 8101', '2002', 'Non-Studio', 2),
('A004', 'Ziman', 'afiqhaziman98@gmail.com', '019 920 7371', '2003', 'Non-Studio', 2),
('A005', 'Pija', 'nrfhzh788@gmail.com', '014 315 8230', '2004', 'Studio', 2),
('A006', 'Amir', 'amiraiman464@gmail.com', '019 292 1839', '2005', 'Non-Studio', 2),
('A007', 'Naufal', 'naufallatif17@gmail.com', '018 374 9007', '2006', 'Studio', 2),
('A008', 'Rasyeed', 'rasyeed.aatsb@gmail.com', '018 367 9397', '2007', 'Studio', 2),
('A009', 'Abell', 'nabilah.amir997@gmail.com', '010 6565 037', '2008', 'Studio', 2);

-- --------------------------------------------------------

--
-- Table structure for table `bed`
--

CREATE TABLE `bed` (
  `BedID` varchar(6) NOT NULL,
  `RoomID` varchar(6) NOT NULL,
  `UnitID` varchar(6) NOT NULL,
  `BedNo` varchar(255) NOT NULL,
  `BedRentAmount` decimal(10,2) NOT NULL,
  `BedStatus` varchar(50) DEFAULT NULL CHECK (`BedStatus` in ('Vacant','Rented')),
  `AgentID` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bed`
--

INSERT INTO `bed` (`BedID`, `RoomID`, `UnitID`, `BedNo`, `BedRentAmount`, `BedStatus`, `AgentID`) VALUES
('B0001', 'R0001', 'U1001', 'C-15-10-R1-B1', 250.00, NULL, 'A001'),
('B0002', 'R0001', 'U1001', 'C-15-10-R1-B2', 250.00, NULL, NULL),
('B0003', 'R0001', 'U1001', 'C-15-10-R1-B3', 250.00, NULL, NULL),
('B0004', 'R0001', 'U1001', 'C-15-10-R1-B4', 250.00, NULL, NULL),
('B0005', 'R0001', 'U1001', 'C-15-10-R1-B5', 250.00, NULL, NULL),
('B0006', 'R0001', 'U1001', 'C-15-10-R1-B6', 250.00, NULL, NULL),
('B0007', 'R0002', 'U1001', 'C-15-10-R2-B7', 250.00, NULL, NULL),
('B0008', 'R0002', 'U1001', 'C-15-10-R2-B8', 250.00, NULL, NULL),
('B0009', 'R0002', 'U1001', 'C-15-10-R2-B9', 250.00, NULL, NULL),
('B0010', 'R0002', 'U1001', 'C-15-10-R2-B10', 250.00, NULL, NULL),
('B0011', 'R0003', 'U1001', 'C-15-10-R3-B11', 250.00, NULL, NULL),
('B0012', 'R0003', 'U1001', 'C-15-10-R3-B12', 250.00, NULL, NULL),
('B0013', 'R0003', 'U1001', 'C-15-10-R3-B13', 250.00, NULL, NULL),
('B0014', 'R0003', 'U1001', 'C-15-10-R3-B14', 250.00, NULL, NULL),
('B0015', 'R0004', 'U1001', 'C-15-10-R4-B15', 250.00, NULL, NULL),
('B0016', 'R0004', 'U1001', 'C-15-10-R4-B16', 250.00, NULL, NULL),
('B0017', 'R0005', 'U1002', 'C-16-09-R1-B1', 250.00, NULL, NULL),
('B0018', 'R0005', 'U1002', 'C-16-09-R1-B2', 250.00, NULL, NULL),
('B0019', 'R0005', 'U1002', 'C-16-09-R1-B3', 250.00, NULL, NULL),
('B0020', 'R0005', 'U1002', 'C-16-09-R1-B4', 250.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `property`
--

CREATE TABLE `property` (
  `PropertyID` varchar(6) NOT NULL,
  `PropertyName` varchar(255) NOT NULL,
  `PropertyType` varchar(255) NOT NULL,
  `PropertyOwn` int(11) DEFAULT NULL,
  `Location` varchar(255) NOT NULL,
  `Maps` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property`
--

INSERT INTO `property` (`PropertyID`, `PropertyName`, `PropertyType`, `PropertyOwn`, `Location`, `Maps`) VALUES
('1', 'PV9', 'Condominium', NULL, 'Setapak, KL', 'https://www.google.com/maps?q=3.2181805,101.7241724'),
('2', 'MakChili', 'Terrace', NULL, 'Kemaman, Terengganu', 'https://www.google.com/maps?q=4.2412824,103.3973365'),
('5', 'Cyber', 'Terrace', NULL, 'Cyberjaya, Selangor', 'https://www.google.com/maps?q=2.9223242,101.638862'),
('6', 'PV3', 'Condominium', NULL, 'Setapak, KL', 'https://www.google.com/maps?q=3.2230402,101.7292976'),
('7', 'PV2', 'Condominium', NULL, 'Setapak, KL', 'https://www.google.com/maps?q=3.2244948,101.7288293'),
('8', 'PV5', 'Condominium', NULL, 'Setapak, KL', 'https://www.google.com/maps?q=3.2230758,101.7292379'),
('9', 'Menara Alpha Condominium', 'Condominium', NULL, 'Wangsa Maju, KL', 'https://www.google.com/maps?q=3.2072772,101.7363808');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `RoomID` varchar(6) NOT NULL,
  `UnitID` varchar(6) NOT NULL,
  `RoomNo` varchar(255) NOT NULL,
  `RoomRentAmount` decimal(10,2) NOT NULL,
  `Katil` int(11) DEFAULT NULL,
  `RoomStatus` varchar(50) DEFAULT NULL CHECK (`RoomStatus` in ('Vacant','Partially Rented','Fully Rented')),
  `AgentID` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`RoomID`, `UnitID`, `RoomNo`, `RoomRentAmount`, `Katil`, `RoomStatus`, `AgentID`) VALUES
('R0001', 'U1001', 'C-15-10-R1', 750.00, 6, NULL, NULL),
('R0002', 'U1001', 'C-15-10-R2', 750.00, 4, NULL, NULL),
('R0003', 'U1001', 'C-15-10-R3', 750.00, 4, NULL, NULL),
('R0004', 'U1001', 'C-15-10-R4', 550.00, 2, NULL, NULL),
('R0005', 'U1002', 'C-16-09-R1', 750.00, 4, NULL, NULL),
('R0006', 'U1002', 'C-16-09-R2', 750.00, 4, NULL, NULL),
('R0007', 'U1002', 'C-16-09-R3', 750.00, 4, NULL, NULL),
('R0008', 'U1002', 'C-16-09-R4', 550.00, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tenant`
--

CREATE TABLE `tenant` (
  `TenantID` varchar(6) NOT NULL,
  `UnitID` varchar(6) DEFAULT NULL,
  `RoomID` varchar(6) DEFAULT NULL,
  `BedID` varchar(6) DEFAULT NULL,
  `AgentID` varchar(6) NOT NULL,
  `TenantName` varchar(255) NOT NULL,
  `TenantPhoneNo` varchar(20) DEFAULT NULL,
  `TenantEmail` varchar(255) DEFAULT NULL,
  `RentStartDate` date NOT NULL,
  `RentExpiryDate` date NOT NULL,
  `TenantStatus` tinyint(1) NOT NULL,
  `Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `unit`
--

CREATE TABLE `unit` (
  `UnitID` varchar(6) NOT NULL,
  `PropertyID` varchar(6) NOT NULL,
  `UnitNo` varchar(255) NOT NULL,
  `FloorPlan` varchar(255) DEFAULT NULL,
  `Investor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit`
--

INSERT INTO `unit` (`UnitID`, `PropertyID`, `UnitNo`, `FloorPlan`, `Investor`) VALUES
('U1001', '1', 'C-15-10', '', 'Internal'),
('U1002', '1', 'C-16-09', '', 'Internal'),
('U2001', '2', '50302', '', 'Internal'),
('U2002', '2', '50317', '', 'Internal'),
('U5001', '5', 'E-22B', '', 'Internal'),
('U6001', '6', 'A-9-7', '', 'External'),
('U6002', '6', 'A-3-8', '', 'Internal'),
('U6003', '6', 'C-3-2', '', 'External'),
('U7001', '7', 'B-16-3A', '', 'External'),
('U7002', '7', 'A-9-2', '', 'External'),
('U7003', '7', 'A-29-6', '', 'External'),
('U7004', '7', 'B-13-9', '', 'External'),
('U8001', '8', 'A-11-11', '', 'External'),
('U8002', '8', 'A-17-2', '', 'External');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`AgentID`);

--
-- Indexes for table `bed`
--
ALTER TABLE `bed`
  ADD PRIMARY KEY (`BedID`),
  ADD KEY `RoomID` (`RoomID`),
  ADD KEY `UnitID` (`UnitID`),
  ADD KEY `AgentID` (`AgentID`);

--
-- Indexes for table `property`
--
ALTER TABLE `property`
  ADD PRIMARY KEY (`PropertyID`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`RoomID`),
  ADD KEY `UnitID` (`UnitID`),
  ADD KEY `AgentID` (`AgentID`);

--
-- Indexes for table `tenant`
--
ALTER TABLE `tenant`
  ADD PRIMARY KEY (`TenantID`),
  ADD KEY `UnitID` (`UnitID`),
  ADD KEY `RoomID` (`RoomID`),
  ADD KEY `BedID` (`BedID`),
  ADD KEY `AgentID` (`AgentID`);

--
-- Indexes for table `unit`
--
ALTER TABLE `unit`
  ADD PRIMARY KEY (`UnitID`),
  ADD KEY `PropertyID` (`PropertyID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bed`
--
ALTER TABLE `bed`
  ADD CONSTRAINT `bed_ibfk_1` FOREIGN KEY (`RoomID`) REFERENCES `room` (`RoomID`),
  ADD CONSTRAINT `bed_ibfk_2` FOREIGN KEY (`UnitID`) REFERENCES `unit` (`UnitID`),
  ADD CONSTRAINT `bed_ibfk_3` FOREIGN KEY (`AgentID`) REFERENCES `agent` (`AgentID`);

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `room_ibfk_1` FOREIGN KEY (`UnitID`) REFERENCES `unit` (`UnitID`),
  ADD CONSTRAINT `room_ibfk_2` FOREIGN KEY (`AgentID`) REFERENCES `agent` (`AgentID`);

--
-- Constraints for table `tenant`
--
ALTER TABLE `tenant`
  ADD CONSTRAINT `tenant_ibfk_1` FOREIGN KEY (`UnitID`) REFERENCES `unit` (`UnitID`),
  ADD CONSTRAINT `tenant_ibfk_2` FOREIGN KEY (`RoomID`) REFERENCES `room` (`RoomID`),
  ADD CONSTRAINT `tenant_ibfk_3` FOREIGN KEY (`BedID`) REFERENCES `bed` (`BedID`),
  ADD CONSTRAINT `tenant_ibfk_4` FOREIGN KEY (`AgentID`) REFERENCES `agent` (`AgentID`);

--
-- Constraints for table `unit`
--
ALTER TABLE `unit`
  ADD CONSTRAINT `unit_ibfk_1` FOREIGN KEY (`PropertyID`) REFERENCES `property` (`PropertyID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
