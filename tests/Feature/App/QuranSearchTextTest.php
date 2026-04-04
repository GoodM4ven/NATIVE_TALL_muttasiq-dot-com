<?php

use GoodMaven\Arabicable\Support\Quran\QuranSearchText;

test('strict exact phrase variants include hamzated madd forms', function () {
    $variants = QuranSearchText::expandStrictExactPhraseVariants('فبأي آلاء ربكما');

    expect($variants)
        ->toContain('فباي الاء ربكما')
        ->toContain('فباي ءالاء ربكما');
});

test('broad quran search variants include hamzated madd forms', function () {
    $variants = QuranSearchText::expandVariants('فبأي آلاء ربكما');

    expect($variants)
        ->toContain('فباي الاء ربكما')
        ->toContain('فباي ءالاء ربكما');
});
