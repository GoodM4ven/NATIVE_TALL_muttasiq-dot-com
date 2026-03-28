<div
    class="quran-manager-shell"
    data-no-swipe
    dir="rtl"
>
    <div class="quran-manager-toolbar">
        <p class="quran-manager-toolbar-note">
            آخر 100 انتقال غير معلّم، مع الاحتفاظ بجميع العناصر التي تمت إضافة وسوم لها.
        </p>

        <button
            class="quran-manager-toolbar-button"
            data-quran-history-clear
            type="button"
            x-bind:disabled="navigationHistory.length === 0"
            x-on:click="clearNavigationHistory()"
        >
            مسح غير المعلّم
        </button>
    </div>

    <div class="quran-manager-table-shell">
        <table class="quran-manager-table">
            <thead>
                <tr>
                    <th>الانتقال</th>
                    <th>السورة</th>
                    <th>النوع</th>
                    <th>الوسوم</th>
                </tr>
            </thead>
            <tbody x-ref="historyRowsList">
                <template x-if="navigationHistory.length === 0">
                    <tr>
                        <td
                            class="quran-manager-empty"
                            colspan="4"
                        >لا توجد عناصر بعد.</td>
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
                                x-text="`صفحة ${entry.page_number}`"
                            ></button>
                        </td>
                        <td x-text="historyEntrySurahName(entry)"></td>
                        <td x-text="historyEntrySourceLabel(entry)"></td>
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
                                            aria-label="حذف الوسم"
                                            x-on:click.stop.prevent="removeHistoryEntryTag(entry.id, tag)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </template>

                                <input
                                    class="quran-manager-tags-entry"
                                    data-quran-history-tags
                                    type="text"
                                    placeholder="أضف وسمًا..."
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
