<?php

namespace App\Services\Courier\DTOs;

class CourierDispatchResult
{
    public function __construct(
        public bool $success,
        public ?string $consignmentId,
        public ?string $trackingCode,
        public ?string $trackingUrl,
        public string $provider,
        public ?string $message = null,
        public array $rawResponse = []
    ) {}

    public static function success(
        string $provider,
        string $consignmentId,
        string $trackingCode,
        string $trackingUrl,
        ?string $message = 'Consignment created successfully.',
        array $rawResponse = []
    ): self {
        return new self(
            success: true,
            consignmentId: $consignmentId,
            trackingCode: $trackingCode,
            trackingUrl: $trackingUrl,
            provider: $provider,
            message: $message,
            rawResponse: $rawResponse
        );
    }

    public static function failure(
        string $provider,
        string $message,
        array $rawResponse = []
    ): self {
        return new self(
            success: false,
            consignmentId: null,
            trackingCode: null,
            trackingUrl: null,
            provider: $provider,
            message: $message,
            rawResponse: $rawResponse
        );
    }
}

