-- =====================================================
-- TABLE: PAYMENT_HISTORY
-- Stores individual payment transactions for invoices
-- =====================================================
CREATE TABLE IF NOT EXISTS PAYMENT_HISTORY (
    PaymentId INT AUTO_INCREMENT PRIMARY KEY,
    BillId INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    PaymentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Notes TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BillId) REFERENCES BILLING(BillId) ON DELETE CASCADE,
    INDEX idx_bill (BillId),
    INDEX idx_date (PaymentDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
