<?php

declare(strict_types=1);

namespace App\Services\Native;

class IosSimulatorSelector
{
    public function selectUdidFromJson(string $json): ?string
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $devices = $data['devices'] ?? null;

        if (! is_array($devices)) {
            return null;
        }

        $bootedSimulator = null;
        $preferredIphone = null;
        $fallbackIphone = null;

        foreach ($devices as $runtimeDevices) {
            if (! is_array($runtimeDevices)) {
                continue;
            }

            foreach ($runtimeDevices as $device) {
                if (! is_array($device)) {
                    continue;
                }

                $udid = (string) ($device['udid'] ?? '');
                $name = (string) ($device['name'] ?? '');
                $state = (string) ($device['state'] ?? '');
                $isAvailable = ($device['isAvailable'] ?? false) === true;

                if ($udid === '') {
                    continue;
                }

                if ($state === 'Booted') {
                    $bootedSimulator ??= $udid;

                    continue;
                }

                if (! $isAvailable || ! str_contains($name, 'iPhone')) {
                    continue;
                }

                if ($name === 'iPhone 16 Pro') {
                    $preferredIphone ??= $udid;

                    continue;
                }

                $fallbackIphone ??= $udid;
            }
        }

        return $bootedSimulator ?? $preferredIphone ?? $fallbackIphone;
    }
}
