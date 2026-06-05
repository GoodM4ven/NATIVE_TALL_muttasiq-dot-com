<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('enables ipad support in the generated ios project', function () {
    expect(config('nativephp.ipad'))->toBeTrue();

    $projectPath = base_path('nativephp/ios/NativePHP.xcodeproj/project.pbxproj');

    expect(File::exists($projectPath))->toBeTrue();

    $projectContents = File::get($projectPath);

    expect($projectContents)
        ->toContain('TARGETED_DEVICE_FAMILY = "1,2";')
        ->toContain('INFOPLIST_KEY_UISupportedInterfaceOrientations_iPad = "UIInterfaceOrientationPortrait UIInterfaceOrientationPortraitUpsideDown UIInterfaceOrientationLandscapeLeft UIInterfaceOrientationLandscapeRight";')
        ->not->toContain('TARGETED_DEVICE_FAMILY = 1;');
});
