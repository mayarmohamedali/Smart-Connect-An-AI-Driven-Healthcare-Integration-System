<?php

/**
 * Claim Model
 *
 * Handles all database operations for the claims table.
 * Used by process_claim.php to insert and update claims.
 */
class Claim {

    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Insert a new claim with status 'Pending' and immediately return its ID.
     * We insert first, then update — so there's always a claim record to trace.
     */
    public function insertPending(
        int    $record_id,
        int    $patient_id,
        int    $insurance_id,
        ?int   $service_id,
        float  $claim_amount
    ): ?int {

        $stmt = $this->conn->prepare("
            INSERT INTO claims
                (record_id, patient_id, insurance_id, service_id, treatment_cost, claim_amount, claim_status)
            VALUES
                (?, ?, ?, ?, ?, ?, 'Pending')
        ");

        $stmt->bind_param(
            'iiiidd',
            $record_id,
            $patient_id,
            $insurance_id,
            $service_id,
            $claim_amount,
            $claim_amount
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $claim_id = (int) $stmt->insert_id;
        $stmt->close();

        return $claim_id;
    }

    /**
     * Update the claim_status of a specific claim.
     * Only 'Accepted' or 'Rejected' should be passed here.
     */
    public function updateStatus(int $claim_id, string $status): bool {

        $stmt = $this->conn->prepare("
            UPDATE claims
            SET claim_status = ?
            WHERE claim_id = ?
            LIMIT 1
        ");

        $stmt->bind_param('si', $status, $claim_id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}