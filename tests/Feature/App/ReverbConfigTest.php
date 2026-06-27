<?php

declare(strict_types=1);

it('keeps the installed reverb start-server keys available', function (): void {
    expect(config('reverb.servers.reverb.pulse_ingest_interval'))->toBeInt();
    expect(config('reverb.servers.reverb.telescope_ingest_interval'))->toBeInt();
});
