<?php

namespace App\Services\Courier\DTOs;

class CourierWebhookResult
{
    public function __construct(
        public bool $success,
        public ?string $orderNumber = null,
        public ?string $consignmentId = null,
        public ?string $trackingCode = null,
        public ?string $orderStatus = null,
        public ?string $paymentStatus = null,
        public ?string $comment = null,
        public array $rawPayload = []
    ) {}

    public static function success(
        ?string $orderNumber,
        ?string $consignmentId,
        ?string $trackingCode,
        ?string $orderStatus,
        ?string $paymentStatus = null,
        ?string $comment = null,
        array $rawPayload = []
    ): self {
        return new self(
            success: true,
            orderNumber: $orderNumber,
            consignmentId: $consignmentId,
            trackingCode: $trackingCode,
            orderStatus: $orderStatus,
            paymentStatus: $paymentStatus,
            comment: $comment,
            rawPayload: $rawPayload
        );
    }

    public static function failure(string $comment, array $rawPayload = []): self
    {
        return new self(
            success: false,
            comment: $comment,
            rawPayload: $rawPayload
        );
    }
}

