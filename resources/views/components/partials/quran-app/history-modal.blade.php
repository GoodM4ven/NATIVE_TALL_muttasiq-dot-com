<div
    class="quran-manager-shell"
    data-no-swipe
    dir="rtl"
>
    <div class="quran-manager-toolbar">
        <button
            class="quran-manager-toolbar-button"
            data-quran-history-clear
            type="button"
            x-bind:disabled="navigationHistory.length === 0"
            x-on:click="clearNavigationHistory()"
        >
            {{ app_arabic_text('مسح غير الموسوم') }}
        </button>
    </div>

    <div class="quran-manager-table-shell">
        <table class="quran-manager-table">
            <thead>
                <tr>
                    <th>{{ app_arabic_text('الصفحة') }}</th>
                    <th>{{ app_arabic_text('السورة') }}</th>
                    <th>{{ app_arabic_text('النوع') }}</th>
                    <th>{{ app_arabic_text('ملاحظة') }}</th>
                    <th>{{ app_arabic_text('الوسوم') }}</th>
                </tr>
            </thead>
            <tbody x-ref="historyRowsList">
                <template x-if="navigationHistory.length === 0">
                    <tr>
                        <td
                            class="quran-manager-empty"
                            colspan="5"
                        >{{ app_arabic_text('لا توجد عناصر بعد.') }}</td>
                    </tr>
                </template>

                <template
                    x-for="entry in navigationHistory"
                    :key="`quran-history-entry-${entry.id}`"
                >
                    <tr
                        data-quran-history-row
                        x-bind:class="historyRowEffectClass(entry)"
                    >
                        <td>
                            <button
                                class="quran-manager-link"
                                data-quran-history-go
                                type="button"
                                x-on:click="goToHistoryEntry(entry)"
                                x-text="`${entry.page_number}`"
                            ></button>
                        </td>
                        <td x-text="historyEntrySurahName(entry)"></td>
                        <td x-text="historyEntrySourceLabel(entry)"></td>
                        <td>
                            <input
                                class="quran-manager-input"
                                data-quran-history-note
                                type="text"
                                placeholder="-"
                                x-bind:value="entry.note ?? ''"
                                x-on:input.debounce.350ms="updateHistoryEntryNote(entry.id, $event.target.value)"
                            />
                        </td>
                        <td>
                            <div class="quran-manager-tags-field">
                                <template
                                    x-for="tag in (Array.isArray(entry.tags) ? entry.tags : [])"
                                    :key="`quran-history-tag-${entry.id}-${tag}`"
                                >
                                    <span class="quran-manager-tag-chip">
                                        <span x-text="tag"></span>
                                        <button
                                            class="quran-manager-tag-chip-remove"
                                            type="button"
                                            aria-label="{{ app_arabic_text('حذف الوسم') }}"
                                            x-on:click.stop.prevent="removeHistoryEntryTag(entry.id, tag)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </template>

                                <input
                                    class="quran-manager-tags-entry outline-none"
                                    data-quran-history-tags
                                    type="text"
                                    placeholder="{{ app_arabic_text('أضف وسمًا...') }}"
                                    x-bind:list="`quran-history-tags-suggestions-${entry.id}`"
                                    x-bind:value="historyTagDraft(entry.id)"
                                    x-on:input="setHistoryTagDraft(entry.id, $event.target.value)"
                                    x-on:keydown="
                                        if (['Enter', 'Tab', ','].includes($event.key)) {
                                            $event.preventDefault();
                                            commitHistoryTagDraft(entry.id);
                                        }
                                    "
                                    x-on:blur="commitHistoryTagDraft(entry.id)"
                                />

                                <datalist x-bind:id="`quran-history-tags-suggestions-${entry.id}`">
                                    <template
                                        x-for="tagSuggestion in historyTagSuggestions(entry.id)"
                                        :key="`quran-history-tag-suggestion-${entry.id}-${tagSuggestion}`"
                                    >
                                        <option x-bind:value="tagSuggestion"></option>
                                    </template>
                                </datalist>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
