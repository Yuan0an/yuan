-- Migration: Add receipt_data column to payments table
-- Run this once on your live Railway MySQL database.
-- It is idempotent — safe to run multiple times.

ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS receipt_data LONGTEXT;
