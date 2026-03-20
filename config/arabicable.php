<?php

declare(strict_types=1);
use GoodMaven\Arabicable\Database\Factories\CommonArabicTextFactory;
use GoodMaven\Arabicable\Models\CommonArabicText;

$rawDataCandidates = basename(base_path()) === 'workbench'
    ? [
        dirname(base_path()).'/resources/raw-data',
        base_path('vendor/goodm4ven/arabicable/resources/raw-data'),
        base_path('resources/raw-data'),
    ]
    : [
        base_path('vendor/goodm4ven/arabicable/resources/raw-data'),
        base_path('resources/raw-data'),
    ];
$rawDataPath = '';

foreach ($rawDataCandidates as $candidatePath) {
    if ($candidatePath === '' || ! is_dir($candidatePath)) {
        continue;
    }

    $rawDataPath = rtrim($candidatePath, '/');

    break;
}

if ($rawDataPath === '') {
    $rawDataPath = base_path('resources/raw-data');
}

return [
    'validate_configuration' => true,

    'raw_data_path' => $rawDataPath,

    'features' => [
        'quran' => true,
    ],

    'special_characters' => [
        'harakat' => ['ِ', 'ُ', 'ٓ', 'ٰ', 'ْ', 'ٌ', 'ٍ', 'ً', 'ّ', 'َ', 'ـ', 'ٗ'],

        'indian_numerals' => ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        'arabic_numerals' => ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],

        'punctuation_marks' => ['.', '!', ':', '-'],
        'foreign_punctuation_marks' => [',', ';', '?'],
        'arabic_punctuation_marks' => ['،', '؛', '؟'],

        'enclosing_marks' => ["'", '"', '/'],
        'enclosing_starter_marks' => ['(', '{', '[', '<', '<<'],
        'enclosing_ender_marks' => [')', '}', ']', '>', '>>'],
        'arabic_enclosing_marks' => ['/'],
        'arabic_enclosing_starter_marks' => ['﴾', '⦗', '«'],
        'arabic_enclosing_ender_marks' => ['﴿', '⦘', '»'],
    ],

    'property_suffix_keys' => [
        'numbers_to_indian' => '_indian',
        'text_with_harakat' => '_with_harakat',
        'text_for_search' => '_searchable',
        'text_for_stem' => '_stemmed',
    ],

    'numerals' => [
        // Historical naming is kept for BC:
        // `arabic` => ASCII digits (0-9), `indian` => Arabic-Indic digits (٠-٩), `both` => keep both forms.
        'search_mode' => 'arabic',
    ],

    'spacing_after_punctuation_only' => false,

    'normalized_punctuation_marks' => [
        '«' => ['<', '<<'],
        '»' => ['>', '>>'],
    ],

    'space_preserved_enclosings' => [
        '{',
        '}',
    ],

    'common_arabic_text' => [
        'model' => CommonArabicText::class,
        'factory' => CommonArabicTextFactory::class,
        'cache_key' => 'common_arabic_texts',
    ],

    'search' => [
        // Higher numbers rank first in SQL ordering helpers.
        'ranking_weights' => [
            'exact' => 100,
            'searchable' => 80,
            'stemmed' => 60,
            'token_overlap' => 40,
        ],
        'comprehensive' => [
            // Safety cap for generated terms used in comprehensive query helpers.
            'max_terms' => 60,
            // Enable lexicon-based expansion for word-family variants in comprehensive search plans.
            'expand_with_word_variants' => true,
            // Max number of variants added per query token.
            'max_word_variants_per_token' => 24,
            // Max total variant terms merged into a search plan.
            'max_variant_terms' => 120,
            // Ignore variant terms shorter than this length.
            'min_variant_term_length' => 2,
            // Variant output strategy: all | roots | stems | original_words.
            'variant_mode' => 'all',
            // Always remove stop words from expanded variants.
            'strip_stop_words_from_variants' => true,
        ],
    ],

    'processing' => [
        // If true, removeDiacritics() keeps shadda while removing other marks.
        'keep_shadda_when_stripping_diacritics' => false,
        // If true, addHarakat() also uses external vocalization dictionaries (after stop-words map).
        'add_harakat_use_vocalization_map' => false,
    ],

    'data_sources' => [
        // All package dictionaries are read from arabicable.raw_data_path.
        'stop_words_forms' => $rawDataPath.'/stop-words/compiled-stop-words-forms.tsv',
        'stop_words_classified' => $rawDataPath.'/stop-words/compiled-stop-words-classified.tsv',
        'vocalizations' => $rawDataPath.'/vocalizations/compiled-vocalizations.tsv',
        'word_variants' => $rawDataPath.'/verbs/compiled-word-variants.tsv',
        'quran_word_index' => $rawDataPath.'/quran/compiled-quran-word-index.tsv',
        'quran_othmani_surahs_dir' => $rawDataPath.'/quran',
        'quran_exegesis_databases_dir' => $rawDataPath.'/quran/exegesis',
        'quran_layout_databases_dir' => $rawDataPath.'/quran/layouts',
        'quran_lexicon_databases_dir' => $rawDataPath.'/quran/lexicon',
    ],
];
