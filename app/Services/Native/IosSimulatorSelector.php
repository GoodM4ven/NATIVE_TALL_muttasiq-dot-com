<?php

declare(strict_types=1);

namespace App\Services\Native;

class IosSimulatorSelector
{
    public function selectUdidFromJson(string $json, string $preferredIphoneName = 'iPhone 17 Pro'): ?string
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $devices = $data['devices'] ?? null;

        if (! is_array($devices)) {
            return null;
        }

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
                $isAvailable = ($device['isAvailable'] ?? false) === true;

                if ($udid === '' || ! $isAvailable || ! str_contains($name, 'iPhone')) {
                    continue;
                }

                if ($name === $preferredIphoneName) {
                    return $udid;
                }
            }
        }

        return null;
    }
}
