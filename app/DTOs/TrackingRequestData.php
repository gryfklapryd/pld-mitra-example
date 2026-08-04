<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Http\Requests\Api\TrackingRequest;

/**
 * Permintaan tracking yang masuk dari PLD — kontrak §3.
 */
final readonly class TrackingRequestData
{
    /**
     * @param  array<int, string>  $userLogins
     */
    public function __construct(
        public string $contractVersion,
        public array $userLogins,
    ) {}

    public static function fromRequest(TrackingRequest $request): self
    {
        /** @var array{contractVersion: string, userLogins: array<int, string>} $validated */
        $validated = $request->validated();

        return new self(
            contractVersion: $validated['contractVersion'],
            userLogins: array_values(array_unique(
                array_map(trim(...), $validated['userLogins']),
            )),
        );
    }
}
