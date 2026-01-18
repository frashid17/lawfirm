-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 18, 2026 at 01:21 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lawfirm_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ADMIN`
--

CREATE TABLE `ADMIN` (
  `AdminId` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `PhoneNo` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ADMIN`
--

INSERT INTO `ADMIN` (`AdminId`, `FirstName`, `LastName`, `PhoneNo`, `Email`, `Username`, `Password`, `CreatedAt`) VALUES
(1, 'System', 'Administrator', '1234567890', 'admin@lawfirm.com', 'admin', '$2y$10$PQE1MNQiIoAZt4Hb/bBUvemG6n04in2JJgbrY/Ti.37jRxwloj0be', '2026-01-16 18:10:03');

-- --------------------------------------------------------

--
-- Table structure for table `ADVOCATE`
--

CREATE TABLE `ADVOCATE` (
  `AdvtId` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `PhoneNo` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Address` text DEFAULT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Status` varchar(20) DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `SecurityQuestion` varchar(255) DEFAULT NULL,
  `SecurityAnswer` varchar(255) DEFAULT NULL,
  `IsLocked` tinyint(1) DEFAULT 0,
  `FailedAttempts` int(11) DEFAULT 0,
  `LockedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ADVOCATE`
--

INSERT INTO `ADVOCATE` (`AdvtId`, `FirstName`, `LastName`, `PhoneNo`, `Email`, `Address`, `Username`, `Password`, `Status`, `CreatedAt`, `SecurityQuestion`, `SecurityAnswer`, `IsLocked`, `FailedAttempts`, `LockedAt`) VALUES
(1, 'John', 'Munyoki', '0987654321', 'john.munyoki@lawfirm.com', 'Nairobi, Kenya', 'advocate1', '$2y$10$8D3iJsZmSYfOYqD.rHGqw.bIP8I2n10jMLo8pv70ahjjQHjVPgrom', 'Active', '2026-01-16 18:10:03', NULL, NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `BILLING`
--

CREATE TABLE `BILLING` (
  `BillId` int(11) NOT NULL,
  `ClientId` int(11) NOT NULL,
  `CaseNo` int(11) DEFAULT NULL,
  `Date` date NOT NULL,
  `Amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Deposit` decimal(10,2) DEFAULT 0.00,
  `Installments` decimal(10,2) DEFAULT 0.00,
  `Status` varchar(50) DEFAULT 'Pending',
  `Description` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `BILLING`
--

INSERT INTO `BILLING` (`BillId`, `ClientId`, `CaseNo`, `Date`, `Amount`, `Deposit`, `Installments`, `Status`, `Description`, `CreatedAt`) VALUES
(8, 3, 2, '2026-01-17', 30000.00, 17000.00, 0.00, 'Partially Paid', 'test bill', '2026-01-17 05:19:21');

-- --------------------------------------------------------

--
-- Table structure for table `CASE`
--

CREATE TABLE `CASE` (
  `CaseNo` int(11) NOT NULL,
  `CaseName` varchar(200) NOT NULL,
  `CaseType` varchar(100) NOT NULL,
  `Court` varchar(200) DEFAULT NULL,
  `ClientId` int(11) NOT NULL,
  `Status` varchar(50) DEFAULT 'Active',
  `Description` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CASE`
--

INSERT INTO `CASE` (`CaseNo`, `CaseName`, `CaseType`, `Court`, `ClientId`, `Status`, `Description`, `CreatedAt`) VALUES
(2, 'Kamau vs. ABC Company', 'Civil', 'High Court Nairobi', 3, 'Active', 'Contract dispute case', '2026-01-16 17:48:11');

-- --------------------------------------------------------

--
-- Table structure for table `CASE_ASSIGNMENT`
--

CREATE TABLE `CASE_ASSIGNMENT` (
  `AssId` int(11) NOT NULL,
  `CaseNo` int(11) NOT NULL,
  `AdvtId` int(11) NOT NULL,
  `AssignedDate` date NOT NULL,
  `Status` varchar(50) DEFAULT 'Active',
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CASE_ASSIGNMENT`
--

INSERT INTO `CASE_ASSIGNMENT` (`AssId`, `CaseNo`, `AdvtId`, `AssignedDate`, `Status`, `Notes`) VALUES
(4, 2, 1, '2026-01-17', 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `CLIENT`
--

CREATE TABLE `CLIENT` (
  `ClientId` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `PhoneNo` varchar(20) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CLIENT`
--

INSERT INTO `CLIENT` (`ClientId`, `FirstName`, `LastName`, `PhoneNo`, `Email`, `Address`, `CreatedAt`) VALUES
(1, 'Peter', 'Kamau', '254712345678', 'peter.kamau@email.com', 'Nairobi, Kenya', '2026-01-16 17:47:35'),
(2, 'Peter', 'Kamau', '254712345678', 'peter.kamau@email.com', 'Nairobi, Kenya', '2026-01-16 17:48:11'),
(3, 'Milkah', 'Milkah', '07111111111', 'milkah@mail.com', 'milkah123', '2026-01-16 18:54:49');

-- --------------------------------------------------------

--
-- Table structure for table `CLIENT_AUTH`
--

CREATE TABLE `CLIENT_AUTH` (
  `AuthId` int(11) NOT NULL,
  `ClientId` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `LastLogin` timestamp NULL DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `SecurityQuestion` varchar(255) DEFAULT NULL,
  `SecurityAnswer` varchar(255) DEFAULT NULL,
  `IsLocked` tinyint(1) DEFAULT 0,
  `FailedAttempts` int(11) DEFAULT 0,
  `LockedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CLIENT_AUTH`
--

INSERT INTO `CLIENT_AUTH` (`AuthId`, `ClientId`, `Username`, `Password`, `IsActive`, `LastLogin`, `CreatedAt`, `SecurityQuestion`, `SecurityAnswer`, `IsLocked`, `FailedAttempts`, `LockedAt`) VALUES
(1, 3, 'milkah', '$2y$10$8SZwlsW39xQpSxGrOkPumuuewgnmxowfFpyy8R.WPx7keX4LGVCP6', 1, '2026-01-17 21:45:18', '2026-01-16 18:55:00', NULL, NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `CONTACT`
--

CREATE TABLE `CONTACT` (
  `PhoneNo` varchar(20) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Location` varchar(200) DEFAULT NULL,
  `CaseNo` int(11) NOT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `DOCUMENT`
--

CREATE TABLE `DOCUMENT` (
  `DocumentId` int(11) NOT NULL,
  `CaseNo` int(11) NOT NULL,
  `DocumentName` varchar(255) NOT NULL,
  `DocumentCategory` varchar(100) NOT NULL,
  `FilePath` varchar(500) NOT NULL,
  `FileSize` int(11) NOT NULL,
  `FileType` varchar(50) NOT NULL,
  `UploadedBy` int(11) NOT NULL,
  `UploadedByRole` enum('admin','advocate','receptionist','client') NOT NULL,
  `Version` int(11) DEFAULT 1,
  `IsCurrentVersion` tinyint(1) DEFAULT 1,
  `Description` text DEFAULT NULL,
  `UploadedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `EVENT`
--

CREATE TABLE `EVENT` (
  `EventId` int(11) NOT NULL,
  `EventName` varchar(200) NOT NULL,
  `EventType` varchar(100) NOT NULL,
  `Date` datetime NOT NULL,
  `CaseNo` int(11) NOT NULL,
  `Description` text DEFAULT NULL,
  `Location` varchar(200) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `EVENT`
--

INSERT INTO `EVENT` (`EventId`, `EventName`, `EventType`, `Date`, `CaseNo`, `Description`, `Location`, `CreatedAt`) VALUES
(1, 'test meeting', 'Meeting', '2026-01-19 08:00:00', 2, '', 'Mombasa', '2026-01-17 04:45:05');

-- --------------------------------------------------------

--
-- Table structure for table `MESSAGE`
--

CREATE TABLE `MESSAGE` (
  `MessageId` int(11) NOT NULL,
  `CaseNo` int(11) NOT NULL,
  `ClientId` int(11) NOT NULL,
  `AdvocateId` int(11) NOT NULL,
  `SenderRole` enum('client','advocate') NOT NULL,
  `Message` text NOT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `MESSAGE`
--

INSERT INTO `MESSAGE` (`MessageId`, `CaseNo`, `ClientId`, `AdvocateId`, `SenderRole`, `Message`, `IsRead`, `CreatedAt`) VALUES
(50, 2, 3, 1, 'advocate', 'hi', 1, '2026-01-17 04:18:01'),
(51, 2, 3, 1, 'advocate', 'hi milkah', 1, '2026-01-17 04:19:34'),
(52, 2, 3, 1, 'advocate', 'pdf', 1, '2026-01-17 04:20:24'),
(53, 2, 3, 1, 'client', 'hello', 1, '2026-01-17 04:27:52'),
(54, 2, 3, 1, 'client', 'working now', 1, '2026-01-17 04:28:04'),
(55, 2, 3, 1, 'client', '.', 1, '2026-01-17 04:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `PAYMENT_HISTORY`
--

CREATE TABLE `PAYMENT_HISTORY` (
  `PaymentId` int(11) NOT NULL,
  `BillId` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `PaymentDate` datetime NOT NULL DEFAULT current_timestamp(),
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `PAYMENT_HISTORY`
--

INSERT INTO `PAYMENT_HISTORY` (`PaymentId`, `BillId`, `Amount`, `PaymentDate`, `Notes`, `CreatedAt`) VALUES
(1, 8, 2000.00, '2026-01-17 08:27:28', 'Payment of KES 2,000.00 recorded by client', '2026-01-17 05:27:28');

-- --------------------------------------------------------

--
-- Table structure for table `RECEPTIONIST`
--

CREATE TABLE `RECEPTIONIST` (
  `RecId` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `PhoneNo` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `SecurityQuestion` varchar(255) DEFAULT NULL,
  `SecurityAnswer` varchar(255) DEFAULT NULL,
  `IsLocked` tinyint(1) DEFAULT 0,
  `FailedAttempts` int(11) DEFAULT 0,
  `LockedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `RECEPTIONIST`
--

INSERT INTO `RECEPTIONIST` (`RecId`, `FirstName`, `LastName`, `PhoneNo`, `Email`, `Username`, `Password`, `CreatedAt`, `SecurityQuestion`, `SecurityAnswer`, `IsLocked`, `FailedAttempts`, `LockedAt`) VALUES
(1, 'Mary', 'Maheli', '1122334455', 'mary.maheli@lawfirm.com', 'receptionist1', '$2y$10$mbvWY8tHpWz9b0R/DaXXLe8TDx/fKMaavKxC7yeTkAcpcbGvLdG9G', '2026-01-16 18:10:03', NULL, NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `TASK`
--

CREATE TABLE `TASK` (
  `TaskId` int(11) NOT NULL,
  `CaseNo` int(11) NOT NULL,
  `TaskTitle` varchar(200) NOT NULL,
  `TaskDescription` text DEFAULT NULL,
  `AssignedTo` int(11) DEFAULT NULL,
  `AssignedToRole` enum('admin','advocate','receptionist') NOT NULL,
  `Priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `Status` enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `DueDate` date DEFAULT NULL,
  `CreatedBy` int(11) NOT NULL,
  `CreatedByRole` enum('admin','advocate','receptionist') NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `CompletedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `USER_PROFILE`
--

CREATE TABLE `USER_PROFILE` (
  `ProfileId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `UserRole` enum('admin','advocate','receptionist') NOT NULL,
  `ProfilePicture` varchar(500) DEFAULT NULL,
  `Bio` text DEFAULT NULL,
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ADMIN`
--
ALTER TABLE `ADMIN`
  ADD PRIMARY KEY (`AdminId`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `ADVOCATE`
--
ALTER TABLE `ADVOCATE`
  ADD PRIMARY KEY (`AdvtId`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `idx_advocate_locked` (`IsLocked`);

--
-- Indexes for table `BILLING`
--
ALTER TABLE `BILLING`
  ADD PRIMARY KEY (`BillId`),
  ADD KEY `CaseNo` (`CaseNo`),
  ADD KEY `idx_client` (`ClientId`),
  ADD KEY `idx_date` (`Date`);

--
-- Indexes for table `CASE`
--
ALTER TABLE `CASE`
  ADD PRIMARY KEY (`CaseNo`),
  ADD KEY `idx_client` (`ClientId`),
  ADD KEY `idx_status` (`Status`);

--
-- Indexes for table `CASE_ASSIGNMENT`
--
ALTER TABLE `CASE_ASSIGNMENT`
  ADD PRIMARY KEY (`AssId`),
  ADD UNIQUE KEY `unique_assignment` (`CaseNo`,`AdvtId`),
  ADD KEY `idx_case` (`CaseNo`),
  ADD KEY `idx_advocate` (`AdvtId`);

--
-- Indexes for table `CLIENT`
--
ALTER TABLE `CLIENT`
  ADD PRIMARY KEY (`ClientId`),
  ADD KEY `idx_phone` (`PhoneNo`);

--
-- Indexes for table `CLIENT_AUTH`
--
ALTER TABLE `CLIENT_AUTH`
  ADD PRIMARY KEY (`AuthId`),
  ADD UNIQUE KEY `ClientId` (`ClientId`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `idx_username` (`Username`),
  ADD KEY `idx_client` (`ClientId`),
  ADD KEY `idx_client_auth_locked` (`IsLocked`);

--
-- Indexes for table `CONTACT`
--
ALTER TABLE `CONTACT`
  ADD PRIMARY KEY (`PhoneNo`),
  ADD KEY `idx_case` (`CaseNo`);

--
-- Indexes for table `DOCUMENT`
--
ALTER TABLE `DOCUMENT`
  ADD PRIMARY KEY (`DocumentId`),
  ADD KEY `idx_case` (`CaseNo`),
  ADD KEY `idx_category` (`DocumentCategory`),
  ADD KEY `idx_version` (`Version`);

--
-- Indexes for table `EVENT`
--
ALTER TABLE `EVENT`
  ADD PRIMARY KEY (`EventId`),
  ADD KEY `idx_date` (`Date`),
  ADD KEY `idx_case` (`CaseNo`);

--
-- Indexes for table `MESSAGE`
--
ALTER TABLE `MESSAGE`
  ADD PRIMARY KEY (`MessageId`),
  ADD KEY `idx_case` (`CaseNo`),
  ADD KEY `idx_client` (`ClientId`),
  ADD KEY `idx_advocate` (`AdvocateId`),
  ADD KEY `idx_read` (`IsRead`);

--
-- Indexes for table `PAYMENT_HISTORY`
--
ALTER TABLE `PAYMENT_HISTORY`
  ADD PRIMARY KEY (`PaymentId`),
  ADD KEY `idx_bill` (`BillId`),
  ADD KEY `idx_date` (`PaymentDate`);

--
-- Indexes for table `RECEPTIONIST`
--
ALTER TABLE `RECEPTIONIST`
  ADD PRIMARY KEY (`RecId`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `idx_receptionist_locked` (`IsLocked`);

--
-- Indexes for table `TASK`
--
ALTER TABLE `TASK`
  ADD PRIMARY KEY (`TaskId`),
  ADD KEY `idx_case` (`CaseNo`),
  ADD KEY `idx_assigned` (`AssignedTo`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_priority` (`Priority`);

--
-- Indexes for table `USER_PROFILE`
--
ALTER TABLE `USER_PROFILE`
  ADD PRIMARY KEY (`ProfileId`),
  ADD UNIQUE KEY `unique_user` (`UserId`,`UserRole`),
  ADD KEY `idx_user` (`UserId`,`UserRole`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ADMIN`
--
ALTER TABLE `ADMIN`
  MODIFY `AdminId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ADVOCATE`
--
ALTER TABLE `ADVOCATE`
  MODIFY `AdvtId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `BILLING`
--
ALTER TABLE `BILLING`
  MODIFY `BillId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `CASE`
--
ALTER TABLE `CASE`
  MODIFY `CaseNo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `CASE_ASSIGNMENT`
--
ALTER TABLE `CASE_ASSIGNMENT`
  MODIFY `AssId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `CLIENT`
--
ALTER TABLE `CLIENT`
  MODIFY `ClientId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `CLIENT_AUTH`
--
ALTER TABLE `CLIENT_AUTH`
  MODIFY `AuthId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `DOCUMENT`
--
ALTER TABLE `DOCUMENT`
  MODIFY `DocumentId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `EVENT`
--
ALTER TABLE `EVENT`
  MODIFY `EventId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `MESSAGE`
--
ALTER TABLE `MESSAGE`
  MODIFY `MessageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `PAYMENT_HISTORY`
--
ALTER TABLE `PAYMENT_HISTORY`
  MODIFY `PaymentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `RECEPTIONIST`
--
ALTER TABLE `RECEPTIONIST`
  MODIFY `RecId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `TASK`
--
ALTER TABLE `TASK`
  MODIFY `TaskId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `USER_PROFILE`
--
ALTER TABLE `USER_PROFILE`
  MODIFY `ProfileId` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `BILLING`
--
ALTER TABLE `BILLING`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`ClientId`) REFERENCES `CLIENT` (`ClientId`) ON DELETE CASCADE,
  ADD CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`CaseNo`) REFERENCES `CASE` (`CaseNo`) ON DELETE SET NULL;

--
-- Constraints for table `CASE`
--
ALTER TABLE `CASE`
  ADD CONSTRAINT `case_ibfk_1` FOREIGN KEY (`ClientId`) REFERENCES `CLIENT` (`ClientId`) ON DELETE CASCADE;

--
-- Constraints for table `CASE_ASSIGNMENT`
--
ALTER TABLE `CASE_ASSIGNMENT`
  ADD CONSTRAINT `case_assignment_ibfk_1` FOREIGN KEY (`CaseNo`) REFERENCES `CASE` (`CaseNo`) ON DELETE CASCADE,
  ADD CONSTRAINT `case_assignment_ibfk_2` FOREIGN KEY (`AdvtId`) REFERENCES `ADVOCATE` (`AdvtId`) ON DELETE CASCADE;

--
-- Constraints for table `CLIENT_AUTH`
--
ALTER TABLE `CLIENT_AUTH`
  ADD CONSTRAINT `client_auth_ibfk_1` FOREIGN KEY (`ClientId`) REFERENCES `CLIENT` (`ClientId`) ON DELETE CASCADE;

--
-- Constraints for table `CONTACT`
--
ALTER TABLE `CONTACT`
  ADD CONSTRAINT `contact_ibfk_1` FOREIGN KEY (`CaseNo`) REFERENCES `CASE` (`CaseNo`) ON DELETE CASCADE;

--
-- Constraints for table `DOCUMENT`
--
ALTER TABLE `DOCUMENT`
  ADD CONSTRAINT `document_ibfk_1` FOREIGN KEY (`CaseNo`) REFERENCES `CASE` (`CaseNo`) ON DELETE CASCADE;

--
-- Constraints for table `EVENT`
--
ALTER TABLE `EVENT`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`CaseNo`) REFERENCES `CASE` (`CaseNo`) ON DELETE CASCADE;

--
-- Constraints for table `MESSAGE`
--
ALTER TABLE `MESSAGE`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`CaseNo`) REFERENCES `CASE` (`CaseNo`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`ClientId`) REFERENCES `CLIENT` (`ClientId`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_3` FOREIGN KEY (`AdvocateId`) REFERENCES `ADVOCATE` (`AdvtId`) ON DELETE CASCADE;

--
-- Constraints for table `PAYMENT_HISTORY`
--
ALTER TABLE `PAYMENT_HISTORY`
  ADD CONSTRAINT `payment_history_ibfk_1` FOREIGN KEY (`BillId`) REFERENCES `BILLING` (`BillId`) ON DELETE CASCADE;

--
-- Constraints for table `TASK`
--
ALTER TABLE `TASK`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`CaseNo`) REFERENCES `CASE` (`CaseNo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
