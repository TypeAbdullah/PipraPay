<?php
declare(strict_types=1);

namespace PipraPay\PaymentAPI\DTO;

class PaymentResponseDTO
{
    private string $paymentId;
    private string $status;
    private float $amount;
    private string $currency;
    private array $auditTrail;
    private string $createdAt;

    public function __construct(
        string $paymentId,
        string $status,
        float $amount,
        string $currency,
        array $auditTrail,
        string $createdAt
    ) {
        $this->paymentId = $paymentId;
        $this->status = $status;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->auditTrail = $auditTrail;
        $this->createdAt = $createdAt;
    }

    /**
     * Converts the DTO into a strict JSON payload safe for client consumption.
     * It completely strips internal MongoDB _id fields and handles type casting.
     */
    public function toArray(): array
    {
        $safeAuditTrail = array_map(function ($entry) {
            return [
                'status' => (string) ($entry['status'] ?? ''),
                'timestamp' => isset($entry['timestamp']) ? $entry['timestamp']->toDateTime()->format('c') : '',
                'reason' => (string) ($entry['reason'] ?? '')
            ];
        }, $this->auditTrail);

        return [
            'payment_id' => $this->paymentId,
            'status' => $this->status,
            'amount' => $this->amount, // Amount returned as standard float for JSON API consumption
            'currency' => $this->currency,
            'audit_trail' => $safeAuditTrail,
            'created_at' => $this->createdAt,
        ];
    }
}
